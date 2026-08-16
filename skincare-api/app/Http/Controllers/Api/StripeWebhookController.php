<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\StripePaymentService;
use App\Services\StripeWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use LogicException;
use Stripe\Exception\SignatureVerificationException;

class StripeWebhookController extends Controller
{
    public function __construct(
        private readonly OrderService $orders,
        private readonly StripePaymentService $stripe,
        private readonly StripeWebhookService $webhooks,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $event = $this->webhooks->parse(
                $request->getContent(),
                $request->header('Stripe-Signature'),
            );
        } catch (LogicException) {
            return response()->json(['message' => 'Stripe webhook is not configured.'], 503);
        } catch (\UnexpectedValueException|SignatureVerificationException) {
            return response()->json(['message' => 'Invalid Stripe webhook signature.'], 400);
        }

        if ($event['type'] !== 'payment_intent.succeeded' || ! $event['payment_intent_id']) {
            return response()->json(['received' => true]);
        }

        $order = Order::query()
            ->where('stripe_payment_intent_id', $event['payment_intent_id'])
            ->first();

        // A successful payment for an order that was deliberately cancelled
        // is retained for reconciliation; it is never resurrected here.
        if (! $order || $order->status === Order::STATUS_CANCELLED) {
            return response()->json(['received' => true]);
        }

        if (
            $event['amount'] !== $order->total * 100
            || strtolower((string) $event['currency']) !== $this->stripe->currency()
        ) {
            Log::warning('Stripe PaymentIntent did not match its order.', [
                'order_id' => $order->id,
                'payment_intent_id' => $event['payment_intent_id'],
            ]);

            return response()->json(['message' => 'Stripe payment does not match order.'], 422);
        }

        $this->orders->markPaymentPaid($order);

        return response()->json(['received' => true]);
    }
}
