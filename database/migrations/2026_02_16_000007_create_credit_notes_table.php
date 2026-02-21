<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('credit_notes')) { return; }

        Schema::create('credit_notes', function (Blueprint $table) {
            $table->id();
            $table->string('credit_note_number')->unique();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('refund_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('merchant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customer_name');
            $table->string('customer_email')->nullable();
            $table->string('merchant_name')->nullable();
            $table->string('merchant_trn')->nullable();
            $table->string('type'); // cancellation, full_refund, partial_refund
            $table->decimal('subtotal', 12, 2);
            $table->decimal('tax_rate', 5, 2)->default(5.00);
            $table->decimal('tax_amount', 12, 2);
            $table->decimal('total_amount', 12, 2);
            $table->string('currency', 3)->default('AED');
            $table->text('reason')->nullable();
            $table->string('pdf_path')->nullable();
            $table->string('status')->default('draft'); // draft, issued, voided
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_notes');
    }
};
