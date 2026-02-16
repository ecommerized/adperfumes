<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add FTA-compliant QR code storage to invoices table.
     *
     * The QR code data is stored as Base64-encoded TLV (Tag-Length-Value) format
     * as required by UAE Federal Tax Authority for e-invoicing (ZATCA/FATOORA Phase 2).
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->text('qr_code_data')->nullable()->after('pdf_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('qr_code_data');
        });
    }
};
