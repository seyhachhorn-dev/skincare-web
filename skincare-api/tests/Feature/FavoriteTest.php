<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FavoriteTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_save_a_product(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/favorites', ['product_id' => $product->id]);

        $response->assertCreated();
        $this->assertDatabaseHas('favorites', ['user_id' => $user->id, 'product_id' => $product->id]);
    }

    public function test_saving_the_same_product_twice_does_not_duplicate(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/favorites', ['product_id' => $product->id])->assertCreated();
        $this->postJson('/api/favorites', ['product_id' => $product->id])->assertCreated();

        $this->assertDatabaseCount('favorites', 1);
    }

    public function test_user_can_list_their_saved_products(): void
    {
        $user = User::factory()->create();
        $products = Product::factory()->count(3)->create();
        Sanctum::actingAs($user);

        foreach ($products as $product) {
            $this->postJson('/api/favorites', ['product_id' => $product->id]);
        }

        $response = $this->getJson('/api/favorites');

        $response->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_user_can_remove_a_saved_product(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        Sanctum::actingAs($user);
        $this->postJson('/api/favorites', ['product_id' => $product->id]);

        $response = $this->deleteJson("/api/favorites/{$product->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('favorites', ['user_id' => $user->id, 'product_id' => $product->id]);
    }
}
