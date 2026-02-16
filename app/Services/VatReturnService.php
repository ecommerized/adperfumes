<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Order;
use App\Models\TaxComplianceEvent;
use App\Models\VatReturn;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * VatReturnService
 *
 * Handles UAE VAT Return preparation, calculation, and filing.
 *
 * UAE VAT Rules:
 * - Standard Rate: 5%
 * - Zero-rated supplies: 0%
 * - Exempt supplies: No VAT
 * - Filing frequency: Quarterly (most businesses) or Monthly (large businesses)
 * - Filing deadline: 28 days after period end
 * - Payment deadline: Same as filing deadline
 *
 * VAT Return Calculation:
 * - Output VAT = VAT collected on sales (5% of taxable supplies)
 * - Input VAT = VAT paid on purchases/expenses (reclaimable)
 * - Net VAT Payable = Output VAT - Input VAT
 * - If negative, refund is due from FTA
 */
class VatReturnService
{
    protected $expenseService;
    protected $accountingService;

    public function __construct(
        ExpenseService $expenseService,
        AccountingService $accountingService
    ) {
        $this->expenseService = $expenseService;
        $this->accountingService = $accountingService;
    }

    /**
     * Prepare VAT return for a period.
     */
    public function prepareVatReturn(Carbon $periodStart, Carbon $periodEnd, string $periodType = 'quarterly'): VatReturn
    {
        DB::beginTransaction();
        try {
            // 1. Calculate OUTPUT VAT (from sales)
            $salesData = $this->calculateOutputVat($periodStart, $periodEnd);

            // 2. Calculate INPUT VAT (from expenses)
            $purchasesData = $this->calculateInputVat($periodStart, $periodEnd);

            // 3. Calculate NET VAT
            $netVatPayable = round(
                $salesData['output_vat_amount'] - $purchasesData['input_vat_reclaimable'],
                2
            );

            // 4. Determine quarter/month
            $year = $periodStart->year;
            $quarter = $periodType === 'quarterly' ? $periodStart->quarter : null;
            $month = $periodType === 'monthly' ? $periodStart->month : null;

            // 5. Create VAT return
            $vatReturn = VatReturn::create([
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'period_type' => $periodType,
                'year' => $year,
                'quarter' => $quarter,
                'month' => $month,

                // Output VAT (Sales)
                'total_sales_excl_vat' => $salesData['total_sales_excl_vat'],
                'output_vat_rate' => config('accounting.vat_rate', 5.00),
                'output_vat_amount' => $salesData['output_vat_amount'],
                'zero_rated_sales' => $salesData['zero_rated_sales'],
                'exempt_sales' => $salesData['exempt_sales'],

                // Input VAT (Purchases)
                'total_purchases_excl_vat' => $purchasesData['total_purchases_excl_vat'],
                'input_vat_amount' => $purchasesData['total_input_vat'],
                'input_vat_reclaimable' => $purchasesData['input_vat_reclaimable'],

                // Net VAT
                'net_vat_payable' => $netVatPayable,

                // Defaults
                'adjustments' => 0,
                'status' => 'draft',
                'filing_deadline' => $periodEnd->copy()->addDays(28),
                'payment_due_date' => $periodEnd->copy()->addDays(28),
                'prepared_by' => auth()->id(),
            ]);

            // 6. Create compliance events
            $this->createComplianceEvents($vatReturn);

            DB::commit();

            Log::info('VAT return prepared', [
                'return_number' => $vatReturn->return_number,
                'period' => $periodStart->format('Y-m-d') . ' to ' . $periodEnd->format('Y-m-d'),
                'net_vat_payable' => $netVatPayable,
            ]);

            return $vatReturn;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to prepare VAT return', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Calculate output VAT (VAT collected from sales).
     */
    protected function calculateOutputVat(Carbon $from, Carbon $to): array
    {
        $vatRate = config('accounting.vat_rate', 5.00);

        // Get all paid orders in period
        $orders = Order::where('payment_status', 'paid')
            ->whereIn('status', ['confirmed', 'processing', 'shipped', 'delivered'])
            ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
            ->get();

        $totalSalesInclVat = $orders->sum('grand_total');

        // Back-calculate VAT from tax-inclusive prices
        // Formula: VAT = (Total / 1.05) × 0.05
        $totalSalesExclVat = round($totalSalesInclVat / (1 + ($vatRate / 100)), 2);
        $outputVat = round($totalSalesInclVat - $totalSalesExclVat, 2);

        // Zero-rated and exempt sales (currently 0, can be extended)
        $zeroRatedSales = 0;
        $exemptSales = 0;

        return [
            'total_sales_incl_vat' => $totalSalesInclVat,
            'total_sales_excl_vat' => $totalSalesExclVat,
            'output_vat_amount' => $outputVat,
            'zero_rated_sales' => $zeroRatedSales,
            'exempt_sales' => $exemptSales,
            'orders_count' => $orders->count(),
        ];
    }

    /**
     * Calculate input VAT (VAT paid on expenses that can be reclaimed).
     */
    protected function calculateInputVat(Carbon $from, Carbon $to): array
    {
        // Get input VAT data from ExpenseService
        $inputVatData = $this->expenseService->getInputVatReclaimable(
            $from->toDateString(),
            $to->toDateString()
        );

        return [
            'total_purchases_excl_vat' => $inputVatData['total_purchases_excl_vat'],
            'total_input_vat' => $inputVatData['total_input_vat'],
            'input_vat_reclaimable' => $inputVatData['input_vat_reclaimable'],
            'expenses_count' => $inputVatData['expenses_count'],
        ];
    }

    /**
     * Prepare quarterly VAT return.
     */
    public function prepareQuarterlyReturn(int $year, int $quarter): VatReturn
    {
        $startMonth = (($quarter - 1) * 3) + 1;
        $periodStart = Carbon::create($year, $startMonth, 1)->startOfMonth();
        $periodEnd = $periodStart->copy()->addMonths(2)->endOfMonth();

        return $this->prepareVatReturn($periodStart, $periodEnd, 'quarterly');
    }

    /**
     * Prepare monthly VAT return.
     */
    public function prepareMonthlyReturn(int $year, int $month): VatReturn
    {
        $periodStart = Carbon::create($year, $month, 1)->startOfMonth();
        $periodEnd = $periodStart->copy()->endOfMonth();

        return $this->prepareVatReturn($periodStart, $periodEnd, 'monthly');
    }

    /**
     * Prepare current quarter VAT return.
     */
    public function prepareCurrentQuarterReturn(): VatReturn
    {
        $now = Carbon::now();
        return $this->prepareQuarterlyReturn($now->year, $now->quarter);
    }

    /**
     * Submit VAT return for review.
     */
    public function submitForReview(VatReturn $vatReturn): bool
    {
        if ($vatReturn->status !== 'draft') {
            return false;
        }

        $vatReturn->update(['status' => 'pending_review']);

        $vatReturn->logTransaction('vat_return_submitted', ['status' => 'draft'], [
            'status' => 'pending_review',
            'submitted_by' => auth()->user()?->name ?? 'System',
        ]);

        return true;
    }

    /**
     * Approve VAT return.
     */
    public function approveReturn(VatReturn $vatReturn, int $approverId): bool
    {
        return $vatReturn->approve(\App\Models\User::findOrFail($approverId));
    }

    /**
     * File VAT return with FTA.
     */
    public function fileReturn(VatReturn $vatReturn, string $ftaReference): bool
    {
        if (!$vatReturn->markAsFiled($ftaReference)) {
            return false;
        }

        // Mark all expenses as VAT reclaimed
        $this->expenseService->markExpensesVatReclaimed($vatReturn);

        return true;
    }

    /**
     * Record VAT payment.
     */
    public function recordPayment(VatReturn $vatReturn, string $paymentReference): bool
    {
        return $vatReturn->markAsPaid($paymentReference);
    }

    /**
     * Create compliance events for VAT return.
     */
    protected function createComplianceEvents(VatReturn $vatReturn): void
    {
        // Filing deadline event
        TaxComplianceEvent::create([
            'title' => "VAT Return Filing - {$vatReturn->return_number}",
            'description' => "File VAT return for period {$vatReturn->period_start->format('M Y')} to {$vatReturn->period_end->format('M Y')}",
            'tax_type' => 'vat',
            'event_type' => 'filing_deadline',
            'due_date' => $vatReturn->filing_deadline,
            'reminder_date' => $vatReturn->filing_deadline->copy()->subDays(7),
            'vat_return_id' => $vatReturn->id,
            'status' => 'upcoming',
        ]);

        // Payment deadline event (if VAT is payable)
        if ($vatReturn->net_vat_payable > 0) {
            TaxComplianceEvent::create([
                'title' => "VAT Payment Due - {$vatReturn->return_number}",
                'description' => "Pay VAT amount: AED " . number_format($vatReturn->net_vat_payable, 2),
                'tax_type' => 'vat',
                'event_type' => 'payment_deadline',
                'due_date' => $vatReturn->payment_due_date,
                'reminder_date' => $vatReturn->payment_due_date->copy()->subDays(3),
                'vat_return_id' => $vatReturn->id,
                'status' => 'upcoming',
            ]);
        }
    }

    /**
     * Get VAT return dashboard summary.
     */
    public function getDashboard(): array
    {
        $currentQuarter = Carbon::now()->quarter;
        $currentYear = Carbon::now()->year;

        // Try to find current quarter return
        $currentReturn = VatReturn::where('year', $currentYear)
            ->where('quarter', $currentQuarter)
            ->where('period_type', 'quarterly')
            ->first();

        // Get all pending returns
        $pendingReturns = VatReturn::whereIn('status', ['draft', 'pending_review'])
            ->orderBy('filing_deadline')
            ->get();

        // Get overdue returns
        $overdueReturns = VatReturn::overdue()->get();

        // Get deadline approaching returns
        $upcomingDeadlines = VatReturn::deadlineApproaching()->get();

        // Get YTD VAT summary
        $ytdStart = Carbon::now()->startOfYear();
        $ytdEnd = Carbon::now()->endOfDay();

        $ytdOutputVat = $this->calculateOutputVat($ytdStart, $ytdEnd);
        $ytdInputVat = $this->calculateInputVat($ytdStart, $ytdEnd);
        $ytdNetVat = round($ytdOutputVat['output_vat_amount'] - $ytdInputVat['input_vat_reclaimable'], 2);

        return [
            'current_quarter' => [
                'quarter' => $currentQuarter,
                'year' => $currentYear,
                'return' => $currentReturn,
                'status' => $currentReturn?->status ?? 'not_prepared',
                'filing_deadline' => $currentReturn?->filing_deadline ?? null,
            ],
            'pending_returns' => $pendingReturns,
            'overdue_returns' => $overdueReturns,
            'upcoming_deadlines' => $upcomingDeadlines,
            'ytd_summary' => [
                'output_vat' => $ytdOutputVat['output_vat_amount'],
                'input_vat_reclaimable' => $ytdInputVat['input_vat_reclaimable'],
                'net_vat_payable' => $ytdNetVat,
            ],
        ];
    }

    /**
     * Get VAT summary for a period.
     */
    public function getVatSummary(Carbon $from, Carbon $to): array
    {
        $outputVat = $this->calculateOutputVat($from, $to);
        $inputVat = $this->calculateInputVat($from, $to);

        $netVatPayable = round($outputVat['output_vat_amount'] - $inputVat['input_vat_reclaimable'], 2);

        return [
            'period_start' => $from->toDateString(),
            'period_end' => $to->toDateString(),

            // Output VAT
            'total_sales_excl_vat' => $outputVat['total_sales_excl_vat'],
            'total_sales_incl_vat' => $outputVat['total_sales_incl_vat'],
            'output_vat_amount' => $outputVat['output_vat_amount'],
            'orders_count' => $outputVat['orders_count'],

            // Input VAT
            'total_purchases_excl_vat' => $inputVat['total_purchases_excl_vat'],
            'input_vat_amount' => $inputVat['total_input_vat'],
            'input_vat_reclaimable' => $inputVat['input_vat_reclaimable'],
            'expenses_count' => $inputVat['expenses_count'],

            // Net VAT
            'net_vat_payable' => $netVatPayable,
            'is_refund_due' => $netVatPayable < 0,
            'refund_amount' => $netVatPayable < 0 ? abs($netVatPayable) : 0,
        ];
    }
}
