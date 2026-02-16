<?php

namespace App\Models;

use App\Traits\HasTransactionLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Settlement extends Model
{
    use HasTransactionLog;

    protected $fillable = [
        'merchant_id', 'payout_date', 'total_order_amount', 'total_subtotal',
        'total_tax', 'commission_amount', 'commission_tax', 'total_commission',
        'merchant_payout', 'deductions', 'net_payout', 'status',
        'transaction_reference', 'notes', 'paid_at',
        'total_payment_gateway_fees', 'total_platform_fees',
    ];

    protected $casts = [
        'payout_date' => 'date',
        'paid_at' => 'datetime',
        'total_order_amount' => 'decimal:2',
        'total_subtotal' => 'decimal:2',
        'total_tax' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'commission_tax' => 'decimal:2',
        'total_commission' => 'decimal:2',
        'merchant_payout' => 'decimal:2',
        'deductions' => 'decimal:2',
        'net_payout' => 'decimal:2',
        'total_payment_gateway_fees' => 'decimal:2',
        'total_platform_fees' => 'decimal:2',
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SettlementItem::class);
    }

    public function payoutReport(): HasOne
    {
        return $this->hasOne(PayoutReport::class);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function markAsPaid(string $transactionReference = null): void
    {
        $this->update([
            'status' => 'paid',
            'paid_at' => now(),
            'transaction_reference' => $transactionReference,
        ]);
    }
}
