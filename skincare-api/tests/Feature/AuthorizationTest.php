<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_update_another_users_address(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $address = Address::factory()->for($owner)->create();

        Sanctum::actingAs($intruder);

        $response = $this->putJson("/api/addresses/{$address->id}", ['house_no' => '99']);

        $response->assertStatus(403);
    }

    public function test_user_cannot_delete_another_users_address(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $address = Address::factory()->for($owner)->create();

        Sanctum::actingAs($intruder);

        $response = $this->deleteJson("/api/addresses/{$address->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('addresses', ['id' => $address->id]);
    }

    public function test_user_cannot_view_another_users_order(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $order = Order::factory()->for($owner)->create();

        Sanctum::actingAs($intruder);

        $response = $this->getJson("/api/orders/{$order->id}");

        $response->assertStatus(403);
    }

    public function test_user_cannot_modify_another_users_cart_item(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $product = Product::factory()->create();
        $cartItem = CartItem::factory()->for($owner)->for($product)->create();

        Sanctum::actingAs($intruder);

        $response = $this->patchJson("/api/cart/items/{$cartItem->id}", ['quantity' => 5]);

        $response->assertStatus(403);
    }

    public function test_user_can_access_their_own_addresses(): void
    {
        $user = User::factory()->create();
        Address::factory()->for($user)->count(2)->create();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/addresses');

        $response->assertOk()->assertJsonCount(2, 'data');
    }
}
