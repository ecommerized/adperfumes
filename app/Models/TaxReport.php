<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxReport extends Model
{
    const REPORT_TYPES = ['daily', 'weekly', 'monthly', 'quarterly', 'yearly', 'custom'];

    protected $fillable = [
        'report_number', 'report_type', 'period_start', 'period_end',
        'total_sales_incl_tax', 'total_sales_excl_tax', 'total_output_vat',
        'total_commission_earned', 'total_commission_vat', 'net_vat_payable',
        'total_orders', 'total_merchants', 'merchant_breakdown', 'category_breakdown',
        'pdf_path', 'status', 'generated_by',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'merchant_breakdown' => 'array',
        'category_breakdown' => 'array',
        'total_sales_incl_tax' => 'decimal:2',
        'total_sales_excl_tax' => 'decimal:2',
        'total_output_vat' => 'decimal:2',
        'total_commission_earned' => 'decimal:2',
        'total_commission_vat' => 'decimal:2',
        'net_vat_payable' => 'decimal:2',
    ];

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
