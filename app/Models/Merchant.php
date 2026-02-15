<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Merchant extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'email',
        'password',
        'business_name',
        'contact_name',
        'phone',
        'address',
        'city',
        'country',
        'trade_license',
        'tax_registration',
        'status',
        'rejection_reason',
        'approved_at',
        'commission_percentage',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'approved_at' => 'datetime',
        'commission_percentage' => 'decimal:2',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['name'];

    /**
     * Get the merchant's name (uses business_name).
     * Required by Filament for user display.
     */
    public function getNameAttribute(): string
    {
        return $this->business_name;
    }

    /**
     * Get the products for the merchant.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Check if merchant is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Check if merchant is pending approval.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if merchant is suspended.
     */
    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    /**
     * Approve the merchant.
     */
    public function approve(): void
    {
        $this->update([
            'status' => 'approved',
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);
    }

    /**
     * Reject the merchant.
     */
    public function reject(string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
            'approved_at' => null,
        ]);
    }

    /**
     * Suspend the merchant.
     */
    public function suspend(string $reason): void
    {
        $this->update([
            'status' => 'suspended',
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * Get the order items for the merchant.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the settlements for the merchant.
     */
    public function settlements(): HasMany
    {
        return $this->hasMany(Settlement::class);
    }

    /**
     * Get the refunds for the merchant.
     */
    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    /**
     * Get the invoices for the merchant.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get the commission rules for the merchant.
     */
    public function commissionRules(): HasMany
    {
        return $this->hasMany(CommissionRule::class);
    }

    /**
     * Get the debit notes for the merchant.
     */
    public function debitNotes(): HasMany
    {
        return $this->hasMany(MerchantDebitNote::class);
    }

    /**
     * Get the payout reports for the merchant.
     */
    public function payoutReports(): HasMany
    {
        return $this->hasMany(PayoutReport::class);
    }

    /**
     * Get the effective commission rate (defaults to 15% if not set).
     */
    public function getEffectiveCommissionAttribute(): float
    {
        return (float) ($this->commission_percentage ?? 15.00);
    }
}
