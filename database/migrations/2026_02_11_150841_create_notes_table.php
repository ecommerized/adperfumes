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
        if (Schema::hasTable('notes')) { return; }

        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->enum('type', ['top', 'middle', 'base']);
            $table->timestamps();

            $table->index(['type', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
};
