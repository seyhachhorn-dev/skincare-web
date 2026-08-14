<?php

namespace Tests\Feature;

use App\Models\Order;
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
        $order = Order::factory()->for($user)->create(['payment_method' => 'apple_pay']);
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
        ]);
        Sanctum::actingAs($user);

        $this->mock(BakongKhqrService::class, function ($mock) {
            $mock->shouldReceive('isPaid')->with('abc123')->once()->andReturn(true);
        });

        $response = $this->getJson("/api/orders/{$order->id}/khqr/status");

        $response->assertOk()->assertJsonPath('data.paid', true);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'payment_status' => 'paid']);
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
}
