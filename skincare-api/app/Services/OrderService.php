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
                'status' => 'processing',
                'total' => $total,
                'points_earned' => $pointsEarned,
                'payment_method' => $paymentMethod,
                'shipping_method' => $shippingMethod,
            ]);

            foreach ($cartItems as $item) {
                $order->items()->create([
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->product->price,
                ]);
            }

            $user->increment('points_balance', $pointsEarned);
            $user->cartItems()->delete();

            return $order->load(['items.product', 'address']);
        });
    }

    private function generateOrderNumber(): string
    {
        do {
            $candidate = 'FD'.random_int(10000000, 99999999);
        } while (Order::query()->where('order_number', $candidate)->exists());

        return $candidate;
    }
}
