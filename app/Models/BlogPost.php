<?php

namespace App\Models;

use App\Traits\HasSeoAeo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BlogPost extends Model
{
    use HasSeoAeo;

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'featured_image',
        'author',
        'status',
        'published_at',
        'topic_source',
        'seo_score',
        'meta_data',
    ];

    protected $casts = [
        'meta_data' => 'array',
        'published_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($post) {
            if (empty($post->slug)) {
                $post->slug = Str::slug($post->title);
            }
        });
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    // HasSeoAeo overrides

    public function seoRelevantFields(): array
    {
        return ['title', 'content', 'excerpt', 'slug'];
    }

    public function seoTitle(): string
    {
        return $this->title;
    }

    public function seoContent(): string
    {
        return $this->content ?? $this->excerpt ?? '';
    }

    public function seoUrl(): string
    {
        return url("/blog/{$this->slug}");
    }

    public function seoContentType(): string
    {
        return 'blog_post';
    }

    public function seoCategory(): string
    {
        return 'Blog';
    }

    public function seoImages(): array
    {
        return $this->featured_image ? [\Storage::url($this->featured_image)] : [];
    }
}
