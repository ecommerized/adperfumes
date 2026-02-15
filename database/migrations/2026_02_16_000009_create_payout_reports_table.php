<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payout_reports', function (Blueprint $table) {
            $table->id();
            $table->string('report_number')->unique();
            $table->foreignId('settlement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $table->date('payout_date');
            $table->date('period_start');
            $table->date('period_end');
            $table->integer('total_orders')->default(0);
            $table->decimal('gross_revenue', 12, 2)->default(0);
            $table->decimal('total_tax_collected', 12, 2)->default(0);
            $table->decimal('total_commission', 12, 2)->default(0);
            $table->decimal('commission_tax', 12, 2)->default(0);
            $table->decimal('net_payout', 12, 2)->default(0);
            $table->string('pdf_path')->nullable();
            $table->string('status')->default('generated'); // generated, sent, viewed
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['merchant_id', 'payout_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payout_reports');
    }
};
