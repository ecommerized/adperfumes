<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Expand the status enum to include return/refund statuses
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending','confirmed','processing','shipped','delivered','cancelled','return_requested','return_approved','return_rejected','returned','refunded') DEFAULT 'pending'");

        Schema::table('orders', function (Blueprint $table) {
            // Delivery tracking
            $table->timestamp('delivered_at')->nullable()->after('admin_notes');
            $table->timestamp('settlement_eligible_at')->nullable()->after('delivered_at');

            // Cancellation tracking
            $table->timestamp('cancelled_at')->nullable()->after('settlement_eligible_at');
            $table->string('cancellation_reason')->nullable()->after('cancelled_at');
            $table->foreignId('cancelled_by')->nullable()->after('cancellation_reason')
                ->constrained('users')->nullOnDelete();

            // Return tracking
            $table->timestamp('return_requested_at')->nullable()->after('cancelled_by');
            $table->text('return_reason')->nullable()->after('return_requested_at');
            $table->string('return_reason_category')->nullable()->after('return_reason');
            $table->text('return_notes')->nullable()->after('return_reason_category');

            // Refund eligibility
            $table->boolean('is_refund_eligible')->default(false)->after('return_notes');

            // Indexes for settlement queries
            $table->index('delivered_at');
            $table->index('settlement_eligible_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['delivered_at']);
            $table->dropIndex(['settlement_eligible_at']);
            $table->dropForeign(['cancelled_by']);

            $table->dropColumn([
                'delivered_at', 'settlement_eligible_at',
                'cancelled_at', 'cancellation_reason', 'cancelled_by',
                'return_requested_at', 'return_reason', 'return_reason_category', 'return_notes',
                'is_refund_eligible',
            ]);
        });

        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('pending','confirmed','processing','shipped','delivered','cancelled') DEFAULT 'pending'");
    }
};
