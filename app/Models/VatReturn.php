<?php

namespace App\Models;

use App\Traits\HasTransactionLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class VatReturn extends Model
{
    use HasFactory, SoftDeletes, HasTransactionLog;

    protected $fillable = [
        'return_number',
        'period_start',
        'period_end',
        'period_type',
        'year',
        'quarter',
        'month',
        'total_sales_excl_vat',
        'output_vat_rate',
        'output_vat_amount',
        'zero_rated_sales',
        'exempt_sales',
        'total_purchases_excl_vat',
        'input_vat_amount',
        'input_vat_reclaimable',
        'net_vat_payable',
        'adjustments',
        'adjustment_notes',
        'status',
        'filing_deadline',
        'filed_at',
        'fta_reference',
        'payment_due_date',
        'paid_at',
        'payment_reference',
        'original_return_id',
        'is_amendment',
        'amendment_reason',
        'prepared_by',
        'approved_by',
        'approved_at',
        'pdf_path',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'year' => 'integer',
        'quarter' => 'integer',
        'month' => 'integer',
        'total_sales_excl_vat' => 'decimal:2',
        'output_vat_rate' => 'decimal:2',
        'output_vat_amount' => 'decimal:2',
        'zero_rated_sales' => 'decimal:2',
        'exempt_sales' => 'decimal:2',
        'total_purchases_excl_vat' => 'decimal:2',
        'input_vat_amount' => 'decimal:2',
        'input_vat_reclaimable' => 'decimal:2',
        'net_vat_payable' => 'decimal:2',
        'adjustments' => 'decimal:2',
        'filing_deadline' => 'date',
        'filed_at' => 'date',
        'payment_due_date' => 'date',
        'paid_at' => 'date',
        'is_amendment' => 'boolean',
        'approved_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($return) {
            if (!$return->return_number) {
                $return->return_number = static::generateReturnNumber($return);
            }

            // Calculate net VAT payable
            if (!isset($return->net_vat_payable)) {
                $return->calculateNetVat();
            }

            // Set filing deadline (28 days after period end)
            if (!$return->filing_deadline && $return->period_end) {
                $return->filing_deadline = $return->period_end->copy()->addDays(28);
            }
        });
    }

    /**
     * Generate unique return number.
     */
    public static function generateReturnNumber(VatReturn $return): string
    {
        $year = $return->year;
        $period = $return->period_type === 'quarterly' ? "Q{$return->quarter}" : "M{$return->month}";

        $count = static::where('year', $year)
            ->where('period_type', $return->period_type)
            ->count();

        return sprintf('VAT-%s-%s-%03d', $year, $period, $count + 1);
    }

    /**
     * Get the user who prepared this return.
     */
    public function preparer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    /**
     * Get the user who approved this return.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the original return (if this is an amendment).
     */
    public function originalReturn(): BelongsTo
    {
        return $this->belongsTo(VatReturn::class, 'original_return_id');
    }

    /**
     * Get amendments to this return.
     */
    public function amendments(): HasMany
    {
        return $this->hasMany(VatReturn::class, 'original_return_id');
    }

    /**
     * Get expenses included in this return.
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'vat_return_id');
    }

    /**
     * Get compliance events for this return.
     */
    public function complianceEvents(): HasMany
    {
        return $this->hasMany(TaxComplianceEvent::class, 'vat_return_id');
    }

    /**
     * Calculate net VAT payable.
     */
    public function calculateNetVat(): void
    {
        $this->net_vat_payable = round(
            $this->output_vat_amount - $this->input_vat_reclaimable + $this->adjustments,
            2
        );
    }

    /**
     * Check if VAT refund is due (negative net VAT).
     */
    public function isRefundDue(): bool
    {
        return $this->net_vat_payable < 0;
    }

    /**
     * Get refund amount (absolute value if negative).
     */
    public function getRefundAmount(): float
    {
        return $this->isRefundDue() ? abs($this->net_vat_payable) : 0;
    }

    /**
     * Check if filing deadline is approaching (within 7 days).
     */
    public function isDeadlineApproaching(): bool
    {
        if (!$this->filing_deadline || in_array($this->status, ['filed', 'paid'])) {
            return false;
        }

        return $this->filing_deadline->diffInDays(now()) <= 7 && $this->filing_deadline->isFuture();
    }

    /**
     * Check if filing deadline is overdue.
     */
    public function isOverdue(): bool
    {
        if (!$this->filing_deadline || in_array($this->status, ['filed', 'paid'])) {
            return false;
        }

        return $this->filing_deadline->isPast();
    }

    /**
     * Approve this VAT return.
     */
    public function approve(User $approver): bool
    {
        if ($this->status !== 'pending_review') {
            return false;
        }

        $oldValues = [
            'status' => $this->status,
        ];

        $this->update([
            'status' => 'approved',
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);

        $this->logTransaction('vat_return_approved', $oldValues, [
            'approved_by' => $approver->name,
            'approved_at' => now()->toDateTimeString(),
        ]);

        return true;
    }

    /**
     * Mark as filed with FTA.
     */
    public function markAsFiled(string $ftaReference): bool
    {
        if (!in_array($this->status, ['approved', 'pending_review'])) {
            return false;
        }

        $oldValues = [
            'status' => $this->status,
        ];

        $this->update([
            'status' => 'filed',
            'filed_at' => now(),
            'fta_reference' => $ftaReference,
        ]);

        $this->logTransaction('vat_return_filed', $oldValues, [
            'fta_reference' => $ftaReference,
            'filed_at' => now()->toDateString(),
        ]);

        // Create payment compliance event if VAT is payable
        if ($this->net_vat_payable > 0) {
            $this->createPaymentEvent();
        }

        return true;
    }

    /**
     * Mark VAT as paid.
     */
    public function markAsPaid(string $paymentReference): bool
    {
        if ($this->status !== 'filed' || $this->net_vat_payable <= 0) {
            return false;
        }

        $oldValues = [
            'status' => $this->status,
        ];

        $this->update([
            'status' => 'paid',
            'paid_at' => now(),
            'payment_reference' => $paymentReference,
        ]);

        $this->logTransaction('vat_return_paid', $oldValues, [
            'payment_reference' => $paymentReference,
            'amount_paid' => $this->net_vat_payable,
            'paid_at' => now()->toDateString(),
        ]);

        return true;
    }

    /**
     * Create payment compliance event.
     */
    protected function createPaymentEvent(): void
    {
        TaxComplianceEvent::create([
            'title' => "VAT Payment Due - {$this->return_number}",
            'description' => "Payment of AED " . number_format($this->net_vat_payable, 2) . " is due",
            'tax_type' => 'vat',
            'event_type' => 'payment_deadline',
            'due_date' => $this->payment_due_date ?? $this->filing_deadline,
            'reminder_date' => $this->payment_due_date?->copy()->subDays(3),
            'vat_return_id' => $this->id,
            'status' => 'upcoming',
        ]);
    }

    /**
     * Create amendment return.
     */
    public function createAmendment(string $reason): VatReturn
    {
        $amendment = static::create([
            'period_start' => $this->period_start,
            'period_end' => $this->period_end,
            'period_type' => $this->period_type,
            'year' => $this->year,
            'quarter' => $this->quarter,
            'month' => $this->month,
            'total_sales_excl_vat' => $this->total_sales_excl_vat,
            'output_vat_rate' => $this->output_vat_rate,
            'output_vat_amount' => $this->output_vat_amount,
            'zero_rated_sales' => $this->zero_rated_sales,
            'exempt_sales' => $this->exempt_sales,
            'total_purchases_excl_vat' => $this->total_purchases_excl_vat,
            'input_vat_amount' => $this->input_vat_amount,
            'input_vat_reclaimable' => $this->input_vat_reclaimable,
            'net_vat_payable' => $this->net_vat_payable,
            'original_return_id' => $this->id,
            'is_amendment' => true,
            'amendment_reason' => $reason,
            'status' => 'draft',
            'prepared_by' => auth()->id(),
        ]);

        // Update original return status
        $this->update(['status' => 'amended']);

        $this->logTransaction('amendment_created', [], [
            'amendment_return' => $amendment->return_number,
            'reason' => $reason,
        ]);

        return $amendment;
    }

    /**
     * Scope: Filter by period type.
     */
    public function scopePeriodType($query, string $type)
    {
        return $query->where('period_type', $type);
    }

    /**
     * Scope: Filter by year.
     */
    public function scopeYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    /**
     * Scope: Filter by status.
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Overdue returns.
     */
    public function scopeOverdue($query)
    {
        return $query->whereNotIn('status', ['filed', 'paid', 'refund_received'])
            ->where('filing_deadline', '<', now());
    }

    /**
     * Scope: Deadline approaching (within 7 days).
     */
    public function scopeDeadlineApproaching($query)
    {
        return $query->whereNotIn('status', ['filed', 'paid'])
            ->whereBetween('filing_deadline', [now(), now()->addDays(7)]);
    }
}
