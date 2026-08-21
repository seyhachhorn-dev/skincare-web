<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    /**
     * Place an order from the user's current cart. The cart (never the
     * client) is the source of truth for line items and prices, so this
     * can't be manipulated by a crafted request.
     */
    public function placeOrder(User $user, int $addressId, string $paymentMethod, string $shippingMethod): Order
    {
        return DB::transaction(function () use ($user, $addressId, $paymentMethod, $shippingMethod) {
            return $this->createPendingOrder($user, $addressId, $paymentMethod, $shippingMethod)
                ->load(['items.product', 'address']);
        });
    }

    /**
     * Atomically creates the card order and its Stripe PaymentIntent. If
     * Stripe rejects the request, the transaction rolls back and the cart is
     * left untouched for the customer to retry.
     *
     * @return array{order: Order, payment_intent: array{id: string, client_secret: string, amount: int, currency: string}}
     */
    public function startStripeCheckout(
        User $user,
        int $addressId,
        string $shippingMethod,
        StripePaymentService $stripe,
    ): array {
        return DB::transaction(function () use ($user, $addressId, $shippingMethod, $stripe) {
            $order = $this->createPendingOrder($user, $addressId, 'card', $shippingMethod);

            if ($order->total < 1) {
                throw ValidationException::withMessages([
                    'cart' => 'Card payments require a cart total of at least $1.00.',
                ]);
            }

            $paymentIntent = $stripe->createPaymentIntent($order);

            $order->update(['stripe_payment_intent_id' => $paymentIntent['id']]);

            return [
                'order' => $order->load(['items.product', 'address']),
                'payment_intent' => $paymentIntent,
            ];
        });
    }

    /**
     * Abandon a KHQR order the customer never paid for: releases the
     * items back into their cart (so nothing is silently lost), and marks
     * the order cancelled. Caller (PaymentController) is responsible for
     * checking the order is actually a still-pending KHQR order first.
     */
    public function cancelPendingKhqrOrder(Order $order): Order
    {
        return $this->cancelPendingOrder($order);
    }

    /**
     * Restores a not-yet-paid order's items after a payment flow is
     * abandoned. The caller is responsible for checking payment-method
     * specific rules before reaching this shared operation.
     */
    public function cancelPendingOrder(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($order->payment_status !== 'pending') {
                throw ValidationException::withMessages([
                    'payment' => 'Only pending orders can be cancelled.',
                ]);
            }

            $order->load(['items', 'user']);

            foreach ($order->items as $item) {
                $cartItem = $order->user->cartItems()->firstOrNew(['product_id' => $item->product_id]);
                $cartItem->quantity = ($cartItem->exists ? $cartItem->quantity : 0) + $item->quantity;
                $cartItem->save();
            }

            $order->update(['status' => Order::STATUS_CANCELLED]);

            return $order;
        });
    }

    /**
     * Called only after a payment provider has verified the charge. It is
     * idempotent, so a retrying provider webhook cannot credit points twice.
     */
    public function markPaymentPaid(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($order->payment_status === 'paid') {
                return $order;
            }

            if ($order->status === Order::STATUS_CANCELLED) {
                throw ValidationException::withMessages([
                    'payment' => 'A cancelled order cannot be paid.',
                ]);
            }

            $order->update([
                'payment_status' => 'paid',
                'status' => Order::STATUS_PROCESSING,
            ]);
            $order->user()->increment('points_balance', $order->points_earned);

            return $order;
        });
    }

    /**
     * Move a paid order through the fulfillment workflow. The allowed
     * transitions deliberately prevent an unpaid, cancelled, or delivered
     * order from being changed accidentally in the admin dashboard.
     */
    public function updateFulfillmentStatus(Order $order, string $status): Order
    {
        return DB::transaction(function () use ($order, $status) {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($order->payment_status !== 'paid') {
                throw ValidationException::withMessages([
                    'status' => 'Only paid orders can be fulfilled.',
                ]);
            }

            $allowedNextStatuses = match ($order->status) {
                Order::STATUS_PROCESSING => [Order::STATUS_SHIPPED, Order::STATUS_DELIVERED, Order::STATUS_CANCELLED],
                Order::STATUS_SHIPPED => [Order::STATUS_DELIVERED, Order::STATUS_CANCELLED],
                default => [],
            };

            if (! in_array($status, $allowedNextStatuses, true)) {
                throw ValidationException::withMessages([
                    'status' => "Order cannot move from {$order->status} to {$status}.",
                ]);
            }

            $order->update(['status' => $status]);

            return $order->load(['user', 'items.product', 'address']);
        });
    }

    /**
     * Requires an open transaction. Locks the cart so product quantities and
     * prices cannot be changed while an order is being created.
     */
    private function createPendingOrder(User $user, int $addressId, string $paymentMethod, string $shippingMethod): Order
    {
        $cartItems = $user->cartItems()->with('product')->lockForUpdate()->get();

        if ($cartItems->isEmpty()) {
            throw ValidationException::withMessages([
                'cart' => 'Your cart is empty.',
            ]);
        }

        $total = $cartItems->sum(fn ($item) => $item->quantity * $item->product->price);
        $pointsEarned = (int) round($total * 0.1);

        $order = $user->orders()->create([
            'address_id' => $addressId,
            'order_number' => $this->generateOrderNumber(),
            'status' => Order::STATUS_AWAITING_PAYMENT,
            'total' => $total,
            'points_earned' => $pointsEarned,
            'payment_method' => $paymentMethod,
            'payment_status' => 'pending',
            'shipping_method' => $shippingMethod,
        ]);

        foreach ($cartItems as $item) {
            $order->items()->create([
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'unit_price' => $item->product->price,
            ]);
        }

        $user->cartItems()->delete();

        return $order;
    }

    private function generateOrderNumber(): string
    {
        do {
            $candidate = 'FD'.random_int(10000000, 99999999);
        } while (Order::query()->where('order_number', $candidate)->exists());

        return $candidate;
    }
}
