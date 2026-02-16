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
        // Add payment gateway fee tracking to orders table
        Schema::table('orders', function (Blueprint $table) {
            // Payment method details (captured from payment gateway webhook)
            $table->string('payment_card_type')->nullable()->after('payment_method'); // 'local_visa', 'regional_visa', 'international_visa', 'amex', 'tabby'
            $table->string('payment_card_scheme')->nullable()->after('payment_card_type'); // 'visa', 'mastercard', 'amex'
            $table->string('payment_card_issuer_country')->nullable()->after('payment_card_scheme'); // 'AE', 'SA', 'US', etc.

            // Payment gateway fees (what we pay to Tap/Tabby/Tamara)
            $table->decimal('payment_gateway_percentage', 5, 2)->default(0)->after('grand_total'); // e.g., 2.25
            $table->decimal('payment_gateway_fixed_fee', 8, 2)->default(0)->after('payment_gateway_percentage'); // e.g., 1.00 AED
            $table->decimal('payment_gateway_fee_total', 12, 2)->default(0)->after('payment_gateway_fixed_fee'); // total fee charged

            // Platform fee (Tap's platform fee: 0.25%)
            $table->decimal('platform_fee_percentage', 5, 2)->default(0.25)->after('payment_gateway_fee_total');
            $table->decimal('platform_fee_amount', 12, 2)->default(0)->after('platform_fee_percentage');

            // Net amounts after fees
            $table->decimal('net_amount_after_fees', 12, 2)->default(0)->after('platform_fee_amount'); // grand_total - gateway fees - platform fee
        });

        // Add payment gateway fee tracking to settlements table
        Schema::table('settlements', function (Blueprint $table) {
            // Total fees paid to payment gateway for this settlement period
            $table->decimal('total_payment_gateway_fees', 12, 2)->default(0)->after('total_tax');

            // Total platform fees collected
            $table->decimal('total_platform_fees', 12, 2)->default(0)->after('total_payment_gateway_fees');

            // Update net_payout calculation to include fees
            // Note: net_payout will now be = merchant_payout - payment_gateway_fees - platform_fees - deductions
        });

        // Add payment fee tracking to settlement_items
        Schema::table('settlement_items', function (Blueprint $table) {
            $table->decimal('payment_gateway_fee', 12, 2)->default(0)->after('order_subtotal');
            $table->decimal('platform_fee', 12, 2)->default(0)->after('payment_gateway_fee');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'payment_card_type',
                'payment_card_scheme',
                'payment_card_issuer_country',
                'payment_gateway_percentage',
                'payment_gateway_fixed_fee',
                'payment_gateway_fee_total',
                'platform_fee_percentage',
                'platform_fee_amount',
                'net_amount_after_fees',
            ]);
        });

        Schema::table('settlements', function (Blueprint $table) {
            $table->dropColumn([
                'total_payment_gateway_fees',
                'total_platform_fees',
            ]);
        });

        Schema::table('settlement_items', function (Blueprint $table) {
            $table->dropColumn(['payment_gateway_fee', 'platform_fee']);
        });
    }
};
