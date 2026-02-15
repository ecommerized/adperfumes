<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CreditNote extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'credit_note_number', 'order_id', 'refund_id', 'invoice_id', 'merchant_id',
        'customer_name', 'customer_email', 'merchant_name', 'merchant_trn',
        'type', 'subtotal', 'tax_rate', 'tax_amount', 'total_amount', 'currency',
        'reason', 'pdf_path', 'status', 'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function refund(): BelongsTo
    {
        return $this->belongsTo(Refund::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public static function generateCreditNoteNumber(): string
    {
        $last = static::withTrashed()->orderBy('id', 'desc')->first();
        $nextNumber = $last ? $last->id + 1 : 1;

        return 'CN-' . now()->format('Ym') . '-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
    }
}
