<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('settlements')) { return; }

        Schema::create('settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->date('payout_date');
            $table->decimal('total_order_amount', 12, 2)->default(0);
            $table->decimal('total_subtotal', 12, 2)->default(0);
            $table->decimal('total_tax', 12, 2)->default(0);
            $table->decimal('commission_amount', 12, 2)->default(0);
            $table->decimal('commission_tax', 12, 2)->default(0);
            $table->decimal('total_commission', 12, 2)->default(0);
            $table->decimal('merchant_payout', 12, 2)->default(0);
            $table->decimal('deductions', 12, 2)->default(0); // refund recoveries
            $table->decimal('net_payout', 12, 2)->default(0);
            $table->string('status')->default('pending'); // pending, processing, paid, failed
            $table->string('transaction_reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['merchant_id', 'status']);
            $table->index('payout_date');
        });

        Schema::create('settlement_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('settlement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->decimal('order_total', 12, 2);
            $table->decimal('order_subtotal', 12, 2);
            $table->decimal('commission_rate_applied', 5, 2);
            $table->string('commission_source')->nullable();
            $table->decimal('commission_amount', 12, 2);
            $table->decimal('commission_tax', 12, 2)->default(0);
            $table->decimal('merchant_payout', 12, 2);
            $table->timestamps();

            $table->unique(['settlement_id', 'order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settlement_items');
        Schema::dropIfExists('settlements');
    }
};
