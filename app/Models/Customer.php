<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;

class Customer extends Model
{
    use Notifiable;
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'email',
        'phone',
        'first_name',
        'last_name',
        'address',
        'city',
        'country',
        'postal_code',
        'marketing_email_opt_in',
        'marketing_whatsapp_opt_in',
        'total_orders',
        'total_spent',
        'first_order_at',
        'last_order_at',
        'customer_segment',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'marketing_email_opt_in' => 'boolean',
        'marketing_whatsapp_opt_in' => 'boolean',
        'total_orders' => 'integer',
        'total_spent' => 'decimal:2',
        'first_order_at' => 'datetime',
        'last_order_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['full_name'];

    /**
     * Get the customer's full name.
     */
    public function getFullNameAttribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    /**
     * Get the orders for the customer.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Scope a query to only include customers opted in for email marketing.
     */
    public function scopeEmailOptIn($query)
    {
        return $query->where('marketing_email_opt_in', true);
    }

    /**
     * Scope a query to only include customers opted in for WhatsApp marketing.
     */
    public function scopeWhatsappOptIn($query)
    {
        return $query->where('marketing_whatsapp_opt_in', true);
    }

    /**
     * Scope a query to filter by customer segment.
     */
    public function scopeSegment($query, string $segment)
    {
        return $query->where('customer_segment', $segment);
    }

    /**
     * Auto-calculate segment based on spending/orders.
     */
    public function calculateSegment(): string
    {
        if ($this->total_orders === 0) {
            return 'new';
        } elseif ($this->total_spent >= 5000) {
            return 'vip';
        } elseif ($this->last_order_at && $this->last_order_at->lt(now()->subMonths(6))) {
            return 'inactive';
        } else {
            return 'regular';
        }
    }

    /**
     * Update customer statistics from orders.
     */
    public function updateStats(): void
    {
        $orders = $this->orders()->where('payment_status', 'paid')->get();

        $this->update([
            'total_orders' => $orders->count(),
            'total_spent' => $orders->sum('grand_total'),
            'first_order_at' => $orders->min('created_at'),
            'last_order_at' => $orders->max('created_at'),
            'customer_segment' => $this->calculateSegment(),
        ]);
    }
}
