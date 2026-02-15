<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('merchant_id')->nullable()->constrained()->nullOnDelete();

            // Customer snapshot
            $table->string('customer_name');
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            $table->text('customer_address')->nullable();

            // Merchant snapshot
            $table->string('merchant_name')->nullable();
            $table->string('merchant_trn')->nullable(); // Tax Registration Number

            // Financials
            $table->decimal('subtotal', 12, 2);
            $table->decimal('tax_rate', 5, 2)->default(5.00);
            $table->decimal('tax_amount', 12, 2);
            $table->decimal('total_amount', 12, 2);
            $table->string('currency', 3)->default('AED');

            // Status
            $table->string('status')->default('draft'); // draft, sent, viewed, paid
            $table->string('pdf_path')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->date('due_date')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['order_id', 'merchant_id']);
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->string('sku')->nullable();
            $table->integer('quantity');
            $table->decimal('unit_price_excl_tax', 12, 2);
            $table->decimal('unit_price_incl_tax', 12, 2);
            $table->decimal('tax_rate', 5, 2)->default(5.00);
            $table->decimal('tax_amount', 12, 2);
            $table->decimal('line_total', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
    }
};
