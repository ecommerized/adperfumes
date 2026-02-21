<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('refunds')) { return; }

        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->string('refund_number')->unique();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('merchant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type'); // full, partial, exchange
            $table->string('reason_category')->nullable();
            $table->text('reason_details')->nullable();

            // Financials
            $table->decimal('refund_subtotal', 12, 2)->default(0);
            $table->decimal('refund_tax', 12, 2)->default(0);
            $table->decimal('refund_total', 12, 2)->default(0);

            // Commission reversal
            $table->decimal('commission_reversed', 12, 2)->default(0);
            $table->decimal('commission_tax_reversed', 12, 2)->default(0);
            $table->decimal('total_commission_reversed', 12, 2)->default(0);

            // Merchant recovery (for post-settlement refunds)
            $table->decimal('merchant_recovery_amount', 12, 2)->default(0);
            $table->boolean('is_settled')->default(false);
            $table->string('recovery_method')->nullable(); // deduct_next_settlement, direct_repayment, not_applicable
            $table->foreignId('recovery_settlement_id')->nullable()
                ->constrained('settlements')->nullOnDelete();
            $table->boolean('is_recovery_completed')->default(false);

            // Status & workflow
            $table->string('status')->default('pending'); // pending, approved, processing, completed, rejected, recovery_pending, fully_resolved
            $table->foreignId('initiated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('transaction_reference')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['order_id', 'status']);
            $table->index(['merchant_id', 'status']);
            $table->index(['recovery_method', 'is_recovery_completed']);
        });

        Schema::create('refund_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('refund_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');
            $table->integer('quantity_refunded');
            $table->decimal('unit_price_incl_tax', 12, 2);
            $table->decimal('line_refund_excl_tax', 12, 2);
            $table->decimal('line_refund_tax', 12, 2);
            $table->decimal('line_refund_total', 12, 2);
            $table->string('item_condition')->nullable(); // sealed, unopened, opened_defective, damaged_in_transit, wrong_item
            $table->boolean('stock_restored')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refund_items');
        Schema::dropIfExists('refunds');
    }
};
