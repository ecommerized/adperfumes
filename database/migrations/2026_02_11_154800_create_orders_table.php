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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();

            // Customer Information
            $table->string('email');
            $table->string('phone');
            $table->string('first_name');
            $table->string('last_name');

            // Shipping Address
            $table->string('address');
            $table->string('city');
            $table->string('country');
            $table->string('postal_code')->nullable();

            // Order Totals
            $table->decimal('subtotal', 10, 2);
            $table->decimal('shipping', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('grand_total', 10, 2);
            $table->string('currency', 3)->default('AED');

            // Discount Information
            $table->string('discount_code')->nullable();

            // Payment Information
            $table->string('payment_method')->nullable(); // tap, tabby, tamara
            $table->string('payment_status')->default('pending'); // pending, paid, failed, refunded
            $table->string('payment_id')->nullable(); // Gateway transaction ID
            $table->text('payment_response')->nullable(); // JSON response from gateway

            // Shipping Information
            $table->string('shipping_method')->default('aramex');
            $table->string('tracking_number')->nullable();
            $table->string('aramex_shipment_id')->nullable();

            // Order Status
            $table->enum('status', ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'])
                  ->default('pending');

            // Notes
            $table->text('customer_notes')->nullable();
            $table->text('admin_notes')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('order_number');
            $table->index('email');
            $table->index('payment_status');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
