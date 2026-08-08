<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->role = 'admin';
        $admin->save();

        return $admin;
    }

    public function test_admin_can_create_a_category(): void
    {
        Sanctum::actingAs($this->admin());

        $response = $this->postJson('/api/categories', ['name' => 'Masks', 'icon' => '🎭']);

        $response->assertCreated()->assertJsonPath('data.name', 'Masks');
        $this->assertDatabaseHas('categories', ['name' => 'Masks']);
    }

    public function test_regular_user_cannot_create_a_category(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson('/api/categories', ['name' => 'Masks']);

        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_a_category(): void
    {
        $response = $this->postJson('/api/categories', ['name' => 'Masks']);

        $response->assertStatus(401);
    }

    public function test_category_name_must_be_unique(): void
    {
        Sanctum::actingAs($this->admin());
        Category::factory()->create(['name' => 'Toner']);

        $response = $this->postJson('/api/categories', ['name' => 'Toner']);

        $response->assertStatus(422)->assertJsonValidationErrors('name');
    }

    public function test_admin_can_update_a_category(): void
    {
        Sanctum::actingAs($this->admin());
        $category = Category::factory()->create();

        $response = $this->putJson("/api/categories/{$category->id}", ['name' => 'Renamed']);

        $response->assertOk()->assertJsonPath('data.name', 'Renamed');
    }

    public function test_admin_can_delete_a_category(): void
    {
        Sanctum::actingAs($this->admin());
        $category = Category::factory()->create();

        $response = $this->deleteJson("/api/categories/{$category->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_regular_user_cannot_delete_a_category(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $category = Category::factory()->create();

        $response = $this->deleteJson("/api/categories/{$category->id}");

        $response->assertStatus(403);
    }
}
