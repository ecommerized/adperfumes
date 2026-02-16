<?php

namespace App\Models;

use App\Traits\HasTransactionLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Order extends Model
{
    use HasTransactionLog;

    protected $fillable = [
        'order_number',
        'customer_id',
        'email',
        'phone',
        'first_name',
        'last_name',
        'address',
        'city',
        'country',
        'postal_code',
        'subtotal',
        'shipping',
        'discount',
        'grand_total',
        'currency',
        'discount_code',
        'payment_method',
        'payment_status',
        'payment_id',
        'tamara_order_id',
        'payment_response',
        'shipping_method',
        'tracking_number',
        'aramex_shipment_id',
        'awb_label_url',
        'status',
        'customer_notes',
        'admin_notes',
        'delivered_at',
        'settlement_eligible_at',
        'cancelled_at',
        'cancellation_reason',
        'cancelled_by',
        'return_requested_at',
        'return_reason',
        'return_reason_category',
        'return_notes',
        'is_refund_eligible',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'shipping' => 'decimal:2',
        'discount' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'delivered_at' => 'datetime',
        'settlement_eligible_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'return_requested_at' => 'datetime',
        'is_refund_eligible' => 'boolean',
    ];

    /**
     * Relationship: Order belongs to a customer
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Relationship: Order has many items
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Generate unique order number starting from 1001
     */
    public static function generateOrderNumber(): string
    {
        $lastOrder = static::orderBy('id', 'desc')->first();

        if ($lastOrder && preg_match('/^ADP-(\d+)$/', $lastOrder->order_number, $matches)) {
            $nextNumber = (int) $matches[1] + 1;
        } else {
            $nextNumber = max(1001, ($lastOrder ? $lastOrder->id : 0) + 1001);
        }

        $orderNumber = 'ADP-' . $nextNumber;

        // Ensure uniqueness
        while (static::where('order_number', $orderNumber)->exists()) {
            $nextNumber++;
            $orderNumber = 'ADP-' . $nextNumber;
        }

        return $orderNumber;
    }

    /**
     * Get customer's full name
     */
    public function getFullNameAttribute(): string
    {
        $firstName = trim($this->first_name ?? '');
        $lastName = trim($this->last_name ?? '');

        return trim($firstName . ' ' . $lastName);
    }

    /**
     * Get formatted grand total
     */
    public function getFormattedGrandTotalAttribute(): string
    {
        return $this->currency . ' ' . number_format($this->grand_total, 2);
    }

    /**
     * Check if order is paid
     */
    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    /**
     * Check if order can be cancelled
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'confirmed']);
    }

    /**
     * Get total commission for all items
     */
    public function getTotalCommissionAttribute(): float
    {
        return (float) $this->items->sum('commission_amount');
    }

    /**
     * Get merchant payout (grand total minus commission)
     */
    public function getMerchantPayoutAttribute(): float
    {
        return (float) $this->grand_total - $this->total_commission;
    }

    /**
     * Get the refunds for this order.
     */
    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    /**
     * Get the invoices for this order.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get the credit notes for this order.
     */
    public function creditNotes(): HasMany
    {
        return $this->hasMany(CreditNote::class);
    }

    /**
     * Get the settlement items for this order.
     */
    public function settlementItems(): HasMany
    {
        return $this->hasMany(SettlementItem::class);
    }

    /**
     * Get the user who cancelled this order.
     */
    public function cancelledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /**
     * Mark the order as delivered and set settlement eligibility.
     */
    public function markDelivered(): void
    {
        $this->update([
            'status' => 'delivered',
            'delivered_at' => now(),
            'settlement_eligible_at' => now()->addDays(15),
            'is_refund_eligible' => true,
        ]);
    }

    /**
     * Check if a return can be requested for this order.
     */
    public function canRequestReturn(): bool
    {
        if ($this->status !== 'delivered') {
            return false;
        }

        if ($this->return_requested_at) {
            return false;
        }

        // Allow returns within 14 days of delivery
        return $this->delivered_at && $this->delivered_at->diffInDays(now()) <= 14;
    }

    /**
     * Check if this order is eligible for settlement.
     */
    public function isSettlementEligible(): bool
    {
        return $this->status === 'delivered'
            && $this->settlement_eligible_at
            && $this->settlement_eligible_at->isPast();
    }
}
