<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Discount extends Model
{
    protected $fillable = [
        'code',
        'description',
        'type',
        'value',
        'max_uses',
        'max_uses_per_user',
        'current_uses',
        'min_purchase_amount',
        'starts_at',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'min_purchase_amount' => 'decimal:2',
        'max_uses' => 'integer',
        'max_uses_per_user' => 'integer',
        'current_uses' => 'integer',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Check if discount code is valid
     *
     * @param float $cartTotal
     * @param string|null $userEmail
     * @return array ['valid' => bool, 'message' => string]
     */
    public function isValid(float $cartTotal, ?string $userEmail = null): array
    {
        // Check if discount is active
        if (!$this->is_active) {
            return [
                'valid' => false,
                'message' => 'This discount code is inactive',
            ];
        }

        // Check if discount has started
        if ($this->starts_at && now()->isBefore($this->starts_at)) {
            return [
                'valid' => false,
                'message' => 'This discount code is not yet valid',
            ];
        }

        // Check if discount has expired
        if ($this->expires_at && now()->isAfter($this->expires_at)) {
            return [
                'valid' => false,
                'message' => 'This discount code has expired',
            ];
        }

        // Check if maximum uses reached
        if ($this->max_uses !== null && $this->current_uses >= $this->max_uses) {
            return [
                'valid' => false,
                'message' => 'This discount code has reached its usage limit',
            ];
        }

        // Check minimum purchase amount
        if ($this->min_purchase_amount && $cartTotal < $this->min_purchase_amount) {
            return [
                'valid' => false,
                'message' => 'Minimum purchase of AED ' . number_format($this->min_purchase_amount, 2) . ' required',
            ];
        }

        // TODO: Check per-user usage limit (requires user authentication)
        // This can be implemented when customer accounts are added

        return [
            'valid' => true,
            'message' => 'Discount code applied successfully',
        ];
    }

    /**
     * Calculate discount amount
     *
     * @param float $subtotal
     * @return float
     */
    public function calculateDiscount(float $subtotal): float
    {
        if ($this->type === 'percentage') {
            $discount = ($subtotal * $this->value) / 100;
        } else {
            // Fixed amount
            $discount = $this->value;
        }

        // Ensure discount doesn't exceed subtotal
        return min($discount, $subtotal);
    }

    /**
     * Increment usage counter
     *
     * @return void
     */
    public function incrementUsage(): void
    {
        $this->increment('current_uses');
    }

    /**
     * Scope: Active discounts only
     *
     * @param $query
     * @return mixed
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', now());
            });
    }

    /**
     * Scope: Available discounts (not maxed out)
     *
     * @param $query
     * @return mixed
     */
    public function scopeAvailable($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('max_uses')
                ->orWhereRaw('current_uses < max_uses');
        });
    }

    /**
     * Get formatted discount value
     *
     * @return string
     */
    public function getFormattedValueAttribute(): string
    {
        if ($this->type === 'percentage') {
            return $this->value . '%';
        }

        return 'AED ' . number_format($this->value, 2);
    }

    /**
     * Get discount status
     *
     * @return string
     */
    public function getStatusAttribute(): string
    {
        if (!$this->is_active) {
            return 'Inactive';
        }

        if ($this->starts_at && now()->isBefore($this->starts_at)) {
            return 'Scheduled';
        }

        if ($this->expires_at && now()->isAfter($this->expires_at)) {
            return 'Expired';
        }

        if ($this->max_uses !== null && $this->current_uses >= $this->max_uses) {
            return 'Maxed Out';
        }

        return 'Active';
    }

    /**
     * Check if discount is expired
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->expires_at && now()->isAfter($this->expires_at);
    }

    /**
     * Check if discount is scheduled (not started yet)
     *
     * @return bool
     */
    public function isScheduled(): bool
    {
        return $this->starts_at && now()->isBefore($this->starts_at);
    }
}
