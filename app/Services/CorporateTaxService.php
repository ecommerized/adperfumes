<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Order;
use App\Models\Refund;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * CorporateTaxService
 *
 * Handles UAE Corporate Tax calculations (9% on profits).
 *
 * UAE Corporate Tax Rules:
 * - Rate: 9% on taxable income
 * - Tax-free threshold: AED 375,000 annual profit
 * - Applied to: Net profits (revenue - expenses)
 * - NOT applied to: Gross revenue (that's VAT territory)
 *
 * For this marketplace:
 * - Revenue = Commission earned from merchants + Platform fees
 * - Expenses = Payment gateway fees + Refunds + Operational costs (rent, salaries, utilities, etc.)
 * - Taxable Profit = Revenue - Expenses
 * - Corporate Tax = (Taxable Profit - 375,000) × 9%
 */
class CorporateTaxService
{
    /**
     * UAE Corporate Tax rate (9%)
     */
    const CORPORATE_TAX_RATE = 9.00;

    /**
     * Tax-free profit threshold (AED 375,000)
     */
    const TAX_FREE_THRESHOLD = 375000;

    /**
     * Calculate corporate tax for a given period
     *
     * @param Carbon $from Period start date
     * @param Carbon $to Period end date
     * @return array Tax calculation breakdown
     */
    public function calculateCorporateTax(Carbon $from, Carbon $to): array
    {
        $accountingService = app(AccountingService::class);

        // 1. REVENUE: Commission earned from merchant orders
        $commissionRevenue = $this->getCommissionRevenue($from, $to);

        // 2. PLATFORM FEES: Platform fees collected (Tap's 0.25%)
        $platformFeeRevenue = $this->getPlatformFeeRevenue($from, $to);

        // 3. TOTAL REVENUE
        $totalRevenue = $commissionRevenue + $platformFeeRevenue;

        // 4. EXPENSES: Payment gateway fees we paid to Tap
        $paymentGatewayExpenses = $this->getPaymentGatewayExpenses($from, $to);

        // 5. COMMISSION REVERSALS: Commission we gave back due to refunds
        $refundSummary = $accountingService->getRefundSummary($from, $to);
        $commissionReversed = $refundSummary['commission_reversed'];

        // 6. OPERATIONAL EXPENSES: Tax-deductible operational costs (rent, salaries, utilities, etc.)
        $operationalExpenses = $this->getOperationalExpenses($from, $to);

        // 7. TOTAL EXPENSES
        $totalExpenses = $paymentGatewayExpenses + $commissionReversed + $operationalExpenses;

        // 8. GROSS PROFIT
        $grossProfit = $totalRevenue - $totalExpenses;

        // 9. TAXABLE PROFIT (after threshold)
        $taxableProfit = max(0, $grossProfit);

        // 10. CORPORATE TAX CALCULATION
        $corporateTax = 0;
        if ($taxableProfit > self::TAX_FREE_THRESHOLD) {
            $taxableAmount = $taxableProfit - self::TAX_FREE_THRESHOLD;
            $corporateTax = round($taxableAmount * self::CORPORATE_TAX_RATE / 100, 2);
        }

        // 11. NET PROFIT AFTER TAX
        $netProfitAfterTax = round($taxableProfit - $corporateTax, 2);

        return [
            'period_start' => $from->toDateString(),
            'period_end' => $to->toDateString(),

            // Revenue
            'commission_revenue' => $commissionRevenue,
            'platform_fee_revenue' => $platformFeeRevenue,
            'total_revenue' => $totalRevenue,

            // Expenses
            'payment_gateway_expenses' => $paymentGatewayExpenses,
            'commission_reversed' => $commissionReversed,
            'operational_expenses' => $operationalExpenses,
            'total_expenses' => $totalExpenses,

            // Profit
            'gross_profit' => $grossProfit,
            'tax_free_threshold' => self::TAX_FREE_THRESHOLD,
            'taxable_profit' => $taxableProfit,

            // Tax
            'corporate_tax_rate' => self::CORPORATE_TAX_RATE,
            'corporate_tax_due' => $corporateTax,
            'net_profit_after_tax' => $netProfitAfterTax,

            // Effective tax rate
            'effective_tax_rate' => $grossProfit > 0
                ? round(($corporateTax / $grossProfit) * 100, 2)
                : 0,
        ];
    }

    /**
     * Get commission revenue for period
     *
     * @param Carbon $from
     * @param Carbon $to
     * @return float
     */
    private function getCommissionRevenue(Carbon $from, Carbon $to): float
    {
        return (float) DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.payment_status', 'paid')
            ->whereIn('orders.status', ['confirmed', 'processing', 'shipped', 'delivered'])
            ->whereBetween('orders.created_at', [$from->startOfDay(), $to->endOfDay()])
            ->whereNotNull('order_items.merchant_id') // Only merchant orders (exclude own store)
            ->sum('order_items.commission_amount');
    }

    /**
     * Get platform fee revenue for period
     *
     * @param Carbon $from
     * @param Carbon $to
     * @return float
     */
    private function getPlatformFeeRevenue(Carbon $from, Carbon $to): float
    {
        return (float) Order::where('payment_status', 'paid')
            ->whereIn('status', ['confirmed', 'processing', 'shipped', 'delivered'])
            ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
            ->sum('platform_fee_amount');
    }

    /**
     * Get payment gateway expenses for period
     *
     * @param Carbon $from
     * @param Carbon $to
     * @return float
     */
    private function getPaymentGatewayExpenses(Carbon $from, Carbon $to): float
    {
        return (float) Order::where('payment_status', 'paid')
            ->whereIn('status', ['confirmed', 'processing', 'shipped', 'delivered'])
            ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
            ->sum('payment_gateway_fee_total');
    }

    /**
     * Get operational expenses for period (tax-deductible only)
     *
     * Includes expenses like:
     * - Rent, utilities
     * - Salaries and benefits
     * - Marketing and advertising
     * - Professional services
     * - Software subscriptions
     * - Office supplies
     * - etc.
     *
     * @param Carbon $from
     * @param Carbon $to
     * @return float
     */
    private function getOperationalExpenses(Carbon $from, Carbon $to): float
    {
        return (float) Expense::whereIn('status', ['approved', 'paid'])
            ->where('is_tax_deductible', true)
            ->whereBetween('expense_date', [$from->startOfDay(), $to->endOfDay()])
            ->sum('amount_excl_vat'); // Use amount excluding VAT for tax calculation
    }

    /**
     * Calculate monthly corporate tax estimate
     *
     * @param int $month Month number (1-12)
     * @param int $year Year
     * @return array
     */
    public function calculateMonthlyTax(int $month, int $year): array
    {
        $from = Carbon::create($year, $month, 1)->startOfMonth();
        $to = $from->copy()->endOfMonth();

        return $this->calculateCorporateTax($from, $to);
    }

    /**
     * Calculate quarterly corporate tax
     *
     * @param int $quarter Quarter number (1-4)
     * @param int $year Year
     * @return array
     */
    public function calculateQuarterlyTax(int $quarter, int $year): array
    {
        $from = Carbon::create($year, (($quarter - 1) * 3) + 1, 1)->startOfMonth();
        $to = $from->copy()->addMonths(2)->endOfMonth();

        return $this->calculateCorporateTax($from, $to);
    }

    /**
     * Calculate annual corporate tax
     *
     * @param int $year Year
     * @return array
     */
    public function calculateAnnualTax(int $year): array
    {
        $from = Carbon::create($year, 1, 1)->startOfYear();
        $to = $from->copy()->endOfYear();

        return $this->calculateCorporateTax($from, $to);
    }

    /**
     * Get year-to-date corporate tax summary
     *
     * @return array
     */
    public function getYearToDateTax(): array
    {
        $from = Carbon::now()->startOfYear();
        $to = Carbon::now()->endOfDay();

        return $this->calculateCorporateTax($from, $to);
    }

    /**
     * Estimate monthly tax payment needed
     * (UAE requires advance tax payments)
     *
     * @param int $year Fiscal year
     * @return array
     */
    public function estimateMonthlyPayments(int $year): array
    {
        // Calculate estimated annual tax
        $annualTax = $this->calculateAnnualTax($year);

        // Divide by 12 for monthly payments
        $monthlyPayment = round($annualTax['corporate_tax_due'] / 12, 2);

        return [
            'fiscal_year' => $year,
            'estimated_annual_tax' => $annualTax['corporate_tax_due'],
            'monthly_payment_amount' => $monthlyPayment,
            'payment_schedule' => $this->generatePaymentSchedule($year, $monthlyPayment),
        ];
    }

    /**
     * Generate payment schedule for advance tax payments
     *
     * @param int $year
     * @param float $monthlyAmount
     * @return array
     */
    private function generatePaymentSchedule(int $year, float $monthlyAmount): array
    {
        $schedule = [];

        for ($month = 1; $month <= 12; $month++) {
            $schedule[] = [
                'month' => Carbon::create($year, $month, 1)->format('F Y'),
                'due_date' => Carbon::create($year, $month, 15)->toDateString(), // 15th of each month
                'amount' => $monthlyAmount,
            ];
        }

        return $schedule;
    }

    /**
     * Get tax dashboard summary
     *
     * @return array
     */
    public function getTaxDashboard(): array
    {
        $currentMonth = $this->calculateMonthlyTax(Carbon::now()->month, Carbon::now()->year);
        $ytd = $this->getYearToDateTax();
        $currentYear = $this->calculateAnnualTax(Carbon::now()->year);

        return [
            'current_month' => $currentMonth,
            'year_to_date' => $ytd,
            'estimated_annual' => $currentYear,
            'tax_rate' => self::CORPORATE_TAX_RATE . '%',
            'threshold' => 'AED ' . number_format(self::TAX_FREE_THRESHOLD, 2),
        ];
    }
}
