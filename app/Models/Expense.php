<?php

namespace App\Models;

use App\Traits\HasTransactionLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use HasFactory, SoftDeletes, HasTransactionLog;

    protected $fillable = [
        'expense_number',
        'expense_category_id',
        'vendor_name',
        'vendor_trn',
        'amount_excl_vat',
        'vat_rate',
        'vat_amount',
        'total_amount',
        'currency',
        'payment_method',
        'payment_reference',
        'payment_date',
        'invoice_number',
        'invoice_date',
        'expense_date',
        'is_vat_reclaimable',
        'is_tax_deductible',
        'vat_reclaimed',
        'vat_reclaim_date',
        'vat_return_id',
        'title',
        'description',
        'internal_notes',
        'status',
        'created_by',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'attachments',
        'is_recurring',
        'recurring_frequency',
        'recurring_parent_id',
        'next_occurrence_date',
        'is_depreciable',
        'depreciation_rate',
        'useful_life_months',
        'depreciated_amount',
        'book_value',
        'merchant_id',
        'cost_center',
        'project_code',
    ];

    protected $casts = [
        'amount_excl_vat' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'is_vat_reclaimable' => 'boolean',
        'is_tax_deductible' => 'boolean',
        'vat_reclaimed' => 'boolean',
        'vat_reclaim_date' => 'date',
        'payment_date' => 'date',
        'invoice_date' => 'date',
        'expense_date' => 'date',
        'approved_at' => 'datetime',
        'attachments' => 'array',
        'is_recurring' => 'boolean',
        'next_occurrence_date' => 'date',
        'is_depreciable' => 'boolean',
        'depreciation_rate' => 'decimal:2',
        'depreciated_amount' => 'decimal:2',
        'book_value' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($expense) {
            if (!$expense->expense_number) {
                $expense->expense_number = static::generateExpenseNumber();
            }

            // Calculate VAT if not set
            if (!$expense->vat_amount && $expense->amount_excl_vat && $expense->vat_rate) {
                $expense->vat_amount = round($expense->amount_excl_vat * ($expense->vat_rate / 100), 2);
            }

            // Calculate total if not set
            if (!$expense->total_amount && $expense->amount_excl_vat) {
                $expense->total_amount = round($expense->amount_excl_vat + ($expense->vat_amount ?? 0), 2);
            }

            // Set book value for depreciable assets
            if ($expense->is_depreciable && !$expense->book_value) {
                $expense->book_value = $expense->total_amount;
            }
        });
    }

    /**
     * Generate unique expense number.
     */
    public static function generateExpenseNumber(): string
    {
        $year = date('Y');
        $month = date('m');

        $lastExpense = static::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastExpense ? (int) substr($lastExpense->expense_number, -6) + 1 : 1;

        return sprintf('EXP-%s%s-%06d', $year, $month, $sequence);
    }

    /**
     * Get the expense category.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    /**
     * Get the merchant (if expense is allocated to merchant).
     */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    /**
     * Get the user who created this expense.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who approved this expense.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the VAT return this expense is included in.
     */
    public function vatReturn(): BelongsTo
    {
        return $this->belongsTo(VatReturn::class, 'vat_return_id');
    }

    /**
     * Get the parent recurring expense.
     */
    public function recurringParent(): BelongsTo
    {
        return $this->belongsTo(Expense::class, 'recurring_parent_id');
    }

    /**
     * Get child recurring expenses.
     */
    public function recurringChildren(): HasMany
    {
        return $this->hasMany(Expense::class, 'recurring_parent_id');
    }

    /**
     * Get all attachments for this expense.
     */
    public function expenseAttachments(): HasMany
    {
        return $this->hasMany(ExpenseAttachment::class)->orderBy('sort_order');
    }

    /**
     * Scope: Filter by status.
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Filter by date range.
     */
    public function scopeDateRange($query, string $from, string $to)
    {
        return $query->whereBetween('expense_date', [$from, $to]);
    }

    /**
     * Scope: VAT reclaimable expenses only.
     */
    public function scopeVatReclaimable($query)
    {
        return $query->where('is_vat_reclaimable', true)
            ->whereIn('status', ['approved', 'paid']);
    }

    /**
     * Scope: Tax deductible expenses only.
     */
    public function scopeTaxDeductible($query)
    {
        return $query->where('is_tax_deductible', true)
            ->whereIn('status', ['approved', 'paid']);
    }

    /**
     * Scope: Pending approval.
     */
    public function scopePendingApproval($query)
    {
        return $query->where('status', 'pending_approval');
    }

    /**
     * Scope: Approved expenses.
     */
    public function scopeApproved($query)
    {
        return $query->whereIn('status', ['approved', 'paid']);
    }

    /**
     * Scope: Recurring expenses.
     */
    public function scopeRecurring($query)
    {
        return $query->where('is_recurring', true);
    }

    /**
     * Scope: Depreciable assets.
     */
    public function scopeDepreciable($query)
    {
        return $query->where('is_depreciable', true);
    }

    /**
     * Approve this expense.
     */
    public function approve(User $approver): bool
    {
        $oldValues = [
            'status' => $this->status,
            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at,
        ];

        $this->update([
            'status' => 'approved',
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);

        $this->logTransaction('expense_approved', $oldValues, [
            'approved_by' => $approver->name,
            'approved_at' => now()->toDateTimeString(),
        ]);

        return true;
    }

    /**
     * Reject this expense.
     */
    public function reject(User $approver, string $reason): bool
    {
        $oldValues = [
            'status' => $this->status,
        ];

        $this->update([
            'status' => 'rejected',
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'rejection_reason' => $reason,
        ]);

        $this->logTransaction('expense_rejected', $oldValues, [
            'rejected_by' => $approver->name,
            'reason' => $reason,
        ]);

        return true;
    }

    /**
     * Mark expense as paid.
     */
    public function markAsPaid(string $paymentReference, string $paymentMethod): bool
    {
        $oldValues = [
            'status' => $this->status,
            'payment_reference' => $this->payment_reference,
        ];

        $this->update([
            'status' => 'paid',
            'payment_reference' => $paymentReference,
            'payment_method' => $paymentMethod,
            'payment_date' => now(),
        ]);

        $this->logTransaction('expense_paid', $oldValues, [
            'payment_reference' => $paymentReference,
            'payment_method' => $paymentMethod,
            'payment_date' => now()->toDateString(),
        ]);

        return true;
    }

    /**
     * Mark VAT as reclaimed.
     */
    public function markVatReclaimed(VatReturn $vatReturn): bool
    {
        if (!$this->is_vat_reclaimable || $this->vat_reclaimed) {
            return false;
        }

        $oldValues = [
            'vat_reclaimed' => $this->vat_reclaimed,
            'vat_return_id' => $this->vat_return_id,
        ];

        $this->update([
            'vat_reclaimed' => true,
            'vat_reclaim_date' => now(),
            'vat_return_id' => $vatReturn->id,
        ]);

        $this->logTransaction('vat_reclaimed', $oldValues, [
            'vat_return_number' => $vatReturn->return_number,
            'vat_amount' => $this->vat_amount,
        ]);

        return true;
    }

    /**
     * Calculate depreciation for the current month.
     */
    public function calculateDepreciation(): float
    {
        if (!$this->is_depreciable || !$this->depreciation_rate || !$this->useful_life_months) {
            return 0;
        }

        // Monthly depreciation amount
        $monthlyDepreciation = round($this->total_amount / $this->useful_life_months, 2);

        // Check if asset is fully depreciated
        if ($this->depreciated_amount >= $this->total_amount) {
            return 0;
        }

        return $monthlyDepreciation;
    }

    /**
     * Apply monthly depreciation.
     */
    public function applyDepreciation(): bool
    {
        $monthlyDepreciation = $this->calculateDepreciation();

        if ($monthlyDepreciation <= 0) {
            return false;
        }

        $newDepreciatedAmount = round($this->depreciated_amount + $monthlyDepreciation, 2);
        $newBookValue = round($this->total_amount - $newDepreciatedAmount, 2);

        // Ensure we don't depreciate below zero
        if ($newBookValue < 0) {
            $newBookValue = 0;
            $newDepreciatedAmount = $this->total_amount;
        }

        $oldValues = [
            'depreciated_amount' => $this->depreciated_amount,
            'book_value' => $this->book_value,
        ];

        $this->update([
            'depreciated_amount' => $newDepreciatedAmount,
            'book_value' => $newBookValue,
        ]);

        $this->logTransaction('depreciation_applied', $oldValues, [
            'monthly_depreciation' => $monthlyDepreciation,
            'new_book_value' => $newBookValue,
        ]);

        return true;
    }

    /**
     * Create next recurring expense.
     */
    public function createNextRecurring(): ?Expense
    {
        if (!$this->is_recurring || !$this->next_occurrence_date) {
            return null;
        }

        // Check if next occurrence is in the future
        if ($this->next_occurrence_date->isFuture()) {
            return null;
        }

        $nextExpense = static::create([
            'expense_category_id' => $this->expense_category_id,
            'vendor_name' => $this->vendor_name,
            'vendor_trn' => $this->vendor_trn,
            'amount_excl_vat' => $this->amount_excl_vat,
            'vat_rate' => $this->vat_rate,
            'vat_amount' => $this->vat_amount,
            'total_amount' => $this->total_amount,
            'currency' => $this->currency,
            'title' => $this->title,
            'description' => $this->description,
            'expense_date' => $this->next_occurrence_date,
            'is_vat_reclaimable' => $this->is_vat_reclaimable,
            'is_tax_deductible' => $this->is_tax_deductible,
            'status' => 'draft',
            'is_recurring' => true,
            'recurring_frequency' => $this->recurring_frequency,
            'recurring_parent_id' => $this->id,
            'created_by' => $this->created_by,
        ]);

        // Update this expense's next occurrence date
        $this->updateNextOccurrence();

        return $nextExpense;
    }

    /**
     * Update next occurrence date based on frequency.
     */
    public function updateNextOccurrence(): void
    {
        if (!$this->is_recurring || !$this->recurring_frequency) {
            return;
        }

        $currentDate = $this->next_occurrence_date ?? $this->expense_date;

        $nextDate = match ($this->recurring_frequency) {
            'daily' => $currentDate->addDay(),
            'weekly' => $currentDate->addWeek(),
            'monthly' => $currentDate->addMonth(),
            'quarterly' => $currentDate->addMonths(3),
            'yearly' => $currentDate->addYear(),
            default => null,
        };

        $this->update(['next_occurrence_date' => $nextDate]);
    }

    /**
     * Check if expense requires approval.
     */
    public function requiresApproval(): bool
    {
        if (!$this->category) {
            return false;
        }

        if (!$this->category->requires_approval) {
            return false;
        }

        // Check if amount exceeds threshold
        if ($this->category->approval_threshold && $this->total_amount < $this->category->approval_threshold) {
            return false;
        }

        return true;
    }
}
