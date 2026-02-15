<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reconciliations', function (Blueprint $table) {
            $table->id();
            $table->string('reconciliation_number')->unique();
            $table->date('period_start');
            $table->date('period_end');
            $table->unsignedInteger('total_orders')->default(0);
            $table->decimal('total_gmv', 12, 2)->default(0);
            $table->decimal('total_commission_earned', 12, 2)->default(0);
            $table->decimal('total_tax_collected', 12, 2)->default(0);
            $table->decimal('total_refunds_issued', 12, 2)->default(0);
            $table->decimal('total_settlements_paid', 12, 2)->default(0);
            $table->decimal('total_debit_notes', 12, 2)->default(0);
            $table->decimal('net_platform_revenue', 12, 2)->default(0);
            $table->decimal('discrepancy_amount', 12, 2)->default(0);
            $table->text('discrepancy_notes')->nullable();
            $table->string('status')->default('draft');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('pdf_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reconciliations');
    }
};
