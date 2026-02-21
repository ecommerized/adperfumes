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
        if (Schema::hasTable('customers')) { return; }

        Schema::create('customers', function (Blueprint $table) {
            $table->id();

            // Contact Information
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('first_name');
            $table->string('last_name');

            // Default Address (from most recent order)
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->string('postal_code')->nullable();

            // Marketing Preferences
            $table->boolean('marketing_email_opt_in')->default(true);
            $table->boolean('marketing_whatsapp_opt_in')->default(true);

            // Analytics
            $table->integer('total_orders')->default(0);
            $table->decimal('total_spent', 10, 2)->default(0);
            $table->timestamp('first_order_at')->nullable();
            $table->timestamp('last_order_at')->nullable();

            // Segmentation
            $table->string('customer_segment')->nullable(); // vip, regular, new, inactive

            $table->timestamps();

            // Indexes
            $table->index('email');
            $table->index('phone');
            $table->index('customer_segment');
            $table->index(['marketing_email_opt_in', 'marketing_whatsapp_opt_in']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
