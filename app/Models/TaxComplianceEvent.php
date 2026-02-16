<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaxComplianceEvent extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'tax_type',
        'event_type',
        'due_date',
        'reminder_date',
        'vat_return_id',
        'related_type',
        'related_id',
        'status',
        'completed_at',
        'completed_by',
        'has_penalty',
        'penalty_amount',
        'penalty_notes',
        'notification_sent',
        'notification_sent_at',
    ];

    protected $casts = [
        'due_date' => 'date',
        'reminder_date' => 'date',
        'completed_at' => 'date',
        'has_penalty' => 'boolean',
        'penalty_amount' => 'decimal:2',
        'notification_sent' => 'boolean',
        'notification_sent_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($event) {
            // Auto-set status based on due date
            if (!$event->status) {
                $event->status = $event->due_date->isPast() ? 'overdue' : 'upcoming';
            }
        });
    }

    /**
     * Get the VAT return associated with this event.
     */
    public function vatReturn(): BelongsTo
    {
        return $this->belongsTo(VatReturn::class);
    }

    /**
     * Get the polymorphic related model.
     */
    public function related(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who completed this event.
     */
    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    /**
     * Check if event is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->status === 'overdue' || ($this->due_date->isPast() && $this->status !== 'completed');
    }

    /**
     * Check if reminder should be sent.
     */
    public function shouldSendReminder(): bool
    {
        if ($this->notification_sent || $this->status === 'completed') {
            return false;
        }

        if (!$this->reminder_date) {
            return false;
        }

        return now()->gte($this->reminder_date) && now()->lte($this->due_date);
    }

    /**
     * Mark event as completed.
     */
    public function markAsCompleted(User $user): bool
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'completed_by' => $user->id,
        ]);

        return true;
    }

    /**
     * Mark notification as sent.
     */
    public function markNotificationSent(): bool
    {
        $this->update([
            'notification_sent' => true,
            'notification_sent_at' => now(),
        ]);

        return true;
    }

    /**
     * Add penalty.
     */
    public function addPenalty(float $amount, string $notes): bool
    {
        $this->update([
            'has_penalty' => true,
            'penalty_amount' => $amount,
            'penalty_notes' => $notes,
        ]);

        return true;
    }

    /**
     * Scope: Upcoming events.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('status', 'upcoming')
            ->where('due_date', '>=', now())
            ->orderBy('due_date');
    }

    /**
     * Scope: Overdue events.
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue')
            ->orWhere(function ($q) {
                $q->where('due_date', '<', now())
                    ->where('status', '!=', 'completed');
            })
            ->orderBy('due_date');
    }

    /**
     * Scope: Due soon (within N days).
     */
    public function scopeDueSoon($query, int $days = 7)
    {
        return $query->where('status', 'upcoming')
            ->whereBetween('due_date', [now(), now()->addDays($days)])
            ->orderBy('due_date');
    }

    /**
     * Scope: Needs reminder.
     */
    public function scopeNeedsReminder($query)
    {
        return $query->where('status', '!=', 'completed')
            ->where('notification_sent', false)
            ->whereNotNull('reminder_date')
            ->where('reminder_date', '<=', now())
            ->where('due_date', '>=', now());
    }

    /**
     * Scope: By tax type.
     */
    public function scopeTaxType($query, string $type)
    {
        return $query->where('tax_type', $type);
    }

    /**
     * Scope: By event type.
     */
    public function scopeEventType($query, string $type)
    {
        return $query->where('event_type', $type);
    }

    /**
     * Get days until due.
     */
    public function getDaysUntilDueAttribute(): int
    {
        return max(0, now()->diffInDays($this->due_date, false));
    }

    /**
     * Get CSS color class based on urgency.
     */
    public function getUrgencyColorAttribute(): string
    {
        if ($this->status === 'completed') {
            return 'success';
        }

        if ($this->isOverdue()) {
            return 'danger';
        }

        if ($this->days_until_due <= 3) {
            return 'warning';
        }

        return 'info';
    }
}
