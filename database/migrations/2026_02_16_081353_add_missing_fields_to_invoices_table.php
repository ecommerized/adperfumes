<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Add missing fields used by InvoiceService
            $table->decimal('shipping_amount', 12, 2)->default(0)->after('tax_amount');
            $table->decimal('discount_amount', 12, 2)->default(0)->after('shipping_amount');
            $table->decimal('commission_amount', 12, 2)->default(0)->after('discount_amount');
            $table->decimal('net_merchant_amount', 12, 2)->default(0)->after('commission_amount');

            // Rename total_amount to total for consistency with InvoiceService
            $table->renameColumn('total_amount', 'total');
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            // Rename to match InvoiceService
            $table->renameColumn('unit_price_excl_tax', 'unit_price');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'shipping_amount',
                'discount_amount',
                'commission_amount',
                'net_merchant_amount',
            ]);
            $table->renameColumn('total', 'total_amount');
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->renameColumn('unit_price', 'unit_price_excl_tax');
        });
    }
};
