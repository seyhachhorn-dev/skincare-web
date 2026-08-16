<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\StripePaymentService;
use App\Services\StripeWebhookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

class StripeCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_creates_a_pending_card_order_and_returns_payment_sheet_data(): void
    {
        $user = User::factory()->create();
        $address = Address::factory()->for($user)->create();
        $product = Product::factory()->create(['price' => 24]);
        CartItem::factory()->for($user)->for($product)->create(['quantity' => 2]);
        Sanctum::actingAs($user);

        $this->mock(StripePaymentService::class, function ($mock) {
            $mock->shouldReceive('publishableKey')->once()->andReturn('pk_test_123');
            $mock->shouldReceive('createPaymentIntent')
                ->once()
                ->with(Mockery::on(fn (Order $order) => $order->total === 48 && $order->payment_method === 'card'))
                ->andReturn([
                    'id' => 'pi_test_123',
                    'client_secret' => 'pi_test_123_secret_456',
                    'amount' => 4800,
                    'currency' => 'usd',
                ]);
        });

        $response = $this->postJson('/api/checkout', [
            'address_id' => $address->id,
            'shipping_method' => 'dhl',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.client_secret', 'pi_test_123_secret_456')
            ->assertJsonPath('data.amount', 4800)
            ->assertJsonPath('data.currency', 'usd')
            ->assertJsonPath('data.publishable_key', 'pk_test_123');

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'total' => 48,
            'payment_method' => 'card',
            'payment_status' => 'pending',
            'status' => Order::STATUS_AWAITING_PAYMENT,
            'stripe_payment_intent_id' => 'pi_test_123',
        ]);
        $this->assertDatabaseCount('cart_items', 0);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'points_balance' => 0]);
    }

    public function test_card_checkout_requires_a_shipping_address_owned_by_the_user(): void
    {
        $user = User::factory()->create();
        $otherAddress = Address::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/checkout', [
            'address_id' => $otherAddress->id,
            'shipping_method' => 'dhl',
        ])->assertStatus(422)->assertJsonValidationErrors('address_id');
    }

    public function test_card_checkout_rejects_a_zero_total_before_calling_stripe(): void
    {
        $user = User::factory()->create();
        $address = Address::factory()->for($user)->create();
        $product = Product::factory()->create(['price' => 0]);
        CartItem::factory()->for($user)->for($product)->create();
        Sanctum::actingAs($user);

        $this->mock(StripePaymentService::class, function ($mock) {
            $mock->shouldReceive('publishableKey')->once()->andReturn('pk_test_123');
            $mock->shouldNotReceive('createPaymentIntent');
        });

        $this->postJson('/api/checkout', [
            'address_id' => $address->id,
            'shipping_method' => 'dhl',
        ])->assertStatus(422)->assertJsonValidationErrors('cart');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('cart_items', 1);
    }

    public function test_customer_can_cancel_a_pending_card_payment_and_restore_the_cart(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $order = Order::factory()->for($user)->create([
            'status' => Order::STATUS_AWAITING_PAYMENT,
            'payment_method' => 'card',
            'payment_status' => 'pending',
            'stripe_payment_intent_id' => 'pi_test_123',
        ]);
        $order->items()->create(['product_id' => $product->id, 'quantity' => 2, 'unit_price' => $product->price]);
        Sanctum::actingAs($user);

        $this->mock(StripePaymentService::class, function ($mock) {
            $mock->shouldReceive('cancelPaymentIntent')->once()->with('pi_test_123');
        });

        $this->postJson("/api/checkout/{$order->id}/cancel")
            ->assertOk()
            ->assertJsonPath('data.status', Order::STATUS_CANCELLED);

        $this->assertDatabaseHas('cart_items', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
    }

    public function test_verified_stripe_webhook_marks_the_matching_order_paid_once(): void
    {
        $user = User::factory()->create(['points_balance' => 0]);
        $order = Order::factory()->for($user)->create([
            'status' => Order::STATUS_AWAITING_PAYMENT,
            'total' => 24,
            'points_earned' => 2,
            'payment_method' => 'card',
            'payment_status' => 'pending',
            'stripe_payment_intent_id' => 'pi_test_123',
        ]);

        $this->mock(StripeWebhookService::class, function ($mock) {
            $mock->shouldReceive('parse')->twice()->andReturn([
                'type' => 'payment_intent.succeeded',
                'payment_intent_id' => 'pi_test_123',
                'amount' => 2400,
                'currency' => 'usd',
            ]);
        });
        $this->mock(StripePaymentService::class, function ($mock) {
            $mock->shouldReceive('currency')->twice()->andReturn('usd');
        });

        $this->postJson('/api/stripe/webhook', [], ['Stripe-Signature' => 'test-signature'])
            ->assertOk()
            ->assertJsonPath('received', true);
        $this->postJson('/api/stripe/webhook', [], ['Stripe-Signature' => 'test-signature'])
            ->assertOk();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'payment_status' => 'paid',
            'status' => Order::STATUS_PROCESSING,
        ]);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'points_balance' => 2]);
    }
}
