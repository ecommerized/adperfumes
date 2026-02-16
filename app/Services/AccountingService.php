<?php

namespace App\Services;

use App\Models\CreditNote;
use App\Models\Merchant;
use App\Models\MerchantDebitNote;
use App\Models\Order;
use App\Models\Reconciliation;
use App\Models\Refund;
use App\Models\Settlement;
use App\Models\SettlementItem;
use App\Models\TaxReport;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AccountingService
{
    /**
     * Get Gross Merchandise Value for a period.
     * Sum of grand_total from paid orders.
     */
    public function getGmv(Carbon $from, Carbon $to): float
    {
        return (float) Order::where('payment_status', 'paid')
            ->whereIn('status', ['confirmed', 'processing', 'shipped', 'delivered'])
            ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
            ->sum('grand_total');
    }

    /**
     * Get commission revenue earned by the platform.
     * Sum of commission_amount from settlement items in period.
     */
    public function getCommissionRevenue(Carbon $from, Carbon $to): float
    {
        return (float) SettlementItem::whereHas('settlement', function ($q) use ($from, $to) {
            $q->whereBetween('payout_date', [$from, $to]);
        })->sum('commission_amount');
    }

    /**
     * Get refund summary for a period.
     */
    public function getRefundSummary(Carbon $from, Carbon $to): array
    {
        $refunds = Refund::whereIn('status', ['completed', 'fully_resolved'])
            ->whereBetween('completed_at', [$from->startOfDay(), $to->endOfDay()])
            ->get();

        return [
            'count' => $refunds->count(),
            'total_refunded' => (float) $refunds->sum('refund_total'),
            'total_subtotal' => (float) $refunds->sum('refund_subtotal'),
            'total_tax' => (float) $refunds->sum('refund_tax'),
            'commission_reversed' => (float) $refunds->sum('total_commission_reversed'),
            'merchant_recovery' => (float) $refunds->sum('merchant_recovery_amount'),
        ];
    }

    /**
     * Get net platform revenue.
     * Commission earned minus commission reversed from refunds.
     */
    public function getNetRevenue(Carbon $from, Carbon $to): float
    {
        $commissionEarned = $this->getCommissionRevenue($from, $to);

        $commissionReversed = (float) Refund::whereIn('status', ['completed', 'fully_resolved'])
            ->whereBetween('completed_at', [$from->startOfDay(), $to->endOfDay()])
            ->sum('total_commission_reversed');

        return round($commissionEarned - $commissionReversed, 2);
    }

    /**
     * Get UAE VAT tax summary.
     */
    public function getTaxSummary(Carbon $from, Carbon $to): array
    {
        $taxRate = 5.00;

        // Output VAT collected from paid orders
        $gmv = $this->getGmv($from, $to);
        $gmvExclTax = round($gmv / (1 + ($taxRate / 100)), 2);
        $outputVat = round($gmv - $gmvExclTax, 2);

        // Commission VAT
        $commissionEarned = $this->getCommissionRevenue($from, $to);
        $commissionVat = round($commissionEarned * $taxRate / 100, 2);

        // Refund VAT reversed
        $refundSummary = $this->getRefundSummary($from, $to);

        return [
            'total_sales_incl_tax' => $gmv,
            'total_sales_excl_tax' => $gmvExclTax,
            'output_vat' => $outputVat,
            'commission_earned' => $commissionEarned,
            'commission_vat' => $commissionVat,
            'refund_tax_reversed' => $refundSummary['total_tax'],
            'net_vat_payable' => round($outputVat - $refundSummary['total_tax'], 2),
        ];
    }

    /**
     * Generate or update a TaxReport record.
     */
    public function generateTaxReport(Carbon $from, Carbon $to, string $type = 'custom'): TaxReport
    {
        $taxSummary = $this->getTaxSummary($from, $to);

        // Merchant breakdown
        $merchantBreakdown = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('merchants', 'merchants.id', '=', 'order_items.merchant_id')
            ->where('orders.payment_status', 'paid')
            ->whereIn('orders.status', ['confirmed', 'processing', 'shipped', 'delivered'])
            ->whereBetween('orders.created_at', [$from->startOfDay(), $to->endOfDay()])
            ->groupBy('order_items.merchant_id', 'merchants.business_name')
            ->select(
                'order_items.merchant_id',
                'merchants.business_name',
                DB::raw('SUM(order_items.subtotal) as total_sales'),
                DB::raw('ROUND(SUM(order_items.subtotal) - SUM(order_items.subtotal) / 1.05, 2) as vat_amount'),
                DB::raw('SUM(order_items.commission_amount) as commission'),
            )
            ->get()
            ->toArray();

        // Category breakdown
        $categoryBreakdown = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->where('orders.payment_status', 'paid')
            ->whereIn('orders.status', ['confirmed', 'processing', 'shipped', 'delivered'])
            ->whereBetween('orders.created_at', [$from->startOfDay(), $to->endOfDay()])
            ->groupBy('products.category_id', 'categories.name')
            ->select(
                'products.category_id',
                DB::raw("COALESCE(categories.name, 'Uncategorized') as category_name"),
                DB::raw('SUM(order_items.subtotal) as total_sales'),
                DB::raw('ROUND(SUM(order_items.subtotal) - SUM(order_items.subtotal) / 1.05, 2) as vat_amount'),
            )
            ->get()
            ->toArray();

        $totalMerchants = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.payment_status', 'paid')
            ->whereIn('orders.status', ['confirmed', 'processing', 'shipped', 'delivered'])
            ->whereBetween('orders.created_at', [$from->startOfDay(), $to->endOfDay()])
            ->distinct('order_items.merchant_id')
            ->count('order_items.merchant_id');

        $totalOrders = Order::where('payment_status', 'paid')
            ->whereIn('status', ['confirmed', 'processing', 'shipped', 'delivered'])
            ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
            ->count();

        $lastReport = TaxReport::orderBy('id', 'desc')->first();
        $nextNumber = $lastReport ? $lastReport->id + 1 : 1;

        return TaxReport::create([
            'report_number' => 'TAX-' . now()->format('Ym') . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT),
            'report_type' => $type,
            'period_start' => $from,
            'period_end' => $to,
            'total_sales_incl_tax' => $taxSummary['total_sales_incl_tax'],
            'total_sales_excl_tax' => $taxSummary['total_sales_excl_tax'],
            'total_output_vat' => $taxSummary['output_vat'],
            'total_commission_earned' => $taxSummary['commission_earned'],
            'total_commission_vat' => $taxSummary['commission_vat'],
            'net_vat_payable' => $taxSummary['net_vat_payable'],
            'total_orders' => $totalOrders,
            'total_merchants' => $totalMerchants,
            'merchant_breakdown' => $merchantBreakdown,
            'category_breakdown' => $categoryBreakdown,
            'status' => 'generated',
        ]);
    }

    /**
     * Get per-merchant statement for a period.
     */
    public function getMerchantStatement(Merchant $merchant, Carbon $from, Carbon $to): array
    {
        // Orders with this merchant's items
        $orderItems = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('order_items.merchant_id', $merchant->id)
            ->where('orders.payment_status', 'paid')
            ->whereBetween('orders.created_at', [$from->startOfDay(), $to->endOfDay()])
            ->select(
                'orders.id as order_id',
                'orders.order_number',
                'orders.created_at',
                'orders.status',
                DB::raw('SUM(order_items.subtotal) as order_amount'),
                DB::raw('SUM(order_items.commission_amount) as commission'),
            )
            ->groupBy('orders.id', 'orders.order_number', 'orders.created_at', 'orders.status')
            ->get();

        $totalGmv = (float) $orderItems->sum('order_amount');
        $totalCommission = (float) $orderItems->sum('commission');

        // Refunds
        $refunds = Refund::where('merchant_id', $merchant->id)
            ->whereIn('status', ['completed', 'fully_resolved'])
            ->whereBetween('completed_at', [$from->startOfDay(), $to->endOfDay()])
            ->get();

        $totalRefunds = (float) $refunds->sum('refund_total');
        $commissionReversed = (float) $refunds->sum('total_commission_reversed');

        // Debit notes
        $debitNotes = MerchantDebitNote::where('merchant_id', $merchant->id)
            ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
            ->get();

        $totalDebitNotes = (float) $debitNotes->sum('recovery_amount');

        // Settlements paid
        $settlements = Settlement::where('merchant_id', $merchant->id)
            ->where('status', 'paid')
            ->whereBetween('paid_at', [$from->startOfDay(), $to->endOfDay()])
            ->get();

        $totalSettled = (float) $settlements->sum('merchant_payout');

        // Outstanding balance
        $pendingSettlements = Settlement::where('merchant_id', $merchant->id)
            ->where('status', 'pending')
            ->sum('merchant_payout');

        return [
            'merchant' => $merchant,
            'period_start' => $from,
            'period_end' => $to,
            'orders' => $orderItems,
            'order_count' => $orderItems->count(),
            'total_gmv' => $totalGmv,
            'total_commission' => $totalCommission,
            'net_earnings' => round($totalGmv - $totalCommission, 2),
            'refunds' => $refunds,
            'total_refunds' => $totalRefunds,
            'commission_reversed' => $commissionReversed,
            'debit_notes' => $debitNotes,
            'total_debit_notes' => $totalDebitNotes,
            'settlements' => $settlements,
            'total_settled' => $totalSettled,
            'outstanding_balance' => (float) $pendingSettlements,
        ];
    }

    /**
     * Get summary of all merchants with pending settlement amounts.
     */
    public function getMerchantPayablesSummary(Carbon $from, Carbon $to): array
    {
        return DB::table('orders')
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->join('merchants', 'merchants.id', '=', 'order_items.merchant_id')
            ->leftJoin('settlement_items', 'settlement_items.order_id', '=', 'orders.id')
            ->whereNull('settlement_items.id')
            ->where('orders.status', 'delivered')
            ->where('orders.payment_status', 'paid')
            ->groupBy('order_items.merchant_id', 'merchants.business_name')
            ->select(
                'order_items.merchant_id',
                'merchants.business_name',
                DB::raw('COUNT(DISTINCT orders.id) as eligible_orders'),
                DB::raw('SUM(order_items.subtotal) as total_amount'),
                DB::raw('SUM(order_items.commission_amount) as total_commission'),
                DB::raw('SUM(order_items.subtotal) - SUM(order_items.commission_amount) as pending_payout'),
            )
            ->orderByDesc('pending_payout')
            ->get()
            ->toArray();
    }

    /**
     * Get Profit & Loss statement (UPDATED with payment fees).
     */
    public function getProfitAndLoss(Carbon $from, Carbon $to): array
    {
        // Revenue: Commission earned from settlements
        $commissionRevenue = $this->getCommissionRevenue($from, $to);

        // Also count unsettled commission from order_items
        $unsettledCommission = (float) DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->leftJoin('settlement_items', 'settlement_items.order_id', '=', 'orders.id')
            ->whereNull('settlement_items.id')
            ->where('orders.payment_status', 'paid')
            ->whereIn('orders.status', ['confirmed', 'processing', 'shipped', 'delivered'])
            ->whereBetween('orders.created_at', [$from->startOfDay(), $to->endOfDay()])
            ->sum('order_items.commission_amount');

        $totalCommissionEarned = round($commissionRevenue + $unsettledCommission, 2);

        // Platform fee revenue (NEW)
        $platformFeeRevenue = (float) Order::where('payment_status', 'paid')
            ->whereIn('status', ['confirmed', 'processing', 'shipped', 'delivered'])
            ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
            ->sum('platform_fee_amount');

        // Total Revenue
        $totalRevenue = round($totalCommissionEarned + $platformFeeRevenue, 2);

        // Expenses: Payment gateway fees (NEW - what we pay to Tap)
        $paymentGatewayExpenses = (float) Order::where('payment_status', 'paid')
            ->whereIn('status', ['confirmed', 'processing', 'shipped', 'delivered'])
            ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
            ->sum('payment_gateway_fee_total');

        // Expenses: Commission reversals from refunds
        $refundSummary = $this->getRefundSummary($from, $to);
        $commissionReversals = $refundSummary['commission_reversed'];

        // Total Expenses
        $totalExpenses = round($paymentGatewayExpenses + $commissionReversals, 2);

        // Merchant payouts (settlements paid)
        $settlementsPaid = (float) Settlement::where('status', 'paid')
            ->whereBetween('paid_at', [$from->startOfDay(), $to->endOfDay()])
            ->sum('net_payout'); // Changed from merchant_payout to net_payout

        // Gross Profit (Revenue - Expenses)
        $grossProfit = round($totalRevenue - $totalExpenses, 2);

        // Net Profit (same as gross for now, corporate tax calculated separately)
        $netProfit = $grossProfit;

        $gmv = $this->getGmv($from, $to);

        return [
            'period_start' => $from->toDateString(),
            'period_end' => $to->toDateString(),
            'gmv' => $gmv,
            'revenue' => [
                'settled_commission' => $commissionRevenue,
                'unsettled_commission' => $unsettledCommission,
                'total_commission_earned' => $totalCommissionEarned,
                'platform_fee_revenue' => $platformFeeRevenue, // NEW
                'total_revenue' => $totalRevenue, // NEW
            ],
            'expenses' => [
                'payment_gateway_fees' => $paymentGatewayExpenses, // NEW
                'commission_reversals' => $commissionReversals,
                'total_expenses' => $totalExpenses, // NEW
            ],
            'deductions' => [
                'commission_reversals' => $commissionReversals,
                'refund_count' => $refundSummary['count'],
            ],
            'gross_profit' => $grossProfit,
            'settlements_paid' => $settlementsPaid,
            'net_profit' => $netProfit,
            'margin_percentage' => $gmv > 0 ? round(($netProfit / $gmv) * 100, 2) : 0,
            'profit_margin_on_revenue' => $totalRevenue > 0 ? round(($netProfit / $totalRevenue) * 100, 2) : 0, // NEW
        ];
    }

    /**
     * Get revenue trend data for charts.
     *
     * @return array<string, float> [date => amount]
     */
    public function getRevenueTrend(Carbon $from, Carbon $to, string $granularity = 'daily'): array
    {
        $dateFormat = match ($granularity) {
            'weekly' => '%Y-%u',
            'monthly' => '%Y-%m',
            default => '%Y-%m-%d',
        };

        $results = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.payment_status', 'paid')
            ->whereIn('orders.status', ['confirmed', 'processing', 'shipped', 'delivered'])
            ->whereBetween('orders.created_at', [$from->startOfDay(), $to->endOfDay()])
            ->groupBy('date_group')
            ->select(
                DB::raw("DATE_FORMAT(orders.created_at, '{$dateFormat}') as date_group"),
                DB::raw('SUM(order_items.commission_amount) as commission'),
            )
            ->orderBy('date_group')
            ->pluck('commission', 'date_group')
            ->toArray();

        // Fill in missing dates for daily granularity
        if ($granularity === 'daily') {
            $filled = [];
            $current = $from->copy();
            while ($current->lte($to)) {
                $key = $current->format('Y-m-d');
                $filled[$key] = (float) ($results[$key] ?? 0);
                $current->addDay();
            }
            return $filled;
        }

        return array_map('floatval', $results);
    }

    /**
     * Get top merchants by GMV and commission.
     */
    public function getTopMerchants(Carbon $from, Carbon $to, int $limit = 5): array
    {
        return DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('merchants', 'merchants.id', '=', 'order_items.merchant_id')
            ->where('orders.payment_status', 'paid')
            ->whereIn('orders.status', ['confirmed', 'processing', 'shipped', 'delivered'])
            ->whereBetween('orders.created_at', [$from->startOfDay(), $to->endOfDay()])
            ->groupBy('order_items.merchant_id', 'merchants.business_name')
            ->select(
                'order_items.merchant_id',
                'merchants.business_name',
                DB::raw('COUNT(DISTINCT orders.id) as order_count'),
                DB::raw('SUM(order_items.subtotal) as gmv'),
                DB::raw('SUM(order_items.commission_amount) as commission'),
            )
            ->orderByDesc('gmv')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Generate a reconciliation report for a period.
     */
    public function generateReconciliation(Carbon $from, Carbon $to): Reconciliation
    {
        $gmv = $this->getGmv($from, $to);

        $totalOrders = Order::where('payment_status', 'paid')
            ->whereIn('status', ['confirmed', 'processing', 'shipped', 'delivered'])
            ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
            ->count();

        $commissionEarned = (float) DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.payment_status', 'paid')
            ->whereIn('orders.status', ['confirmed', 'processing', 'shipped', 'delivered'])
            ->whereBetween('orders.created_at', [$from->startOfDay(), $to->endOfDay()])
            ->sum('order_items.commission_amount');

        $taxRate = 5.00;
        $taxCollected = round($gmv - ($gmv / (1 + ($taxRate / 100))), 2);

        $refundSummary = $this->getRefundSummary($from, $to);
        $totalRefunds = $refundSummary['total_refunded'];

        $settlementsPaid = (float) Settlement::where('status', 'paid')
            ->whereBetween('paid_at', [$from->startOfDay(), $to->endOfDay()])
            ->sum('merchant_payout');

        $debitNotes = (float) MerchantDebitNote::whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
            ->sum('recovery_amount');

        // Net platform revenue = commission earned - commission reversed from refunds
        $netRevenue = round($commissionEarned - $refundSummary['commission_reversed'], 2);

        // Discrepancy check: Expected merchant payables vs actual settlements
        $expectedPayables = round($gmv - $commissionEarned - $totalRefunds, 2);
        $discrepancy = round($expectedPayables - $settlementsPaid, 2);

        $notes = [];
        if (abs($discrepancy) > 0.01) {
            $pendingSettlements = (float) Settlement::where('status', 'pending')
                ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
                ->sum('merchant_payout');

            $unsettledOrders = Order::where('payment_status', 'paid')
                ->where('status', 'delivered')
                ->whereDoesntHave('settlementItems')
                ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
                ->count();

            $notes[] = "Discrepancy of AED " . number_format(abs($discrepancy), 2);
            $notes[] = "Pending settlements: AED " . number_format($pendingSettlements, 2);
            $notes[] = "Unsettled delivered orders: {$unsettledOrders}";
        }

        return Reconciliation::create([
            'reconciliation_number' => Reconciliation::generateReconciliationNumber(),
            'period_start' => $from,
            'period_end' => $to,
            'total_orders' => $totalOrders,
            'total_gmv' => $gmv,
            'total_commission_earned' => $commissionEarned,
            'total_tax_collected' => $taxCollected,
            'total_refunds_issued' => $totalRefunds,
            'total_settlements_paid' => $settlementsPaid,
            'total_debit_notes' => $debitNotes,
            'net_platform_revenue' => $netRevenue,
            'discrepancy_amount' => $discrepancy,
            'discrepancy_notes' => !empty($notes) ? implode("\n", $notes) : null,
            'status' => 'draft',
        ]);
    }

    /**
     * Get aggregated dashboard stats for a period.
     */
    public function getDashboardStats(Carbon $from, Carbon $to): array
    {
        $gmv = $this->getGmv($from, $to);
        $commissionRevenue = $this->getCommissionRevenue($from, $to);
        $refundSummary = $this->getRefundSummary($from, $to);
        $taxSummary = $this->getTaxSummary($from, $to);

        $pendingPayables = (float) Settlement::where('status', 'pending')
            ->sum('merchant_payout');

        $orderCount = Order::where('payment_status', 'paid')
            ->whereIn('status', ['confirmed', 'processing', 'shipped', 'delivered'])
            ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
            ->count();

        $settledCount = Settlement::where('status', 'paid')
            ->whereBetween('paid_at', [$from->startOfDay(), $to->endOfDay()])
            ->count();

        return [
            'gmv' => $gmv,
            'commission_revenue' => $commissionRevenue,
            'total_refunds' => $refundSummary['total_refunded'],
            'net_revenue' => $this->getNetRevenue($from, $to),
            'tax_collected' => $taxSummary['output_vat'],
            'pending_payables' => $pendingPayables,
            'order_count' => $orderCount,
            'settled_count' => $settledCount,
        ];
    }
}
