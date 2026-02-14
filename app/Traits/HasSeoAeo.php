<?php

namespace App\Traits;

use App\Jobs\GenerateSeoAeoJob;
use App\Models\SeoMeta;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasSeoAeo
{
    public static function bootHasSeoAeo(): void
    {
        static::created(function ($model) {
            GenerateSeoAeoJob::dispatch($model)->onQueue('seo')->delay(now()->addSeconds(5));
        });

        static::updated(function ($model) {
            if ($model->shouldRegenerateSeo()) {
                GenerateSeoAeoJob::dispatch($model)->onQueue('seo')->delay(now()->addSeconds(10));
            }
        });
    }

    public function seoMeta(): MorphOne
    {
        return $this->morphOne(SeoMeta::class, 'seoable');
    }

    public function shouldRegenerateSeo(): bool
    {
        if ($this->seoMeta && $this->seoMeta->is_manually_edited) {
            return false;
        }

        $relevantFields = $this->seoRelevantFields();
        foreach ($relevantFields as $field) {
            if ($this->isDirty($field)) {
                return true;
            }
        }

        return false;
    }

    public function seoRelevantFields(): array
    {
        return ['name', 'title', 'description', 'slug', 'content'];
    }

    public function seoTitle(): string
    {
        return $this->name ?? $this->title ?? '';
    }

    public function seoContent(): string
    {
        return $this->description ?? $this->content ?? '';
    }

    public function seoUrl(): string
    {
        return url('/');
    }

    public function seoContentType(): string
    {
        return 'page';
    }

    public function seoCategory(): string
    {
        return 'General';
    }

    public function seoImages(): array
    {
        return [];
    }
}
