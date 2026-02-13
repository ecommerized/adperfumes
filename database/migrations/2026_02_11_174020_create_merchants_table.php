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
        Schema::create('merchants', function (Blueprint $table) {
            $table->id();

            // Authentication
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();

            // Business Information
            $table->string('business_name');
            $table->string('contact_name');
            $table->string('phone');
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->default('UAE');

            // Business Documents
            $table->string('trade_license')->nullable();
            $table->string('tax_registration')->nullable();

            // Approval & Status
            $table->enum('status', ['pending', 'approved', 'rejected', 'suspended'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('approved_at')->nullable();

            // Commission
            $table->decimal('commission_percentage', 5, 2)->default(15.00);

            $table->timestamps();

            $table->index('email');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('merchants');
    }
};
