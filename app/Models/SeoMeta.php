<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SeoMeta extends Model
{
    protected $table = 'seo_metas';

    protected $fillable = [
        'seoable_type',
        'seoable_id',
        'meta_title',
        'meta_description',
        'canonical_url',
        'robots',
        'og_title',
        'og_description',
        'og_type',
        'og_image',
        'twitter_card',
        'twitter_title',
        'twitter_description',
        'keywords',
        'aeo_data',
        'schema_markup',
        'social_media',
        'scoring',
        'content_optimization',
        'is_manually_edited',
        'last_generated_at',
    ];

    protected $casts = [
        'keywords' => 'array',
        'aeo_data' => 'array',
        'schema_markup' => 'array',
        'social_media' => 'array',
        'scoring' => 'array',
        'content_optimization' => 'array',
        'is_manually_edited' => 'boolean',
        'last_generated_at' => 'datetime',
    ];

    public function seoable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getOverallScoreAttribute(): int
    {
        return $this->scoring['overall_score'] ?? 0;
    }

    public function getSeoScoreAttribute(): int
    {
        return $this->scoring['seo_score'] ?? 0;
    }

    public function getAeoScoreAttribute(): int
    {
        return $this->scoring['aeo_score'] ?? 0;
    }
}
