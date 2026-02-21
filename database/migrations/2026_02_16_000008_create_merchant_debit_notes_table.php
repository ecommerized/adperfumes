<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('merchant_debit_notes')) { return; }

        Schema::create('merchant_debit_notes', function (Blueprint $table) {
            $table->id();
            $table->string('debit_note_number')->unique();
            $table->foreignId('refund_id')->constrained()->cascadeOnDelete();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('settlement_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('recovery_settlement_id')->nullable()
                ->constrained('settlements')->nullOnDelete();
            $table->decimal('recovery_amount', 12, 2);
            $table->decimal('commission_reversed', 12, 2)->default(0);
            $table->text('description')->nullable();
            $table->string('pdf_path')->nullable();
            $table->string('status')->default('pending'); // pending, applied, settled
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();

            $table->index(['merchant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant_debit_notes');
    }
};
