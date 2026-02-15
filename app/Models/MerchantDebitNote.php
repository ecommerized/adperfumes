<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MerchantDebitNote extends Model
{
    protected $fillable = [
        'debit_note_number', 'refund_id', 'merchant_id', 'settlement_id',
        'recovery_settlement_id', 'recovery_amount', 'commission_reversed',
        'description', 'pdf_path', 'status', 'applied_at',
    ];

    protected $casts = [
        'applied_at' => 'datetime',
        'recovery_amount' => 'decimal:2',
        'commission_reversed' => 'decimal:2',
    ];

    public function refund(): BelongsTo
    {
        return $this->belongsTo(Refund::class);
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function settlement(): BelongsTo
    {
        return $this->belongsTo(Settlement::class);
    }

    public function recoverySettlement(): BelongsTo
    {
        return $this->belongsTo(Settlement::class, 'recovery_settlement_id');
    }

    public static function generateDebitNoteNumber(int $merchantId): string
    {
        $last = static::where('merchant_id', $merchantId)->orderBy('id', 'desc')->first();
        $nextNumber = $last ? $last->id + 1 : 1;

        return 'DN-' . now()->format('Ym') . '-M' . str_pad($merchantId, 3, '0', STR_PAD_LEFT) . '-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
    }
}
