<?php

namespace App\Models;

use App\Traits\HasTransactionLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Refund extends Model
{
    use SoftDeletes, HasTransactionLog;

    const TYPES = ['full', 'partial', 'exchange'];
    const REASON_CATEGORIES = [
        'damaged_in_transit', 'wrong_item_sent', 'defective_product',
        'not_as_described', 'sealed_unopened_return', 'allergic_reaction',
        'changed_mind', 'cancelled_before_shipping', 'other',
    ];
    const STATUSES = [
        'pending', 'approved', 'processing', 'completed',
        'rejected', 'recovery_pending', 'fully_resolved',
    ];

    protected $fillable = [
        'refund_number', 'order_id', 'merchant_id', 'type', 'reason_category',
        'reason_details', 'refund_subtotal', 'refund_tax', 'refund_total',
        'commission_reversed', 'commission_tax_reversed', 'total_commission_reversed',
        'merchant_recovery_amount', 'is_settled', 'recovery_method',
        'recovery_settlement_id', 'is_recovery_completed', 'status',
        'initiated_by', 'approved_by', 'approved_at', 'completed_at',
        'transaction_reference',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'completed_at' => 'datetime',
        'is_settled' => 'boolean',
        'is_recovery_completed' => 'boolean',
        'refund_subtotal' => 'decimal:2',
        'refund_tax' => 'decimal:2',
        'refund_total' => 'decimal:2',
        'commission_reversed' => 'decimal:2',
        'commission_tax_reversed' => 'decimal:2',
        'total_commission_reversed' => 'decimal:2',
        'merchant_recovery_amount' => 'decimal:2',
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
        return $this->hasMany(RefundItem::class);
    }

    public function creditNote(): HasOne
    {
        return $this->hasOne(CreditNote::class);
    }

    public function debitNote(): HasOne
    {
        return $this->hasOne(MerchantDebitNote::class);
    }

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function recoverySettlement(): BelongsTo
    {
        return $this->belongsTo(Settlement::class, 'recovery_settlement_id');
    }

    public static function generateRefundNumber(): string
    {
        $lastRefund = static::withTrashed()->orderBy('id', 'desc')->first();
        $nextNumber = $lastRefund ? $lastRefund->id + 1 : 1;

        return 'RFD-' . now()->format('Ym') . '-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
    }
}
