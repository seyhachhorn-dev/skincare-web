<?php

namespace App\Services;

use LogicException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class StripeWebhookService
{
    /**
     * Verifies Stripe's signature against the unmodified request body before
     * returning the only fields the application needs to process an event.
     *
     * @return array{type: string, payment_intent_id: ?string, amount: ?int, currency: ?string}
     *
     * @throws SignatureVerificationException
     * @throws \UnexpectedValueException
     */
    public function parse(string $payload, ?string $signature): array
    {
        $webhookSecret = config('services.stripe.webhook_secret');

        if (! is_string($webhookSecret) || trim($webhookSecret) === '') {
            throw new LogicException('Stripe webhook_secret is not configured.');
        }

        $event = Webhook::constructEvent($payload, $signature ?? '', $webhookSecret);
        $paymentIntent = $event->data->object;

        return [
            'type' => $event->type,
            'payment_intent_id' => isset($paymentIntent->id) ? (string) $paymentIntent->id : null,
            'amount' => isset($paymentIntent->amount) ? (int) $paymentIntent->amount : null,
            'currency' => isset($paymentIntent->currency) ? (string) $paymentIntent->currency : null,
        ];
    }
}
