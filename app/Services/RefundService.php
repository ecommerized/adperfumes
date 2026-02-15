<?php

namespace App\Services;

use App\Models\CreditNote;
use App\Models\MerchantDebitNote;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Refund;
use App\Models\RefundItem;
use App\Models\SettlementItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RefundService
{
    /**
     * Create a refund for an order.
     *
     * @param array $itemsToRefund Array of ['order_item_id' => ..., 'quantity' => ..., 'reason' => ...]
     */
    public function createRefund(
        Order $order,
        array $itemsToRefund,
        string $type = 'partial',
        ?int $merchantId = null,
        ?string $reasonCategory = null,
        ?string $notes = null,
        ?int $initiatedBy = null,
    ): Refund {
        return DB::transaction(function () use ($order, $itemsToRefund, $type, $merchantId, $reasonCategory, $notes, $initiatedBy) {
            $refundSubtotal = 0;
            $refundTax = 0;
            $commissionToReverse = 0;

            // Calculate refund amounts
            $refundItemsData = [];
            foreach ($itemsToRefund as $itemData) {
                $orderItem = OrderItem::findOrFail($itemData['order_item_id']);
                $qty = $itemData['quantity'] ?? $orderItem->quantity;
                $unitPrice = (float) $orderItem->price;
                $lineSubtotal = round($unitPrice * $qty, 2);
                $taxRate = 5.00;
                $lineExclTax = round($lineSubtotal / (1 + ($taxRate / 100)), 2);
                $lineTax = round($lineSubtotal - $lineExclTax, 2);
                $lineCommission = round($lineSubtotal * ($orderItem->commission_rate ?? 0) / 100, 2);

                $refundSubtotal += $lineSubtotal;
                $refundTax += $lineTax;
                $commissionToReverse += $lineCommission;

                $refundItemsData[] = [
                    'order_item_id' => $orderItem->id,
                    'product_id' => $orderItem->product_id,
                    'product_name' => $orderItem->product_name,
                    'quantity_refunded' => $qty,
                    'unit_price' => $unitPrice,
                    'line_subtotal' => $lineSubtotal,
                    'line_tax' => $lineTax,
                    'line_refund_total' => $lineSubtotal,
                    'item_condition' => $itemData['condition'] ?? 'unopened',
                ];
            }

            $effectiveMerchantId = $merchantId ?? $this->resolveMerchantId($itemsToRefund);
            $isSettled = $this->isOrderSettled($order, $effectiveMerchantId);

            $refund = Refund::create([
                'refund_number' => Refund::generateRefundNumber(),
                'order_id' => $order->id,
                'merchant_id' => $effectiveMerchantId,
                'type' => $type,
                'reason_category' => $reasonCategory,
                'refund_subtotal' => $refundSubtotal,
                'refund_tax' => $refundTax,
                'refund_total' => $refundSubtotal,
                'commission_to_reverse' => $commissionToReverse,
                'is_post_settlement' => $isSettled,
                'merchant_recovery_amount' => $isSettled ? round($refundSubtotal - $commissionToReverse, 2) : 0,
                'merchant_recovery_status' => $isSettled ? 'pending' : null,
                'status' => 'pending',
                'initiated_by' => $initiatedBy,
                'notes' => $notes,
            ]);

            foreach ($refundItemsData as $itemData) {
                $refund->items()->create($itemData);
            }

            $refund->logTransaction('refund_created', [], [
                'refund_number' => $refund->refund_number,
                'type' => $type,
                'refund_total' => $refundSubtotal,
            ]);

            return $refund;
        });
    }

    /**
     * Approve a refund.
     */
    public function approveRefund(Refund $refund, int $approvedBy): void
    {
        $refund->update([
            'status' => 'approved',
            'approved_by' => $approvedBy,
            'approved_at' => now(),
        ]);

        $refund->logTransaction('refund_approved', ['status' => 'pending'], ['status' => 'approved']);
    }

    /**
     * Process an approved refund: restore stock, create credit note, handle debit note if post-settlement.
     */
    public function processRefund(Refund $refund): void
    {
        DB::transaction(function () use ($refund) {
            // Restore stock
            foreach ($refund->items as $item) {
                if ($item->product_id && !$item->stock_restored) {
                    $product = $item->product;
                    if ($product) {
                        $product->increment('stock', $item->quantity_refunded);
                    }
                    $item->update(['stock_restored' => true]);
                }
            }

            // Create credit note
            $invoice = $refund->order->invoices()
                ->where('merchant_id', $refund->merchant_id)
                ->first();

            CreditNote::create([
                'credit_note_number' => CreditNote::generateCreditNoteNumber(),
                'order_id' => $refund->order_id,
                'refund_id' => $refund->id,
                'invoice_id' => $invoice?->id,
                'merchant_id' => $refund->merchant_id,
                'type' => $refund->type === 'full' ? 'full_refund' : 'partial_refund',
                'subtotal' => $refund->refund_subtotal,
                'tax_amount' => $refund->refund_tax,
                'total' => $refund->refund_total,
                'reason' => $refund->reason_category,
                'status' => 'issued',
            ]);

            // Create merchant debit note if post-settlement
            if ($refund->is_post_settlement && $refund->merchant_recovery_amount > 0) {
                $lastSettlement = SettlementItem::where('order_id', $refund->order_id)
                    ->join('settlements', 'settlements.id', '=', 'settlement_items.settlement_id')
                    ->where('settlements.merchant_id', $refund->merchant_id)
                    ->orderBy('settlements.id', 'desc')
                    ->first();

                MerchantDebitNote::create([
                    'debit_note_number' => MerchantDebitNote::generateDebitNoteNumber($refund->merchant_id),
                    'refund_id' => $refund->id,
                    'merchant_id' => $refund->merchant_id,
                    'settlement_id' => $lastSettlement?->settlement_id,
                    'recovery_amount' => $refund->merchant_recovery_amount,
                    'commission_reversed' => $refund->commission_to_reverse,
                    'description' => "Recovery for refund #{$refund->refund_number}",
                    'status' => 'pending',
                ]);

                $refund->update(['merchant_recovery_status' => 'debit_note_issued']);
            }

            // Update refund status
            $refund->update([
                'status' => 'processed',
                'processed_at' => now(),
            ]);

            // Update order status if full refund
            if ($refund->type === 'full') {
                $refund->order->update(['status' => 'refunded']);
            }

            $refund->logTransaction('refund_processed', ['status' => 'approved'], ['status' => 'processed']);
        });
    }

    /**
     * Check if an order's merchant portion has already been settled.
     */
    public function isOrderSettled(Order $order, ?int $merchantId): bool
    {
        if (!$merchantId) {
            return false;
        }

        return SettlementItem::where('order_id', $order->id)
            ->whereHas('settlement', fn ($q) => $q->where('merchant_id', $merchantId))
            ->exists();
    }

    /**
     * Resolve merchant ID from order items.
     */
    private function resolveMerchantId(array $itemsToRefund): ?int
    {
        if (empty($itemsToRefund)) {
            return null;
        }

        $orderItem = OrderItem::find($itemsToRefund[0]['order_item_id']);

        return $orderItem?->merchant_id;
    }
}
