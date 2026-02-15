<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class CommissionRule extends Model
{
    const LEVELS = ['global', 'merchant', 'category', 'product', 'tier'];
    const TYPES = ['percentage', 'fixed', 'tiered', 'hybrid'];

    protected $fillable = [
        'name', 'level', 'type', 'merchant_id', 'category_id', 'product_id',
        'percentage_rate', 'fixed_amount', 'tier_rules', 'priority',
        'is_active', 'valid_from', 'valid_until', 'notes', 'created_by',
    ];

    protected $casts = [
        'tier_rules' => 'array',
        'is_active' => 'boolean',
        'valid_from' => 'date',
        'valid_until' => 'date',
        'percentage_rate' => 'decimal:2',
        'fixed_amount' => 'decimal:2',
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeValid(Builder $query, ?Carbon $date = null): Builder
    {
        $date = $date ?? today();

        return $query
            ->where(fn ($q) => $q->whereNull('valid_from')->orWhere('valid_from', '<=', $date))
            ->where(fn ($q) => $q->whereNull('valid_until')->orWhere('valid_until', '>=', $date));
    }

    public function scopeForLevel(Builder $query, string $level): Builder
    {
        return $query->where('level', $level);
    }

    public function getDisplayRateAttribute(): string
    {
        return match ($this->type) {
            'percentage' => $this->percentage_rate . '%',
            'fixed' => 'AED ' . number_format($this->fixed_amount, 2),
            'hybrid' => $this->percentage_rate . '% + AED ' . number_format($this->fixed_amount, 2),
            'tiered' => 'Tiered',
            default => '-',
        };
    }
}
