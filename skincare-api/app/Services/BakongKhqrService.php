<?php

namespace App\Services;

use App\Models\Order;
use Exception;
use Illuminate\Support\Facades\Log;
use KHQR\BakongKHQR;
use KHQR\Helpers\KHQRData;
use KHQR\Models\IndividualInfo;

class BakongKhqrService
{
    /**
     * Generate a KHQR code for an order's total. Returns the raw EMV QR
     * payload (rendered into a scannable code on the client) plus its MD5,
     * which is what Bakong's transaction lookup is keyed on.
     *
     * @return array{qr: string, md5: string}
     */
    public function generate(Order $order): array
    {
        $merchant = new IndividualInfo(
            bakongAccountID: config('services.bakong.account_id'),
            merchantName: config('services.bakong.merchant_name'),
            merchantCity: config('services.bakong.merchant_city'),
            currency: KHQRData::CURRENCY_USD,
            amount: (float) $order->total,
            billNumber: $order->order_number,
            storeLabel: config('app.name'),
            purposeOfTransaction: "Order {$order->order_number}",
        );

        $response = BakongKHQR::generateIndividual($merchant);

        return [
            'qr' => $response->data['qr'],
            'md5' => $response->data['md5'],
        ];
    }

    /**
     * Ask Bakong whether a transaction for this MD5 has settled. Bakong's
     * lookup API responds with a non-200 status (which the client library
     * turns into a thrown KHQRException) while the customer hasn't paid
     * yet — that's the expected, common case during polling, not an
     * error, so it's treated the same as an unpaid result.
     */
    public function isPaid(string $md5): bool
    {
        try {
            $bakong = new BakongKHQR(config('services.bakong.token'));
            $result = $bakong->checkTransactionByMD5($md5);
        } catch (Exception $e) {
            Log::info('Bakong KHQR transaction not yet confirmed', ['md5' => $md5, 'error' => $e->getMessage()]);

            return false;
        }

        return (int) ($result['responseCode'] ?? 1) === 0 && ! empty($result['data']);
    }
}
