<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_add_a_product_to_their_cart(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['price' => 500]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/cart/items', ['product_id' => $product->id, 'quantity' => 2]);

        $response->assertCreated()->assertJsonPath('data.quantity', 2)->assertJsonPath('data.total', 1000);
        $this->assertDatabaseHas('cart_items', ['user_id' => $user->id, 'product_id' => $product->id, 'quantity' => 2]);
    }

    public function test_adding_the_same_product_twice_increments_quantity_instead_of_duplicating(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/cart/items', ['product_id' => $product->id])->assertCreated();
        $this->postJson('/api/cart/items', ['product_id' => $product->id])->assertCreated();

        $this->assertDatabaseCount('cart_items', 1);
        $this->assertDatabaseHas('cart_items', ['user_id' => $user->id, 'product_id' => $product->id, 'quantity' => 2]);
    }

    public function test_user_can_update_cart_item_quantity(): void
    {
        $user = User::factory()->create();
        $cartItem = CartItem::factory()->for($user)->create(['quantity' => 1]);
        Sanctum::actingAs($user);

        $response = $this->patchJson("/api/cart/items/{$cartItem->id}", ['quantity' => 5]);

        $response->assertOk()->assertJsonPath('data.quantity', 5);
    }

    public function test_user_can_remove_a_cart_item(): void
    {
        $user = User::factory()->create();
        $cartItem = CartItem::factory()->for($user)->create();
        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/cart/items/{$cartItem->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('cart_items', ['id' => $cartItem->id]);
    }

    public function test_cart_index_returns_subtotal_and_points_earned(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['price' => 1000]);
        CartItem::factory()->for($user)->for($product)->create(['quantity' => 2]);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/cart');

        $response->assertOk()
            ->assertJsonPath('data.subtotal', 2000)
            ->assertJsonPath('data.points_earned', 200);
    }
}
