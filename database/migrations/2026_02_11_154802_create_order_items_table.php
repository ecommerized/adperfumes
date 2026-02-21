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
        if (Schema::hasTable('order_items')) { return; }

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained()->onDelete('set null');

            // Product Information (snapshot at time of purchase)
            $table->string('product_name');
            $table->string('product_slug');
            $table->string('brand_name')->nullable();
            $table->string('product_image')->nullable();

            // Pricing
            $table->decimal('price', 10, 2); // Price per unit at time of purchase
            $table->integer('quantity');
            $table->decimal('subtotal', 10, 2); // price * quantity

            $table->timestamps();

            // Indexes
            $table->index('order_id');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
