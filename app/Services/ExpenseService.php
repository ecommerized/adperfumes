<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ExpenseAttachment;
use App\Models\VatReturn;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ExpenseService
{
    /**
     * Create a new expense.
     */
    public function createExpense(array $data): Expense
    {
        DB::beginTransaction();
        try {
            // Get category defaults if not provided
            if (isset($data['expense_category_id'])) {
                $category = ExpenseCategory::find($data['expense_category_id']);

                if ($category) {
                    $data['is_vat_reclaimable'] = $data['is_vat_reclaimable'] ?? $category->is_vat_reclaimable;
                    $data['is_tax_deductible'] = $data['is_tax_deductible'] ?? $category->is_tax_deductible;
                    $data['vat_rate'] = $data['vat_rate'] ?? $category->default_vat_rate;
                }
            }

            // Calculate VAT if not provided
            if (isset($data['amount_excl_vat']) && !isset($data['vat_amount'])) {
                $vatRate = $data['vat_rate'] ?? config('accounting.vat_rate', 5.00);
                $data['vat_amount'] = round($data['amount_excl_vat'] * ($vatRate / 100), 2);
            }

            // Calculate total if not provided
            if (isset($data['amount_excl_vat']) && !isset($data['total_amount'])) {
                $data['total_amount'] = round($data['amount_excl_vat'] + ($data['vat_amount'] ?? 0), 2);
            }

            // Set created_by if not set
            $data['created_by'] = $data['created_by'] ?? auth()->id();

            // Create expense
            $expense = Expense::create($data);

            // Handle attachments if provided
            if (isset($data['attachments']) && is_array($data['attachments'])) {
                foreach ($data['attachments'] as $attachmentData) {
                    if ($attachmentData instanceof UploadedFile) {
                        $this->addAttachment($expense, $attachmentData);
                    }
                }
            }

            // Load category relationship before checking approval requirements
            $expense->load('category');

            // Check if requires approval and auto-submit
            if ($expense->requiresApproval()) {
                $expense->update(['status' => 'pending_approval']);
            }

            DB::commit();
            Log::info('Expense created', ['expense_number' => $expense->expense_number]);

            return $expense;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create expense', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Update an existing expense.
     */
    public function updateExpense(Expense $expense, array $data): Expense
    {
        DB::beginTransaction();
        try {
            // Recalculate VAT if amount changed
            if (isset($data['amount_excl_vat']) && $data['amount_excl_vat'] != $expense->amount_excl_vat) {
                $vatRate = $data['vat_rate'] ?? $expense->vat_rate;
                $data['vat_amount'] = round($data['amount_excl_vat'] * ($vatRate / 100), 2);
                $data['total_amount'] = round($data['amount_excl_vat'] + $data['vat_amount'], 2);
            }

            $oldValues = $expense->toArray();
            $expense->update($data);

            $expense->logTransaction('expense_updated', $oldValues, $data);

            DB::commit();
            Log::info('Expense updated', ['expense_number' => $expense->expense_number]);

            return $expense->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update expense', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Add attachment to expense.
     */
    public function addAttachment(Expense $expense, UploadedFile $file, array $metadata = []): ExpenseAttachment
    {
        $fileName = $file->getClientOriginalName();
        $fileExtension = $file->getClientOriginalExtension();
        $fileSize = $file->getSize();
        $mimeType = $file->getMimeType();

        // Generate unique file name
        $storageName = sprintf(
            'expenses/%s/%s_%s.%s',
            $expense->expense_number,
            time(),
            uniqid(),
            $fileExtension
        );

        // Store file
        $path = $file->storeAs('expenses/' . $expense->expense_number, basename($storageName));

        // Create attachment record
        $attachment = $expense->expenseAttachments()->create([
            'file_name' => $fileName,
            'file_path' => $path,
            'file_type' => $fileExtension,
            'file_size' => $fileSize,
            'mime_type' => $mimeType,
            'attachment_type' => $metadata['type'] ?? 'receipt',
            'description' => $metadata['description'] ?? null,
            'uploaded_by' => auth()->id(),
        ]);

        Log::info('Attachment added to expense', [
            'expense_number' => $expense->expense_number,
            'file_name' => $fileName,
        ]);

        return $attachment;
    }

    /**
     * Get total expenses for a date range.
     */
    public function getTotalExpenses(string $from, string $to, array $filters = []): float
    {
        $query = Expense::approved()
            ->dateRange($from, $to);

        if (isset($filters['category_id'])) {
            $query->where('expense_category_id', $filters['category_id']);
        }

        if (isset($filters['is_tax_deductible'])) {
            $query->where('is_tax_deductible', $filters['is_tax_deductible']);
        }

        if (isset($filters['merchant_id'])) {
            $query->where('merchant_id', $filters['merchant_id']);
        }

        return (float) $query->sum('total_amount');
    }

    /**
     * Get tax-deductible expenses for corporate tax calculation.
     */
    public function getTaxDeductibleExpenses(string $from, string $to): array
    {
        $expenses = Expense::approved()
            ->taxDeductible()
            ->dateRange($from, $to)
            ->get();

        $totalAmountExclVat = $expenses->sum('amount_excl_vat');
        $totalVatAmount = $expenses->sum('vat_amount');
        $totalAmount = $expenses->sum('total_amount');

        return [
            'expenses_count' => $expenses->count(),
            'total_amount_excl_vat' => round($totalAmountExclVat, 2),
            'total_vat_amount' => round($totalVatAmount, 2),
            'total_amount' => round($totalAmount, 2),
            'expenses' => $expenses,
        ];
    }

    /**
     * Get input VAT reclaimable amount for VAT return.
     */
    public function getInputVatReclaimable(string $from, string $to): array
    {
        $expenses = Expense::approved()
            ->vatReclaimable()
            ->dateRange($from, $to)
            ->where('vat_reclaimed', false) // Not yet reclaimed
            ->get();

        $totalPurchasesExclVat = $expenses->sum('amount_excl_vat');
        $totalInputVat = $expenses->sum('vat_amount');

        // Calculate reclaimable VAT (may be different from total input VAT based on FTA rules)
        $reclaimableVat = $this->calculateReclaimableVat($expenses);

        return [
            'expenses_count' => $expenses->count(),
            'total_purchases_excl_vat' => round($totalPurchasesExclVat, 2),
            'total_input_vat' => round($totalInputVat, 2),
            'input_vat_reclaimable' => round($reclaimableVat, 2),
            'expenses' => $expenses,
        ];
    }

    /**
     * Calculate reclaimable VAT based on FTA rules.
     *
     * In UAE, businesses can reclaim input VAT on expenses that are used
     * for making taxable supplies.
     */
    protected function calculateReclaimableVat($expenses): float
    {
        $reclaimable = 0;

        foreach ($expenses as $expense) {
            // Basic rule: Full VAT reclaimable if category allows it
            if ($expense->is_vat_reclaimable && $expense->category) {
                if ($expense->category->is_vat_reclaimable) {
                    $reclaimable += $expense->vat_amount;
                }
            }

            // Special cases can be added here (e.g., partial reclaim for dual-use assets)
        }

        return $reclaimable;
    }

    /**
     * Mark expenses as VAT reclaimed for a VAT return.
     */
    public function markExpensesVatReclaimed(VatReturn $vatReturn): int
    {
        $expenses = Expense::approved()
            ->vatReclaimable()
            ->dateRange($vatReturn->period_start->toDateString(), $vatReturn->period_end->toDateString())
            ->where('vat_reclaimed', false)
            ->get();

        $count = 0;

        foreach ($expenses as $expense) {
            if ($expense->markVatReclaimed($vatReturn)) {
                $count++;
            }
        }

        Log::info('Expenses marked as VAT reclaimed', [
            'vat_return' => $vatReturn->return_number,
            'expenses_count' => $count,
        ]);

        return $count;
    }

    /**
     * Get expense summary by category for a date range.
     */
    public function getExpenseSummaryByCategory(string $from, string $to): array
    {
        $summary = Expense::approved()
            ->dateRange($from, $to)
            ->select('expense_category_id', DB::raw('SUM(total_amount) as total'))
            ->groupBy('expense_category_id')
            ->with('category')
            ->get()
            ->map(function ($item) {
                return [
                    'category_id' => $item->expense_category_id,
                    'category_name' => $item->category->name ?? 'Uncategorized',
                    'total_amount' => round($item->total, 2),
                ];
            })
            ->toArray();

        return $summary;
    }

    /**
     * Get expense summary by type for a date range.
     */
    public function getExpenseSummaryByType(string $from, string $to): array
    {
        $summary = Expense::approved()
            ->dateRange($from, $to)
            ->join('expense_categories', 'expenses.expense_category_id', '=', 'expense_categories.id')
            ->select('expense_categories.type', DB::raw('SUM(expenses.total_amount) as total'))
            ->groupBy('expense_categories.type')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->type => round($item->total, 2)];
            })
            ->toArray();

        return $summary;
    }

    /**
     * Apply depreciation to all depreciable assets for current month.
     */
    public function applyMonthlyDepreciation(): int
    {
        $depreciableExpenses = Expense::depreciable()
            ->approved()
            ->where('book_value', '>', 0)
            ->get();

        $count = 0;

        foreach ($depreciableExpenses as $expense) {
            if ($expense->applyDepreciation()) {
                $count++;
            }
        }

        Log::info('Monthly depreciation applied', [
            'assets_count' => $count,
            'month' => now()->format('Y-m'),
        ]);

        return $count;
    }

    /**
     * Create recurring expenses that are due.
     */
    public function createDueRecurringExpenses(): int
    {
        $recurringExpenses = Expense::recurring()
            ->approved()
            ->whereNotNull('next_occurrence_date')
            ->where('next_occurrence_date', '<=', now())
            ->get();

        $count = 0;

        foreach ($recurringExpenses as $expense) {
            if ($expense->createNextRecurring()) {
                $count++;
            }
        }

        Log::info('Recurring expenses created', [
            'count' => $count,
            'date' => now()->toDateString(),
        ]);

        return $count;
    }

    /**
     * Get expense dashboard data.
     */
    public function getExpenseDashboard(string $from, string $to): array
    {
        $totalExpenses = $this->getTotalExpenses($from, $to);
        $taxDeductible = $this->getTaxDeductibleExpenses($from, $to);
        $inputVat = $this->getInputVatReclaimable($from, $to);
        $byCategory = $this->getExpenseSummaryByCategory($from, $to);
        $byType = $this->getExpenseSummaryByType($from, $to);

        $pendingApproval = Expense::pendingApproval()->count();
        $overdueExpenses = Expense::where('payment_date', '<', now())
            ->where('status', 'approved')
            ->count();

        return [
            'period' => [
                'from' => $from,
                'to' => $to,
            ],
            'summary' => [
                'total_expenses' => $totalExpenses,
                'tax_deductible_amount' => $taxDeductible['total_amount_excl_vat'],
                'input_vat_reclaimable' => $inputVat['input_vat_reclaimable'],
                'pending_approval' => $pendingApproval,
                'overdue_payments' => $overdueExpenses,
            ],
            'by_category' => $byCategory,
            'by_type' => $byType,
        ];
    }
}
