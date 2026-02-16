<?php

namespace App\Models;

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
}
