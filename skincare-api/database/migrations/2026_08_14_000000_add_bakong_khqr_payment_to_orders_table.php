<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('payment_method', ['apple_pay', 'paypal', 'bakong_khqr'])->change();

            // apple_pay/paypal are confirmed by the device's payment sheet
            // before the order ever reaches us, so they default to paid;
            // bakong_khqr is the only method where payment happens after
            // order creation, via a KHQR scan the customer completes later.
            $table->enum('payment_status', ['pending', 'paid'])->default('paid')->after('payment_method');

            // MD5 of the generated KHQR string, used to poll Bakong for
            // this order's transaction status.
            $table->string('khqr_md5')->nullable()->after('payment_status');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['payment_status', 'khqr_md5']);
            $table->enum('payment_method', ['apple_pay', 'paypal'])->change();
        });
    }
};
