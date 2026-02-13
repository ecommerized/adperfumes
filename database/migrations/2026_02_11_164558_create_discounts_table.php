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
        Schema::create('discounts', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('description')->nullable();

            // Discount Type and Value
            $table->enum('type', ['percentage', 'fixed'])->default('percentage');
            $table->decimal('value', 10, 2); // Percentage (e.g., 10 = 10%) or Fixed amount (e.g., 50 = AED 50)

            // Usage Limits
            $table->integer('max_uses')->nullable(); // NULL = unlimited
            $table->integer('max_uses_per_user')->default(1);
            $table->integer('current_uses')->default(0);

            // Minimum Requirements
            $table->decimal('min_purchase_amount', 10, 2)->nullable(); // Minimum cart value

            // Validity Period
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            // Status
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Indexes
            $table->index('code');
            $table->index('is_active');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discounts');
    }
};
