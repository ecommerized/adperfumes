<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add type column to product_notes pivot
        Schema::table('product_notes', function (Blueprint $table) {
            $table->enum('type', ['top', 'middle', 'base'])->default('top')->after('note_id');
        });

        // 2. Drop composite primary key, add new one with type
        Schema::table('product_notes', function (Blueprint $table) {
            $table->dropPrimary(['product_id', 'note_id']);
            $table->primary(['product_id', 'note_id', 'type']);
        });

        // 3. Copy type from notes table to pivot table
        DB::statement('UPDATE product_notes pn JOIN notes n ON pn.note_id = n.id SET pn.type = n.type');

        // 4. Remove unique constraint on notes.name and drop type column
        Schema::table('notes', function (Blueprint $table) {
            $table->dropUnique(['name']);
            $table->dropIndex(['type', 'name']);
            $table->dropColumn('type');
            $table->unique('name');
        });
    }

    public function down(): void
    {
        // Re-add type to notes
        Schema::table('notes', function (Blueprint $table) {
            $table->dropUnique(['name']);
            $table->enum('type', ['top', 'middle', 'base'])->default('top')->after('name');
            $table->unique('name');
            $table->index(['type', 'name']);
        });

        // Copy type back from pivot
        DB::statement('UPDATE notes n JOIN product_notes pn ON n.id = pn.note_id SET n.type = pn.type');

        // Remove type from pivot
        Schema::table('product_notes', function (Blueprint $table) {
            $table->dropPrimary(['product_id', 'note_id', 'type']);
            $table->primary(['product_id', 'note_id']);
            $table->dropColumn('type');
        });
    }
};
