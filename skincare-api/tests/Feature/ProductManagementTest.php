<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->role = 'admin';
        $admin->save();

        return $admin;
    }

    public function test_admin_can_create_a_product(): void
    {
        Storage::fake('public');
        Sanctum::actingAs($this->admin());
        $category = Category::factory()->create();

        $response = $this->postJson('/api/products', [
            'category_id' => $category->id,
            'name' => 'New Serum',
            'description' => 'A brand new serum.',
            'price' => 850,
            // ->create() (not ->image()) so this doesn't require the GD extension —
            // Laravel's "image" validation rule checks MIME type, not pixel data.
            'image' => UploadedFile::fake()->create('serum.jpg', 10, 'image/jpeg'),
            'brand' => 'The Ordinary',
            'size' => '30 ml',
        ]);

        $response->assertCreated()->assertJsonPath('data.name', 'New Serum');
        $this->assertDatabaseHas('products', ['name' => 'New Serum', 'price' => 850]);
        Storage::disk('public')->assertExists($response->json('data.image'));
    }

    public function test_regular_user_cannot_create_a_product(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson('/api/products', [
            'name' => 'New Serum',
            'price' => 850,
            'image' => 'assets/images/pro1.png',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('products', ['name' => 'New Serum']);
    }

    public function test_guest_cannot_create_a_product(): void
    {
        $response = $this->postJson('/api/products', [
            'name' => 'New Serum',
            'price' => 850,
            'image' => 'assets/images/pro1.png',
        ]);

        $response->assertStatus(401);
    }

    public function test_admin_can_update_a_product(): void
    {
        Sanctum::actingAs($this->admin());
        $product = Product::factory()->create(['price' => 500]);

        $response = $this->putJson("/api/products/{$product->id}", ['price' => 750]);

        $response->assertOk()->assertJsonPath('data.price', 750);
    }

    public function test_admin_can_replace_a_products_image(): void
    {
        Storage::fake('public');
        Sanctum::actingAs($this->admin());
        $oldImage = UploadedFile::fake()->create('old.jpg', 10, 'image/jpeg')->store('products', 'public');
        $product = Product::factory()->create(['image' => $oldImage]);

        $response = $this->putJson("/api/products/{$product->id}", [
            'image' => UploadedFile::fake()->create('new.jpg', 10, 'image/jpeg'),
        ]);

        $response->assertOk();
        Storage::disk('public')->assertExists($response->json('data.image'));
        Storage::disk('public')->assertMissing($oldImage);
    }

    public function test_regular_user_cannot_update_a_product(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $product = Product::factory()->create(['price' => 500]);

        $response = $this->putJson("/api/products/{$product->id}", ['price' => 750]);

        $response->assertStatus(403);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'price' => 500]);
    }

    public function test_admin_can_delete_a_product(): void
    {
        Sanctum::actingAs($this->admin());
        $product = Product::factory()->create();

        $response = $this->deleteJson("/api/products/{$product->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_deleting_a_product_removes_its_stored_image(): void
    {
        Storage::fake('public');
        Sanctum::actingAs($this->admin());
        $image = UploadedFile::fake()->create('doomed.jpg', 10, 'image/jpeg')->store('products', 'public');
        $product = Product::factory()->create(['image' => $image]);

        $this->deleteJson("/api/products/{$product->id}")->assertOk();

        Storage::disk('public')->assertMissing($image);
    }

    public function test_regular_user_cannot_delete_a_product(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $product = Product::factory()->create();

        $response = $this->deleteJson("/api/products/{$product->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_creating_a_product_requires_required_fields(): void
    {
        Sanctum::actingAs($this->admin());

        $response = $this->postJson('/api/products', []);

        $response->assertStatus(422)->assertJsonValidationErrors(['name', 'price', 'image']);
    }
}
