<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('address_id')->nullable()->constrained()->nullOnDelete();
            $table->string('order_number')->unique();
            $table->enum('status', ['processing', 'delivered', 'cancelled'])->default('processing');
            $table->unsignedInteger('total');
            $table->unsignedInteger('points_earned')->default(0);
            $table->enum('payment_method', ['apple_pay', 'paypal']);
            $table->enum('shipping_method', ['dhl', 'inpost']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
