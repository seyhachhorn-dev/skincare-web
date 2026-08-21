<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminOrderManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->role = 'admin';
        $admin->save();

        return $admin;
    }

    public function test_admin_can_list_orders_for_the_dashboard(): void
    {
        $customer = User::factory()->create(['name' => 'Ava Anderson']);
        $order = Order::factory()->for($customer)->create();
        Sanctum::actingAs($this->admin());

        $response = $this->getJson('/api/admin/orders');

        $response->assertOk()
            ->assertJsonPath('data.0.id', $order->id)
            ->assertJsonPath('data.0.customer.name', 'Ava Anderson');
    }

    public function test_admin_can_move_a_paid_order_from_processing_to_shipped_and_delivered(): void
    {
        $order = Order::factory()->create([
            'status' => Order::STATUS_PROCESSING,
            'payment_status' => 'paid',
        ]);
        Sanctum::actingAs($this->admin());

        $this->patchJson("/api/admin/orders/{$order->id}/status", ['status' => Order::STATUS_SHIPPED])
            ->assertOk()
            ->assertJsonPath('data.status', Order::STATUS_SHIPPED);

        $this->patchJson("/api/admin/orders/{$order->id}/status", ['status' => Order::STATUS_DELIVERED])
            ->assertOk()
            ->assertJsonPath('data.status', Order::STATUS_DELIVERED);

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => Order::STATUS_DELIVERED]);
    }

    public function test_admin_can_cancel_a_paid_order_that_has_not_shipped(): void
    {
        $order = Order::factory()->create([
            'status' => Order::STATUS_PROCESSING,
            'payment_status' => 'paid',
        ]);
        Sanctum::actingAs($this->admin());

        $this->patchJson("/api/admin/orders/{$order->id}/status", ['status' => Order::STATUS_CANCELLED])
            ->assertOk()
            ->assertJsonPath('data.status', Order::STATUS_CANCELLED);

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => Order::STATUS_CANCELLED]);
    }

    public function test_admin_cannot_move_a_delivered_order_to_cancelled(): void
    {
        $order = Order::factory()->create([
            'status' => Order::STATUS_DELIVERED,
            'payment_status' => 'paid',
        ]);
        Sanctum::actingAs($this->admin());

        $this->patchJson("/api/admin/orders/{$order->id}/status", ['status' => Order::STATUS_CANCELLED])
            ->assertStatus(422)
            ->assertJsonValidationErrors('status');

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => Order::STATUS_DELIVERED]);
    }

    public function test_admin_cannot_fulfill_an_unpaid_order(): void
    {
        $order = Order::factory()->create([
            'status' => Order::STATUS_AWAITING_PAYMENT,
            'payment_method' => 'bakong_khqr',
            'payment_status' => 'pending',
        ]);
        Sanctum::actingAs($this->admin());

        $this->patchJson("/api/admin/orders/{$order->id}/status", ['status' => Order::STATUS_SHIPPED])
            ->assertStatus(422)
            ->assertJsonValidationErrors('status');

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => Order::STATUS_AWAITING_PAYMENT]);
    }

    public function test_regular_user_cannot_access_admin_order_management(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/admin/orders')->assertForbidden();
    }
}
