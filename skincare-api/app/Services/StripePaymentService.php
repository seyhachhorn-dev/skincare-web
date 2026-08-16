<?php

namespace App\Services;

use App\Models\Order;
use LogicException;
use Stripe\StripeClient;

class StripePaymentService
{
    /**
     * Stripe receives the smallest currency unit. Product prices are whole
     * USD amounts throughout this application, so $24 becomes 2400 cents.
     * Prices always come from the server-side cart/order, never Flutter.
     *
     * @return array{id: string, client_secret: string, amount: int, currency: string}
     */
    public function createPaymentIntent(Order $order): array
    {
        $currency = $this->currency();
        $amount = $order->total * 100;

        $intent = $this->client()->paymentIntents->create([
            'amount' => $amount,
            'currency' => $currency,
            'automatic_payment_methods' => ['enabled' => true],
            'metadata' => [
                'order_id' => (string) $order->id,
                'user_id' => (string) $order->user_id,
            ],
        ], [
            'idempotency_key' => "checkout_order_{$order->id}",
        ]);

        return [
            'id' => $intent->id,
            'client_secret' => $intent->client_secret,
            'amount' => $intent->amount,
            'currency' => $intent->currency,
        ];
    }

    public function cancelPaymentIntent(string $paymentIntentId): void
    {
        $this->client()->paymentIntents->cancel($paymentIntentId);
    }

    public function publishableKey(): string
    {
        return $this->requiredConfig('publishable_key');
    }

    public function currency(): string
    {
        return strtolower((string) config('services.stripe.currency', 'usd'));
    }

    private function client(): StripeClient
    {
        return new StripeClient($this->requiredConfig('secret'));
    }

    private function requiredConfig(string $key): string
    {
        $value = config("services.stripe.{$key}");

        if (! is_string($value) || trim($value) === '') {
            throw new LogicException("Stripe {$key} is not configured.");
        }

        return $value;
    }
}
