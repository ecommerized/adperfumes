<?php

namespace App\Services;

use App\Models\CreditNote;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Order;
use App\Models\Refund;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class InvoiceService
{
    /**
     * Generate invoices for an order — one per merchant (multi-merchant support).
     *
     * @return Invoice[]
     */
    public function generateInvoicesForOrder(Order $order): array
    {
        $invoices = [];
        $order->load('items');

        // Group order items by merchant
        $merchantGroups = $order->items->groupBy('merchant_id');

        DB::transaction(function () use ($order, $merchantGroups, &$invoices) {
            foreach ($merchantGroups as $merchantId => $items) {
                // Skip if invoice already exists for this merchant + order
                if (Invoice::where('order_id', $order->id)->where('merchant_id', $merchantId)->exists()) {
                    continue;
                }

                $merchant = $merchantId ? \App\Models\Merchant::find($merchantId) : null;
                $taxRate = 5.00;

                $subtotalInclTax = $items->sum('subtotal');
                $subtotalExclTax = round($subtotalInclTax / (1 + ($taxRate / 100)), 2);
                $taxAmount = round($subtotalInclTax - $subtotalExclTax, 2);
                $totalCommission = $items->sum('commission_amount');

                $invoice = Invoice::create([
                    'invoice_number' => Invoice::generateInvoiceNumber(),
                    'order_id' => $order->id,
                    'merchant_id' => $merchantId ?: null,
                    'customer_name' => $order->full_name,
                    'customer_email' => $order->email,
                    'customer_phone' => $order->phone,
                    'customer_address' => implode(', ', array_filter([
                        $order->address, $order->city, $order->country, $order->postal_code,
                    ])),
                    'merchant_name' => $merchant?->business_name,
                    'merchant_trn' => $merchant?->tax_registration,
                    'subtotal' => $subtotalExclTax,
                    'tax_rate' => $taxRate,
                    'tax_amount' => $taxAmount,
                    'shipping_amount' => 0, // Shipping allocated to first merchant or split later
                    'discount_amount' => 0,
                    'total' => $subtotalInclTax,
                    'commission_amount' => $totalCommission,
                    'net_merchant_amount' => round($subtotalInclTax - $totalCommission, 2),
                    'currency' => $order->currency ?? 'AED',
                    'status' => 'issued',
                ]);

                foreach ($items as $item) {
                    $unitExclTax = round((float) $item->price / (1 + ($taxRate / 100)), 2);
                    $lineTax = round(((float) $item->price - $unitExclTax) * $item->quantity, 2);

                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'description' => $item->product_name,
                        'sku' => $item->product_slug,
                        'quantity' => $item->quantity,
                        'unit_price' => $unitExclTax,
                        'unit_price_incl_tax' => $item->price,
                        'tax_rate' => $taxRate,
                        'tax_amount' => $lineTax,
                        'line_total' => $item->subtotal,
                    ]);
                }

                $invoices[] = $invoice;
            }
        });

        Log::info('Generated ' . count($invoices) . ' invoices for order #' . $order->order_number);

        return $invoices;
    }

    /**
     * Generate a credit note for a refund.
     */
    public function generateCreditNote(Order $order, Refund $refund): CreditNote
    {
        $invoice = $order->invoices()
            ->where('merchant_id', $refund->merchant_id)
            ->first();

        return CreditNote::create([
            'credit_note_number' => CreditNote::generateCreditNoteNumber(),
            'order_id' => $order->id,
            'refund_id' => $refund->id,
            'invoice_id' => $invoice?->id,
            'merchant_id' => $refund->merchant_id,
            'type' => $refund->type === 'full' ? 'full_refund' : 'partial_refund',
            'subtotal' => $refund->refund_subtotal,
            'tax_amount' => $refund->refund_tax,
            'total' => $refund->refund_total,
            'reason' => $refund->reason_category ?? 'Refund',
            'status' => 'issued',
        ]);
    }

    /**
     * Generate PDF for an invoice and store it.
     */
    public function generateInvoicePdf(Invoice $invoice): string
    {
        $invoice->load(['items', 'order', 'merchant']);

        $settings = app(\App\Services\SettingsService::class);
        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'storeName' => $settings->get('store_name', 'AD Perfumes'),
            'storeUrl' => config('app.url'),
        ]);

        $path = 'invoices/' . $invoice->invoice_number . '.pdf';
        Storage::put($path, $pdf->output());

        $invoice->update(['pdf_path' => $path]);

        return $path;
    }

    /**
     * Generate PDF for a credit note and store it.
     */
    public function generateCreditNotePdf(CreditNote $creditNote): string
    {
        $creditNote->load(['order', 'refund', 'invoice', 'merchant']);

        $settings = app(\App\Services\SettingsService::class);
        $pdf = Pdf::loadView('pdf.credit-note', [
            'creditNote' => $creditNote,
            'storeName' => $settings->get('store_name', 'AD Perfumes'),
        ]);

        $path = 'credit-notes/' . $creditNote->credit_note_number . '.pdf';
        Storage::put($path, $pdf->output());

        $creditNote->update(['pdf_path' => $path]);

        return $path;
    }
}
