<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
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
        'status',
        'customer_notes',
        'admin_notes',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'shipping' => 'decimal:2',
        'discount' => 'decimal:2',
        'grand_total' => 'decimal:2',
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
        return $this->first_name . ' ' . $this->last_name;
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
}
