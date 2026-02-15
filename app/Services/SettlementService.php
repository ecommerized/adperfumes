<?php

namespace App\Services;

use App\Models\Merchant;
use App\Models\Order;
use App\Models\PayoutReport;
use App\Models\Settlement;
use App\Models\SettlementItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SettlementService
{
    const PAYOUT_DAYS = [1, 8, 15, 22];
    const ELIGIBILITY_DAYS = 15;

    /**
     * Calculate settlement eligibility for delivered orders.
     * Sets settlement_eligible_at = delivered_at + 15 days.
     */
    public function calculateEligibility(): int
    {
        $updated = Order::where('status', 'delivered')
            ->whereNotNull('delivered_at')
            ->whereNull('settlement_eligible_at')
            ->update([
                'settlement_eligible_at' => DB::raw('DATE_ADD(delivered_at, INTERVAL ' . self::ELIGIBILITY_DAYS . ' DAY)'),
            ]);

        Log::info("Settlement eligibility calculated for {$updated} orders.");

        return $updated;
    }

    /**
     * Generate settlements for a specific payout date.
     * Groups eligible order items by merchant.
     */
    public function generateSettlements(?Carbon $payoutDate = null): array
    {
        $payoutDate = $payoutDate ?? Carbon::today();
        $settlements = [];

        // Find all eligible order items not yet settled
        $eligibleItems = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->leftJoin('settlement_items', 'settlement_items.order_id', '=', 'orders.id')
            ->whereNull('settlement_items.id')
            ->where('orders.status', 'delivered')
            ->where('orders.payment_status', 'paid')
            ->whereNotNull('orders.settlement_eligible_at')
            ->where('orders.settlement_eligible_at', '<=', $payoutDate)
            ->whereNotNull('order_items.merchant_id')
            ->select(
                'order_items.merchant_id',
                'order_items.order_id',
                'order_items.subtotal as item_subtotal',
                'order_items.price',
                'order_items.quantity',
                'order_items.commission_rate',
                'order_items.commission_amount',
            )
            ->get();

        if ($eligibleItems->isEmpty()) {
            Log::info('No eligible items for settlement on ' . $payoutDate->toDateString());
            return [];
        }

        // Group by merchant
        $grouped = $eligibleItems->groupBy('merchant_id');

        DB::beginTransaction();
        try {
            foreach ($grouped as $merchantId => $items) {
                $totalOrderAmount = $items->sum('item_subtotal');
                $taxRate = 5.00; // UAE VAT
                $totalSubtotal = round($totalOrderAmount / (1 + ($taxRate / 100)), 2);
                $totalTax = round($totalOrderAmount - $totalSubtotal, 2);
                $commissionAmount = $items->sum('commission_amount');
                $commissionTax = round($commissionAmount * $taxRate / 100, 2);
                $totalCommission = round($commissionAmount + $commissionTax, 2);
                $merchantPayout = round($totalOrderAmount - $totalCommission, 2);

                $settlement = Settlement::create([
                    'merchant_id' => $merchantId,
                    'payout_date' => $payoutDate,
                    'total_order_amount' => $totalOrderAmount,
                    'total_subtotal' => $totalSubtotal,
                    'total_tax' => $totalTax,
                    'commission_amount' => $commissionAmount,
                    'commission_tax' => $commissionTax,
                    'total_commission' => $totalCommission,
                    'merchant_payout' => $merchantPayout,
                    'status' => 'pending',
                ]);

                // Group items by order for settlement_items
                $orderGroups = $items->groupBy('order_id');
                foreach ($orderGroups as $orderId => $orderItems) {
                    $orderTotal = $orderItems->sum('item_subtotal');
                    $orderCommission = $orderItems->sum('commission_amount');
                    $orderCommissionTax = round($orderCommission * $taxRate / 100, 2);
                    $orderSubtotal = round($orderTotal / (1 + ($taxRate / 100)), 2);

                    SettlementItem::create([
                        'settlement_id' => $settlement->id,
                        'order_id' => $orderId,
                        'order_total' => $orderTotal,
                        'order_subtotal' => $orderSubtotal,
                        'commission_rate_applied' => $orderItems->first()->commission_rate,
                        'commission_source' => 'order_item',
                        'commission_amount' => $orderCommission,
                        'commission_tax' => $orderCommissionTax,
                        'merchant_payout' => round($orderTotal - $orderCommission - $orderCommissionTax, 2),
                    ]);
                }

                $settlement->logTransaction('settlement_created', [], [
                    'merchant_id' => $merchantId,
                    'payout_date' => $payoutDate->toDateString(),
                    'merchant_payout' => $merchantPayout,
                    'orders_count' => $orderGroups->count(),
                ]);

                $settlements[] = $settlement;
            }

            DB::commit();
            Log::info('Generated ' . count($settlements) . ' settlements for ' . $payoutDate->toDateString());
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Settlement generation failed: ' . $e->getMessage());
            throw $e;
        }

        return $settlements;
    }

    /**
     * Get the next payout date from today.
     */
    public function getNextPayoutDate(): Carbon
    {
        $today = Carbon::today();
        $currentDay = $today->day;

        foreach (self::PAYOUT_DAYS as $day) {
            if ($day > $currentDay) {
                return $today->copy()->day($day);
            }
        }

        // Next month's first payout day
        return $today->copy()->addMonthNoOverflow()->day(self::PAYOUT_DAYS[0]);
    }

    /**
     * Mark a settlement as paid.
     */
    public function markAsPaid(Settlement $settlement, string $transactionRef): void
    {
        $oldValues = ['status' => $settlement->status];

        $settlement->update([
            'status' => 'paid',
            'transaction_reference' => $transactionRef,
            'paid_at' => now(),
        ]);

        $settlement->logTransaction('settlement_paid', $oldValues, [
            'status' => 'paid',
            'transaction_reference' => $transactionRef,
        ]);
    }

    /**
     * Generate a payout report PDF for a settlement.
     */
    public function generatePayoutReport(Settlement $settlement): PayoutReport
    {
        $settlement->load(['merchant', 'items']);

        $report = PayoutReport::create([
            'report_number' => 'PR-' . now()->format('Ym') . '-' . str_pad($settlement->id, 5, '0', STR_PAD_LEFT),
            'settlement_id' => $settlement->id,
            'merchant_id' => $settlement->merchant_id,
            'payout_date' => $settlement->payout_date,
            'period_start' => $settlement->items->min(fn ($item) => $item->order->created_at)?->toDateString() ?? $settlement->payout_date,
            'period_end' => $settlement->payout_date,
            'total_orders' => $settlement->items->count(),
            'gross_revenue' => $settlement->total_order_amount,
            'total_tax_collected' => $settlement->total_tax,
            'total_commission' => $settlement->commission_amount,
            'commission_tax' => $settlement->commission_tax,
            'net_payout' => $settlement->merchant_payout,
            'status' => 'generated',
        ]);

        // Generate PDF
        $settings = app(\App\Services\SettingsService::class);
        $pdf = Pdf::loadView('pdf.payout-report', [
            'report' => $report->load(['merchant', 'settlement']),
            'storeName' => $settings->get('store_name', 'AD Perfumes'),
        ]);

        $path = 'payout-reports/' . $report->report_number . '.pdf';
        Storage::put($path, $pdf->output());

        $report->update(['pdf_path' => $path]);

        return $report;
    }
}
