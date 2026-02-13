<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'merchant_id',
        'product_name',
        'product_slug',
        'brand_name',
        'product_image',
        'price',
        'quantity',
        'subtotal',
        'commission_rate',
        'commission_amount',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'quantity' => 'integer',
        'commission_rate' => 'decimal:2',
        'commission_amount' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function getFormattedPriceAttribute(): string
    {
        return 'AED ' . number_format($this->price, 2);
    }

    public function getFormattedSubtotalAttribute(): string
    {
        return 'AED ' . number_format($this->subtotal, 2);
    }
}
