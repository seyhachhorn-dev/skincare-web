<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateStripeCheckoutRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\StripePaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use LogicException;
use Stripe\Exception\ApiErrorException;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly OrderService $orders,
        private readonly StripePaymentService $stripe,
    ) {}

    /**
     * Create a pending card order from the authenticated user's cart and
     * return the PaymentIntent secret required by Flutter PaymentSheet.
     */
    public function store(CreateStripeCheckoutRequest $request): JsonResponse
    {
        try {
            $publishableKey = $this->stripe->publishableKey();
            $checkout = $this->orders->startStripeCheckout(
                $request->user(),
                $request->validated('address_id'),
                $request->validated('shipping_method'),
                $this->stripe,
            );
        } catch (LogicException) {
            return $this->respond(null, 'Stripe is not configured on the server.', 503);
        } catch (ApiErrorException $exception) {
            Log::warning('Stripe PaymentIntent creation failed.', [
                'exception' => $exception::class,
                'http_status' => $exception->getHttpStatus(),
                'stripe_type' => $exception->getError()?->type,
                'stripe_code' => $exception->getError()?->code,
                'message' => $exception->getMessage(),
            ]);

            return $this->respond(null, 'Unable to start card payment. Please try again.', 502);
        }

        /** @var Order $order */
        $order = $checkout['order'];
        $intent = $checkout['payment_intent'];

        return $this->respond([
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'client_secret' => $intent['client_secret'],
            'amount' => $intent['amount'],
            'currency' => $intent['currency'],
            'publishable_key' => $publishableKey,
            'merchant_display_name' => config('services.stripe.merchant_display_name'),
        ], 'Payment sheet initialized successfully', 201);
    }

    /**
     * Called if the customer closes the PaymentSheet before paying. The
     * PaymentIntent is cancelled first; only then are items restored to cart.
     */
    public function cancel(Order $order): JsonResponse
    {
        $this->authorize('view', $order);

        if (
            $order->payment_method !== 'card'
            || $order->payment_status !== 'pending'
            || ! $order->stripe_payment_intent_id
        ) {
            return $this->respond(null, 'This card payment cannot be cancelled.', 422);
        }

        try {
            $this->stripe->cancelPaymentIntent($order->stripe_payment_intent_id);
        } catch (LogicException) {
            return $this->respond(null, 'Stripe is not configured on the server.', 503);
        } catch (ApiErrorException) {
            return $this->respond(null, 'This card payment can no longer be cancelled.', 422);
        }

        $order = $this->orders->cancelPendingOrder($order);

        return $this->respond(
            new OrderResource($order->load(['items.product', 'address'])),
            'Card payment cancelled and items returned to your cart',
        );
    }
}
