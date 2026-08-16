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

class OrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_place_an_order_from_their_cart(): void
    {
        $user = User::factory()->create(['points_balance' => 0]);
        $address = Address::factory()->for($user)->create();
        $product = Product::factory()->create(['price' => 1000]);
        CartItem::factory()->for($user)->for($product)->create(['quantity' => 2]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/orders', [
            'address_id' => $address->id,
            'payment_method' => 'bakong_khqr',
            'shipping_method' => 'dhl',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.total', 2000)
            ->assertJsonPath('data.points_earned', 200)
            ->assertJsonPath('data.item_count', 2)
            ->assertJsonPath('data.status', 'awaiting_payment')
            ->assertJsonPath('data.payment_status', 'pending');

        $this->assertMatchesRegularExpression('/^FD\d{8}$/', $response->json('data.order_number'));

        // Cart is cleared, but points are only credited after payment is
        // confirmed by a provider.
        $this->assertDatabaseCount('cart_items', 0);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'points_balance' => 0]);
        $this->assertDatabaseHas('order_items', ['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 1000]);
    }

    public function test_placing_an_order_with_an_empty_cart_fails(): void
    {
        $user = User::factory()->create();
        $address = Address::factory()->for($user)->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/orders', [
            'address_id' => $address->id,
            'payment_method' => 'bakong_khqr',
            'shipping_method' => 'dhl',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('cart');
    }

    public function test_user_cannot_place_an_order_using_another_users_address(): void
    {
        $user = User::factory()->create();
        $otherUsersAddress = Address::factory()->create();
        Product::factory()->create();
        CartItem::factory()->for($user)->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/orders', [
            'address_id' => $otherUsersAddress->id,
            'payment_method' => 'bakong_khqr',
            'shipping_method' => 'dhl',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('address_id');
    }

    public function test_user_can_list_their_orders(): void
    {
        $user = User::factory()->create();
        Order::factory()->for($user)->count(2)->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/orders');

        $response->assertOk()->assertJsonCount(2, 'data');
    }
}
