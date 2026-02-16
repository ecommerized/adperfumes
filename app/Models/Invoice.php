<?php

namespace App\Models;

use App\Services\FtaQrCodeService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'invoice_number', 'order_id', 'merchant_id',
        'customer_name', 'customer_email', 'customer_phone', 'customer_address',
        'merchant_name', 'merchant_trn',
        'subtotal', 'tax_rate', 'tax_amount', 'shipping_amount', 'discount_amount',
        'total', 'commission_amount', 'net_merchant_amount', 'currency',
        'status', 'pdf_path', 'sent_at', 'due_date',
        'qr_code_data', // FTA QR code Base64 TLV data
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'net_merchant_amount' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function creditNotes(): HasMany
    {
        return $this->hasMany(CreditNote::class);
    }

    public static function generateInvoiceNumber(): string
    {
        $lastInvoice = static::withTrashed()->orderBy('id', 'desc')->first();
        $nextNumber = $lastInvoice ? $lastInvoice->id + 1 : 1;

        return 'INV-' . now()->format('Ym') . '-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Generate FTA-compliant QR code for this invoice.
     *
     * @return array QR code data with base64, svg, and data_url keys
     */
    public function generateFtaQrCode(): array
    {
        $qrService = app(FtaQrCodeService::class);

        // Get seller information
        $sellerName = $this->merchant_name ?? config('app.name', 'AD Perfumes');
        $vatNumber = $this->merchant_trn ?? config('company.vat_number', '');

        // Get invoice data
        $timestamp = $this->created_at->toIso8601String();
        $invoiceTotal = (float) $this->total;
        $vatAmount = (float) $this->tax_amount;

        // Generate QR code
        $qrData = $qrService->generateQrCodeData(
            $sellerName,
            $vatNumber,
            $timestamp,
            $invoiceTotal,
            $vatAmount
        );

        // Store QR code data in invoice
        if (!$this->qr_code_data) {
            $this->update(['qr_code_data' => $qrData]);
        }

        return [
            'base64' => $qrData,
            'svg' => $qrService->generateQrCodeSvg($qrData, 200),
            'data_url' => $qrService->generateQrCodeDataUrl($qrData, 200),
        ];
    }

    /**
     * Get cached QR code or generate new one.
     *
     * @return array QR code data
     */
    public function getQrCode(): array
    {
        if ($this->qr_code_data) {
            $qrService = app(FtaQrCodeService::class);
            return [
                'base64' => $this->qr_code_data,
                'svg' => $qrService->generateQrCodeSvg($this->qr_code_data, 200),
                'data_url' => $qrService->generateQrCodeDataUrl($this->qr_code_data, 200),
            ];
        }

        return $this->generateFtaQrCode();
    }

    /**
     * Validate invoice data for FTA compliance.
     *
     * @return array Validation result
     */
    public function validateFtaCompliance(): array
    {
        $errors = [];

        // Check required fields
        if (empty($this->invoice_number)) {
            $errors[] = 'Invoice number is required';
        }

        if (empty($this->merchant_trn) && empty(config('company.vat_number'))) {
            $errors[] = 'VAT registration number (TRN) is required';
        }

        if (empty($this->customer_name)) {
            $errors[] = 'Customer name is required';
        }

        if ($this->tax_amount <= 0 && $this->tax_rate > 0) {
            $errors[] = 'VAT amount must be greater than zero when VAT rate is applied';
        }

        if ($this->total <= 0) {
            $errors[] = 'Invoice total must be greater than zero';
        }

        // Validate VAT calculation
        $expectedVatAmount = round($this->subtotal * ($this->tax_rate / 100), 2);
        if (abs($this->tax_amount - $expectedVatAmount) > 0.02) {
            $errors[] = 'VAT amount does not match calculated VAT from subtotal';
        }

        // Validate total
        $expectedTotal = round($this->subtotal + $this->tax_amount + $this->shipping_amount - $this->discount_amount, 2);
        if (abs($this->total - $expectedTotal) > 0.02) {
            $errors[] = 'Invoice total does not match sum of components';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Check if invoice is FTA compliant.
     *
     * @return bool
     */
    public function isFtaCompliant(): bool
    {
        $validation = $this->validateFtaCompliance();
        return $validation['valid'];
    }

    /**
     * Get invoice data formatted for QR code generation.
     *
     * @return array
     */
    public function toQrCodeData(): array
    {
        return [
            'seller_name' => $this->merchant_name ?? config('app.name'),
            'seller_vat_number' => $this->merchant_trn ?? config('company.vat_number'),
            'merchant_trn' => $this->merchant_trn,
            'created_at' => $this->created_at->toIso8601String(),
            'total_amount' => $this->total,
            'total' => $this->total,
            'tax_amount' => $this->tax_amount,
        ];
    }
}
