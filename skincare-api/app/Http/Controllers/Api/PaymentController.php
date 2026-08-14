<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\BakongKhqrService;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
    public function __construct(private readonly BakongKhqrService $khqr)
    {
    }

    /**
     * Generate (or re-fetch) the KHQR code for an order awaiting Bakong
     * payment. Idempotent — calling it again before payment just returns
     * a fresh code for the same amount, so the Flutter app can safely
     * retry after a dropped connection.
     */
    public function generateKhqr(Order $order): JsonResponse
    {
        $this->authorize('view', $order);

        if ($order->payment_method !== 'bakong_khqr') {
            return $this->respond(null, 'This order is not set up for Bakong KHQR payment.', 422);
        }

        if ($order->payment_status === 'paid') {
            return $this->respond(null, 'This order has already been paid.', 422);
        }

        $code = $this->khqr->generate($order);

        $order->update(['khqr_md5' => $code['md5']]);

        return $this->respond($code, 'KHQR code generated successfully');
    }

    /**
     * Polled by the Flutter app while the KHQR payment screen is open.
     * Flips the order to paid the first time Bakong reports the
     * transaction as settled.
     */
    public function khqrStatus(Order $order): JsonResponse
    {
        $this->authorize('view', $order);

        if ($order->payment_status === 'paid') {
            return $this->respond(['paid' => true], 'Payment already confirmed');
        }

        if (! $order->khqr_md5) {
            return $this->respond(null, 'No KHQR code has been generated for this order yet.', 422);
        }

        $paid = $this->khqr->isPaid($order->khqr_md5);

        if ($paid) {
            $order->update(['payment_status' => 'paid']);
        }

        return $this->respond(['paid' => $paid], $paid ? 'Payment confirmed' : 'Awaiting payment');
    }
}
