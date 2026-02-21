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
        if (Schema::hasTable('product_accords')) { return; }

        Schema::create('product_accords', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('accord_id')->constrained()->onDelete('cascade');
            $table->integer('percentage')->nullable()->comment('Accord intensity 0-100');

            $table->primary(['product_id', 'accord_id']);
            $table->index('accord_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_accords');
    }
};
