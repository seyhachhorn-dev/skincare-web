<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A string keeps future fulfillment additions (for example, a
        // carrier exception) from requiring another database enum change.
        Schema::table('orders', function (Blueprint $table) {
            $table->string('status', 32)->default('processing')->change();
        });
    }

    public function down(): void
    {
        // The former enum has no awaiting_payment/shipped values. Normalize
        // them before restoring it so a rollback remains executable.
        DB::table('orders')
            ->whereIn('status', ['awaiting_payment', 'shipped'])
            ->update(['status' => 'processing']);

        Schema::table('orders', function (Blueprint $table) {
            $table->enum('status', ['processing', 'delivered', 'cancelled'])
                ->default('processing')
                ->change();
        });
    }
};
