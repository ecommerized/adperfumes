<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * UAE VAT & Tax Integration - Module 1: Expense Management System
     *
     * This migration creates comprehensive expense tracking for:
     * - Corporate tax deductions (9% on net profit)
     * - Input VAT reclaim (5% on eligible expenses)
     * - FTA audit compliance
     * - Operational cost tracking
     */
    public function up(): void
    {
        if (Schema::hasTable('vat_returns')) { return; }

        // 1. VAT Returns Table (created first - referenced by expenses)
        Schema::create('vat_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_number', 50)->unique(); // VAT-2026-Q1-001

            // Period
            $table->date('period_start');
            $table->date('period_end');
            $table->enum('period_type', ['monthly', 'quarterly'])->default('quarterly');
            $table->integer('year');
            $table->integer('quarter')->nullable(); // 1, 2, 3, 4
            $table->integer('month')->nullable(); // 1-12

            // Output VAT (Sales)
            $table->decimal('total_sales_excl_vat', 15, 2)->default(0);
            $table->decimal('output_vat_rate', 5, 2)->default(5.00);
            $table->decimal('output_vat_amount', 15, 2)->default(0);

            // Zero-rated & Exempt Sales
            $table->decimal('zero_rated_sales', 15, 2)->default(0);
            $table->decimal('exempt_sales', 15, 2)->default(0);

            // Input VAT (Purchases/Expenses)
            $table->decimal('total_purchases_excl_vat', 15, 2)->default(0);
            $table->decimal('input_vat_amount', 15, 2)->default(0);
            $table->decimal('input_vat_reclaimable', 15, 2)->default(0); // May be less than input_vat_amount

            // Net VAT
            $table->decimal('net_vat_payable', 15, 2)->default(0); // Output VAT - Input VAT
            // If negative, it's a VAT refund due from FTA

            // Adjustments
            $table->decimal('adjustments', 12, 2)->default(0);
            $table->text('adjustment_notes')->nullable();

            // Filing Details
            $table->enum('status', [
                'draft',           // Being prepared
                'pending_review',  // Ready for review
                'approved',        // Approved internally
                'filed',           // Submitted to FTA
                'paid',            // VAT paid (if payable)
                'refund_requested', // Refund claimed (if negative)
                'refund_received', // Refund received
                'amended'          // Amended return filed
            ])->default('draft');

            $table->date('filing_deadline'); // FTA deadline (28 days after period end)
            $table->date('filed_at')->nullable();
            $table->string('fta_reference', 100)->nullable(); // FTA submission reference

            // Payment/Refund
            $table->date('payment_due_date')->nullable();
            $table->date('paid_at')->nullable();
            $table->string('payment_reference', 100)->nullable();

            // Amendment tracking
            $table->foreignId('original_return_id')->nullable()->constrained('vat_returns')->onDelete('set null');
            $table->boolean('is_amendment')->default(false);
            $table->text('amendment_reason')->nullable();

            // Users
            $table->foreignId('prepared_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();

            // PDF Storage
            $table->string('pdf_path', 500)->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['year', 'quarter', 'month']);
            $table->index(['status', 'filing_deadline']);
            $table->index('period_start');
        });

        // 2. Expense Categories Table
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100); // Utilities, Rent, Salaries, Marketing, etc.
            $table->string('slug', 100)->unique();
            $table->string('code', 20)->unique(); // EXP-001, EXP-002, etc.
            $table->text('description')->nullable();
            $table->string('icon', 50)->nullable(); // For UI
            $table->string('color', 20)->nullable(); // For UI

            // Tax Configuration
            $table->boolean('is_vat_reclaimable')->default(true); // Can reclaim input VAT?
            $table->boolean('is_tax_deductible')->default(true); // Deductible for corporate tax?
            $table->decimal('default_vat_rate', 5, 2)->default(5.00); // UAE VAT 5%

            // Categorization
            $table->enum('type', [
                'operational', // Day-to-day operations
                'capital',     // Asset purchases (depreciation applicable)
                'administrative', // Admin overhead
                'marketing',   // Marketing & advertising
                'utilities',   // Rent, electricity, internet
                'hr',          // Salaries, benefits, training
                'tax',         // Tax payments (non-reclaimable)
                'other'
            ])->default('operational');

            // Budgeting
            $table->decimal('monthly_budget', 12, 2)->nullable();
            $table->boolean('requires_approval')->default(false);
            $table->decimal('approval_threshold', 12, 2)->nullable(); // Auto-approve below this

            // Hierarchy (for nested categories)
            $table->foreignId('parent_id')->nullable()->constrained('expense_categories')->onDelete('set null');

            // Status
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['is_active', 'type']);
            $table->index('parent_id');
        });

        // 3. Expenses Table (can now reference vat_returns)
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('expense_number', 50)->unique(); // EXP-2026-001234

            // Category & Vendor
            $table->foreignId('expense_category_id')->constrained('expense_categories')->onDelete('restrict');
            $table->string('vendor_name', 200); // Supplier/service provider name
            $table->string('vendor_trn', 50)->nullable(); // Tax Registration Number (for VAT reclaim)

            // Financial Details
            $table->decimal('amount_excl_vat', 12, 2); // Amount excluding VAT
            $table->decimal('vat_rate', 5, 2)->default(5.00); // VAT percentage
            $table->decimal('vat_amount', 12, 2); // Calculated VAT
            $table->decimal('total_amount', 12, 2); // Total including VAT
            $table->string('currency', 3)->default('AED');

            // Payment Information
            $table->enum('payment_method', [
                'bank_transfer',
                'cash',
                'credit_card',
                'debit_card',
                'cheque',
                'online_payment',
                'other'
            ])->nullable();
            $table->string('payment_reference', 100)->nullable(); // Transaction ID, cheque number, etc.
            $table->date('payment_date')->nullable();

            // Invoice/Receipt Details
            $table->string('invoice_number', 100)->nullable(); // Vendor's invoice number
            $table->date('invoice_date')->nullable();
            $table->date('expense_date'); // When expense was incurred

            // Tax Treatment
            $table->boolean('is_vat_reclaimable')->default(true);
            $table->boolean('is_tax_deductible')->default(true);
            $table->boolean('vat_reclaimed')->default(false); // Has VAT been reclaimed?
            $table->date('vat_reclaim_date')->nullable();
            $table->foreignId('vat_return_id')->nullable()->constrained('vat_returns')->onDelete('set null');

            // Description & Notes
            $table->string('title', 255); // Brief description
            $table->text('description')->nullable(); // Detailed notes
            $table->text('internal_notes')->nullable(); // Admin notes (not on receipt)

            // Approval Workflow
            $table->enum('status', [
                'draft',           // Created but not submitted
                'pending_approval', // Awaiting approval
                'approved',        // Approved and processed
                'rejected',        // Rejected by approver
                'paid',            // Payment completed
                'cancelled'        // Cancelled
            ])->default('draft');

            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();

            // Attachments (receipts, invoices, etc.)
            $table->json('attachments')->nullable(); // Array of file paths

            // Recurring Expenses
            $table->boolean('is_recurring')->default(false);
            $table->enum('recurring_frequency', ['daily', 'weekly', 'monthly', 'quarterly', 'yearly'])->nullable();
            $table->foreignId('recurring_parent_id')->nullable()->constrained('expenses')->onDelete('set null');
            $table->date('next_occurrence_date')->nullable();

            // Depreciation (for capital expenses)
            $table->boolean('is_depreciable')->default(false);
            $table->decimal('depreciation_rate', 5, 2)->nullable(); // Annual depreciation %
            $table->integer('useful_life_months')->nullable();
            $table->decimal('depreciated_amount', 12, 2)->default(0);
            $table->decimal('book_value', 12, 2)->nullable();

            // Allocation (cost center, project, etc.)
            $table->foreignId('merchant_id')->nullable()->constrained('merchants')->onDelete('set null'); // If expense is for specific merchant
            $table->string('cost_center', 100)->nullable();
            $table->string('project_code', 100)->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['expense_date', 'status']);
            $table->index(['expense_category_id', 'status']);
            $table->index('vendor_trn');
            $table->index('created_by');
            $table->index('status');
            $table->index('vat_reclaimed');
            $table->index('is_recurring');
        });

        // 4. Expense Attachments Table (separate for better file management)
        Schema::create('expense_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_id')->constrained('expenses')->onDelete('cascade');

            $table->string('file_name', 255);
            $table->string('file_path', 500);
            $table->string('file_type', 50); // pdf, jpg, png, etc.
            $table->unsignedBigInteger('file_size'); // bytes
            $table->string('mime_type', 100);

            $table->enum('attachment_type', [
                'invoice',        // Vendor invoice
                'receipt',        // Payment receipt
                'contract',       // Service contract
                'quote',          // Quotation
                'approval',       // Approval document
                'other'
            ])->default('receipt');

            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);

            $table->foreignId('uploaded_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();

            // Indexes
            $table->index('expense_id');
        });

        // 5. Tax Compliance Calendar Table
        Schema::create('tax_compliance_events', function (Blueprint $table) {
            $table->id();

            $table->string('title', 255);
            $table->text('description')->nullable();

            $table->enum('tax_type', [
                'vat',              // VAT return filing
                'corporate_tax',    // Corporate tax filing
                'withholding_tax',  // Withholding tax submission
                'excise_tax',       // Excise tax (if applicable)
                'customs_duty',     // Customs declarations
                'other'
            ]);

            $table->enum('event_type', [
                'filing_deadline',  // Deadline to file return
                'payment_deadline', // Deadline to pay tax
                'registration',     // Registration requirement
                'audit',            // Tax audit scheduled
                'amendment',        // Amendment deadline
                'penalty',          // Penalty payment
                'reminder'          // General reminder
            ]);

            $table->date('due_date');
            $table->date('reminder_date')->nullable(); // When to send reminder

            // Related Records
            $table->foreignId('vat_return_id')->nullable()->constrained('vat_returns')->onDelete('cascade');
            $table->morphs('related'); // Polymorphic relation to any model

            // Status
            $table->enum('status', [
                'upcoming',     // Not yet due
                'due_soon',     // Within reminder period
                'overdue',      // Past due date
                'completed',    // Action completed
                'cancelled'     // Event cancelled
            ])->default('upcoming');

            $table->date('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->onDelete('set null');

            // Penalties
            $table->boolean('has_penalty')->default(false);
            $table->decimal('penalty_amount', 12, 2)->nullable();
            $table->text('penalty_notes')->nullable();

            // Notifications
            $table->boolean('notification_sent')->default(false);
            $table->timestamp('notification_sent_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['due_date', 'status']);
            $table->index(['tax_type', 'event_type']);
            $table->index('status');
        });

        // 6. Add VAT tracking to orders table (if not exists)
        if (!Schema::hasColumn('orders', 'vat_collected')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->decimal('vat_collected', 12, 2)->default(0)->after('grand_total');
                $table->boolean('is_b2b')->default(false)->after('vat_collected'); // B2B vs B2C
                $table->string('customer_trn', 50)->nullable()->after('is_b2b'); // Customer TRN for B2B
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'vat_collected')) {
                $table->dropColumn(['vat_collected', 'is_b2b', 'customer_trn']);
            }
        });

        Schema::dropIfExists('tax_compliance_events');
        Schema::dropIfExists('vat_returns');
        Schema::dropIfExists('expense_attachments');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('expense_categories');
    }
};
