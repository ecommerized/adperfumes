<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExpenseCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'code',
        'description',
        'icon',
        'color',
        'is_vat_reclaimable',
        'is_tax_deductible',
        'default_vat_rate',
        'type',
        'monthly_budget',
        'requires_approval',
        'approval_threshold',
        'parent_id',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_vat_reclaimable' => 'boolean',
        'is_tax_deductible' => 'boolean',
        'default_vat_rate' => 'decimal:2',
        'monthly_budget' => 'decimal:2',
        'approval_threshold' => 'decimal:2',
        'requires_approval' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get the parent category.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'parent_id');
    }

    /**
     * Get child categories.
     */
    public function children(): HasMany
    {
        return $this->hasMany(ExpenseCategory::class, 'parent_id')
            ->orderBy('sort_order');
    }

    /**
     * Get all expenses in this category.
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'expense_category_id');
    }

    /**
     * Scope: Active categories only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: By type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: VAT reclaimable categories.
     */
    public function scopeVatReclaimable($query)
    {
        return $query->where('is_vat_reclaimable', true);
    }

    /**
     * Scope: Tax deductible categories.
     */
    public function scopeTaxDeductible($query)
    {
        return $query->where('is_tax_deductible', true);
    }

    /**
     * Get total expenses for this category in a date range.
     */
    public function getTotalExpenses(string $from, string $to): float
    {
        return (float) $this->expenses()
            ->where('expense_date', '>=', $from)
            ->where('expense_date', '<=', $to)
            ->whereIn('status', ['approved', 'paid'])
            ->sum('total_amount');
    }

    /**
     * Get VAT reclaimable amount for this category.
     */
    public function getTotalReclaimableVat(string $from, string $to): float
    {
        if (!$this->is_vat_reclaimable) {
            return 0;
        }

        return (float) $this->expenses()
            ->where('expense_date', '>=', $from)
            ->where('expense_date', '<=', $to)
            ->whereIn('status', ['approved', 'paid'])
            ->where('is_vat_reclaimable', true)
            ->sum('vat_amount');
    }

    /**
     * Check if budget exceeded for a month.
     */
    public function isBudgetExceeded(int $year, int $month): bool
    {
        if (!$this->monthly_budget) {
            return false;
        }

        $startDate = "{$year}-{$month}-01";
        $endDate = date('Y-m-t', strtotime($startDate));

        $totalSpent = $this->getTotalExpenses($startDate, $endDate);

        return $totalSpent > $this->monthly_budget;
    }

    /**
     * Get budget utilization percentage for a month.
     */
    public function getBudgetUtilization(int $year, int $month): float
    {
        if (!$this->monthly_budget) {
            return 0;
        }

        $startDate = "{$year}-{$month}-01";
        $endDate = date('Y-m-t', strtotime($startDate));

        $totalSpent = $this->getTotalExpenses($startDate, $endDate);

        return round(($totalSpent / $this->monthly_budget) * 100, 2);
    }
}
