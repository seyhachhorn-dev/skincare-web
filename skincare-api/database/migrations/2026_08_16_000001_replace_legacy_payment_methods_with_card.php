<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A string lets Stripe remain an implementation detail while the
        // public API exposes the stable payment method name, "card".
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_method', 32)->change();
        });
    }

    public function down(): void
    {
        // The original enum cannot store card. This mapping exists only to
        // make a technical rollback possible; historical labels stay intact
        // during the normal forward migration.
        DB::table('orders')
            ->where('payment_method', 'card')
            ->update(['payment_method' => 'apple_pay']);

        Schema::table('orders', function (Blueprint $table) {
            $table->enum('payment_method', ['apple_pay', 'paypal', 'bakong_khqr'])->change();
        });
    }
};
