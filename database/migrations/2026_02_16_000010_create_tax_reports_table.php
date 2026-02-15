<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_reports', function (Blueprint $table) {
            $table->id();
            $table->string('report_number')->unique();
            $table->string('report_type'); // daily, weekly, monthly, quarterly, yearly, custom
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('total_sales_incl_tax', 14, 2)->default(0);
            $table->decimal('total_sales_excl_tax', 14, 2)->default(0);
            $table->decimal('total_output_vat', 14, 2)->default(0);
            $table->decimal('total_commission_earned', 14, 2)->default(0);
            $table->decimal('total_commission_vat', 14, 2)->default(0);
            $table->decimal('net_vat_payable', 14, 2)->default(0);
            $table->integer('total_orders')->default(0);
            $table->integer('total_merchants')->default(0);
            $table->json('merchant_breakdown')->nullable();
            $table->json('category_breakdown')->nullable();
            $table->string('pdf_path')->nullable();
            $table->string('status')->default('draft'); // draft, finalized, filed
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_reports');
    }
};
