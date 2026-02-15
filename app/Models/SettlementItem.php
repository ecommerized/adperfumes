<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SettlementItem extends Model
{
    protected $fillable = [
        'settlement_id', 'order_id', 'order_total', 'order_subtotal',
        'commission_rate_applied', 'commission_source', 'commission_amount',
        'commission_tax', 'merchant_payout',
    ];

    protected $casts = [
        'order_total' => 'decimal:2',
        'order_subtotal' => 'decimal:2',
        'commission_rate_applied' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'commission_tax' => 'decimal:2',
        'merchant_payout' => 'decimal:2',
    ];

    public function settlement(): BelongsTo
    {
        return $this->belongsTo(Settlement::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
