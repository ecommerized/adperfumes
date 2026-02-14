<?php

namespace App\Models;

use App\Traits\HasSeoAeo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    use HasSeoAeo;

    public function seoRelevantFields(): array
    {
        return ['name', 'description', 'slug', 'price', 'brand_id'];
    }

    public function seoTitle(): string
    {
        return $this->name;
    }

    public function seoContent(): string
    {
        return $this->description ?? '';
    }

    public function seoUrl(): string
    {
        return route('products.show', $this->slug);
    }

    public function seoContentType(): string
    {
        return 'product';
    }

    public function seoCategory(): string
    {
        return $this->brand?->name ?? 'Perfumes';
    }

    public function seoImages(): array
    {
        return $this->image ? [\Storage::url($this->image)] : [];
    }
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'shopify_id',
        'name',
        'slug',
        'gtin',
        'description',
        'price',
        'stock',
        'image',
        'status',
        'brand_id',
        'merchant_id',
        'is_new',
        'on_sale',
        'original_price',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'decimal:2',
        'original_price' => 'decimal:2',
        'status' => 'boolean',
        'is_new' => 'boolean',
        'on_sale' => 'boolean',
    ];

    /**
     * Get the brand that owns the product.
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Get the merchant that owns the product.
     */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    /**
     * Get all notes for the product.
     */
    public function notes(): BelongsToMany
    {
        return $this->belongsToMany(Note::class, 'product_notes')->withPivot('type');
    }

    /**
     * Get top notes for the product.
     */
    public function topNotes(): BelongsToMany
    {
        return $this->belongsToMany(Note::class, 'product_notes')->wherePivot('type', 'top');
    }

    /**
     * Get middle notes for the product.
     */
    public function middleNotes(): BelongsToMany
    {
        return $this->belongsToMany(Note::class, 'product_notes')->wherePivot('type', 'middle');
    }

    /**
     * Get base notes for the product.
     */
    public function baseNotes(): BelongsToMany
    {
        return $this->belongsToMany(Note::class, 'product_notes')->wherePivot('type', 'base');
    }

    /**
     * Get the accords for the product.
     */
    public function accords(): BelongsToMany
    {
        return $this->belongsToMany(Accord::class, 'product_accords')->withPivot('percentage');
    }

    /**
     * Get the categories for the product.
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }
}
