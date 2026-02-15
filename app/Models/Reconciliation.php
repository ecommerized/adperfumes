<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reconciliation extends Model
{
    protected $fillable = [
        'reconciliation_number', 'period_start', 'period_end',
        'total_orders', 'total_gmv', 'total_commission_earned',
        'total_tax_collected', 'total_refunds_issued',
        'total_settlements_paid', 'total_debit_notes',
        'net_platform_revenue', 'discrepancy_amount', 'discrepancy_notes',
        'status', 'reviewed_by', 'reviewed_at', 'pdf_path',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'reviewed_at' => 'datetime',
        'total_gmv' => 'decimal:2',
        'total_commission_earned' => 'decimal:2',
        'total_tax_collected' => 'decimal:2',
        'total_refunds_issued' => 'decimal:2',
        'total_settlements_paid' => 'decimal:2',
        'total_debit_notes' => 'decimal:2',
        'net_platform_revenue' => 'decimal:2',
        'discrepancy_amount' => 'decimal:2',
    ];

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public static function generateReconciliationNumber(): string
    {
        $last = static::orderBy('id', 'desc')->first();
        $nextNumber = $last ? $last->id + 1 : 1;

        return 'REC-' . now()->format('Ym') . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
