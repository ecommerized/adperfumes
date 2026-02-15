<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id', 'description', 'sku', 'quantity',
        'unit_price_excl_tax', 'unit_price_incl_tax',
        'tax_rate', 'tax_amount', 'line_total',
    ];

    protected $casts = [
        'unit_price_excl_tax' => 'decimal:2',
        'unit_price_incl_tax' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
