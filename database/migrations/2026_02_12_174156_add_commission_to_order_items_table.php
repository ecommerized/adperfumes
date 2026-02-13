<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('merchant_id')->nullable()->after('product_id')->constrained()->nullOnDelete();
            $table->decimal('commission_rate', 5, 2)->nullable()->after('subtotal');
            $table->decimal('commission_amount', 10, 2)->default(0)->after('commission_rate');

            $table->index('merchant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['merchant_id']);
            $table->dropColumn(['merchant_id', 'commission_rate', 'commission_amount']);
        });
    }
};
