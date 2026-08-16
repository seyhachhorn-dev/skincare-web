<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\BakongKhqrService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_generate_a_khqr_code_for_their_pending_order(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create([
            'payment_method' => 'bakong_khqr',
            'payment_status' => 'pending',
        ]);
        Sanctum::actingAs($user);

        $this->mock(BakongKhqrService::class, function ($mock) {
            $mock->shouldReceive('generate')->once()->andReturn(['qr' => 'khqr-string', 'md5' => 'abc123']);
        });

        $response = $this->postJson("/api/orders/{$order->id}/khqr");

        $response->assertOk()->assertJsonPath('data.md5', 'abc123');
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'khqr_md5' => 'abc123']);
    }

    public function test_generating_a_khqr_code_fails_for_a_non_khqr_order(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create(['payment_method' => 'card']);
        Sanctum::actingAs($user);

        $response = $this->postJson("/api/orders/{$order->id}/khqr");

        $response->assertStatus(422);
    }

    public function test_user_cannot_generate_a_khqr_code_for_another_users_order(): void
    {
        $order = Order::factory()->create(['payment_method' => 'bakong_khqr', 'payment_status' => 'pending']);
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson("/api/orders/{$order->id}/khqr");

        $response->assertForbidden();
    }

    public function test_status_check_marks_the_order_paid_once_bakong_confirms_it(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create([
            'payment_method' => 'bakong_khqr',
            'payment_status' => 'pending',
            'khqr_md5' => 'abc123',
            'points_earned' => 20,
        ]);
        Sanctum::actingAs($user);

        $this->mock(BakongKhqrService::class, function ($mock) {
            $mock->shouldReceive('isPaid')->with('abc123')->once()->andReturn(true);
        });

        $response = $this->getJson("/api/orders/{$order->id}/khqr/status");

        $response->assertOk()->assertJsonPath('data.paid', true);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'payment_status' => 'paid',
            'status' => 'processing',
        ]);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'points_balance' => 20]);
    }

    public function test_status_check_reports_unpaid_while_bakong_has_not_seen_the_transaction(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create([
            'payment_method' => 'bakong_khqr',
            'payment_status' => 'pending',
            'khqr_md5' => 'abc123',
        ]);
        Sanctum::actingAs($user);

        $this->mock(BakongKhqrService::class, function ($mock) {
            $mock->shouldReceive('isPaid')->with('abc123')->once()->andReturn(false);
        });

        $response = $this->getJson("/api/orders/{$order->id}/khqr/status");

        $response->assertOk()->assertJsonPath('data.paid', false);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'payment_status' => 'pending']);
    }

    public function test_cancelling_a_pending_khqr_order_restores_its_items_to_the_cart(): void
    {
        $user = User::factory()->create(['points_balance' => 50]);
        $product = Product::factory()->create();
        $order = Order::factory()->for($user)->create([
            'payment_method' => 'bakong_khqr',
            'payment_status' => 'pending',
            'points_earned' => 20,
        ]);
        $order->items()->create(['product_id' => $product->id, 'quantity' => 3, 'unit_price' => $product->price]);
        Sanctum::actingAs($user);

        $response = $this->postJson("/api/orders/{$order->id}/khqr/cancel");

        $response->assertOk()->assertJsonPath('data.status', 'cancelled');
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'cancelled']);
        $this->assertDatabaseHas('cart_items', ['user_id' => $user->id, 'product_id' => $product->id, 'quantity' => 3]);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'points_balance' => 50]);
    }

    public function test_cancelling_a_khqr_order_merges_into_an_existing_cart_item_for_the_same_product(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        CartItem::factory()->for($user)->for($product)->create(['quantity' => 2]);
        $order = Order::factory()->for($user)->create([
            'payment_method' => 'bakong_khqr',
            'payment_status' => 'pending',
        ]);
        $order->items()->create(['product_id' => $product->id, 'quantity' => 3, 'unit_price' => $product->price]);
        Sanctum::actingAs($user);

        $this->postJson("/api/orders/{$order->id}/khqr/cancel")->assertOk();

        $this->assertDatabaseHas('cart_items', ['user_id' => $user->id, 'product_id' => $product->id, 'quantity' => 5]);
    }

    public function test_cannot_cancel_an_order_that_is_not_a_pending_khqr_order(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->create(['payment_method' => 'card']);
        Sanctum::actingAs($user);

        $response = $this->postJson("/api/orders/{$order->id}/khqr/cancel");

        $response->assertStatus(422);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'processing']);
    }

    public function test_user_cannot_cancel_another_users_order(): void
    {
        $order = Order::factory()->create(['payment_method' => 'bakong_khqr', 'payment_status' => 'pending']);
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson("/api/orders/{$order->id}/khqr/cancel");

        $response->assertForbidden();
    }
}
