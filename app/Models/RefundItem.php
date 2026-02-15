<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RefundItem extends Model
{
    const ITEM_CONDITIONS = [
        'sealed', 'unopened', 'opened_defective',
        'damaged_in_transit', 'wrong_item', 'other',
    ];

    protected $fillable = [
        'refund_id', 'order_item_id', 'product_id', 'product_name',
        'quantity_refunded', 'unit_price_incl_tax', 'line_refund_excl_tax',
        'line_refund_tax', 'line_refund_total', 'item_condition', 'stock_restored',
    ];

    protected $casts = [
        'stock_restored' => 'boolean',
        'unit_price_incl_tax' => 'decimal:2',
        'line_refund_excl_tax' => 'decimal:2',
        'line_refund_tax' => 'decimal:2',
        'line_refund_total' => 'decimal:2',
    ];

    public function refund(): BelongsTo
    {
        return $this->belongsTo(Refund::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
