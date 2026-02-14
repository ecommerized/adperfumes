<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialMediaPost extends Model
{
    protected $fillable = [
        'type',
        'caption',
        'hashtags',
        'image_path',
        'product_id',
        'discount_id',
        'status',
        'scheduled_at',
        'published_at',
        'facebook_post_id',
        'error_message',
        'meta_data',
        'created_by',
        'source',
    ];

    protected $casts = [
        'meta_data' => 'array',
        'scheduled_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    // ── Relationships ────────────────────────────

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class);
    }

    // ── Scopes ───────────────────────────────────

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeReadyToPublish($query)
    {
        return $query->where('status', 'scheduled')
            ->where('scheduled_at', '<=', now());
    }

    public function scopeAutoPilot($query)
    {
        return $query->where('source', 'auto_pilot');
    }

    // ── Computed Attributes ──────────────────────

    public function getFullCaptionAttribute(): string
    {
        $caption = $this->caption;
        if ($this->hashtags) {
            $caption .= "\n\n" . $this->hashtags;
        }
        return $caption;
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'gray',
            'scheduled' => 'warning',
            'posting' => 'info',
            'published' => 'success',
            'failed' => 'danger',
            default => 'gray',
        };
    }

    public function getTypeLabel(): string
    {
        return match ($this->type) {
            'product_promo' => 'Product Promotion',
            'offer' => 'Offer / Discount',
            'brand_story' => 'Brand Story',
            'custom' => 'Custom',
            default => ucfirst($this->type),
        };
    }

    // ── Helper Methods ───────────────────────────

    public function markAsPosting(): void
    {
        $this->update(['status' => 'posting']);
    }

    public function markAsPublished(string $facebookPostId): void
    {
        $this->update([
            'status' => 'published',
            'published_at' => now(),
            'facebook_post_id' => $facebookPostId,
            'error_message' => null,
        ]);
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }
}
