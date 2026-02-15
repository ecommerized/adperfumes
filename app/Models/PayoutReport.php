<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayoutReport extends Model
{
    protected $fillable = [
        'report_number', 'settlement_id', 'merchant_id', 'payout_date',
        'period_start', 'period_end', 'total_orders', 'gross_revenue',
        'total_tax_collected', 'total_commission', 'commission_tax',
        'net_payout', 'pdf_path', 'status', 'sent_at',
    ];

    protected $casts = [
        'payout_date' => 'date',
        'period_start' => 'date',
        'period_end' => 'date',
        'sent_at' => 'datetime',
        'gross_revenue' => 'decimal:2',
        'total_tax_collected' => 'decimal:2',
        'total_commission' => 'decimal:2',
        'commission_tax' => 'decimal:2',
        'net_payout' => 'decimal:2',
    ];

    public function settlement(): BelongsTo
    {
        return $this->belongsTo(Settlement::class);
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }
}
