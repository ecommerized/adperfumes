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
    // Perfume concentration types
    const CONCENTRATIONS = [
        'parfum' => 'Pure Parfum',
        'eau_de_parfum' => 'Eau de Parfum',
        'eau_de_toilette' => 'Eau de Toilette',
        'eau_de_cologne' => 'Eau de Cologne',
        'eau_fraiche' => 'Eau Fraiche',
        'attar' => 'Attar/Oil-based',
        'body_mist' => 'Body Mist',
        'hair_mist' => 'Hair Mist',
        'bakhoor' => 'Bakhoor/Incense',
        'oud_oil' => 'Oud Oil',
        'perfume_oil' => 'Perfume Oil',
        'gift_set' => 'Gift Set',
    ];

    const GENDERS = ['men' => 'Men', 'women' => 'Women', 'unisex' => 'Unisex'];

    const SCENT_FAMILIES = [
        'floral', 'woody', 'oriental', 'fresh', 'citrus', 'aquatic',
        'gourmand', 'spicy', 'green', 'fruity', 'aromatic', 'chypre',
        'fougere', 'leather', 'musk', 'oud', 'amber', 'powdery', 'tobacco',
    ];

    const SEASONS = ['spring', 'summer', 'fall', 'winter', 'all_seasons'];
    const OCCASIONS = ['daily', 'office', 'evening', 'date_night', 'formal', 'casual', 'special_occasion', 'wedding', 'all_occasions'];
    const LONGEVITIES = ['light', 'moderate', 'long_lasting', 'very_long_lasting', 'beast_mode'];
    const SILLAGES = ['intimate', 'moderate', 'strong', 'enormous'];

    protected $fillable = [
        'shopify_id', 'name', 'slug', 'gtin', 'sku', 'description', 'short_description',
        'price', 'price_excluding_tax', 'tax_amount', 'tax_rate', 'compare_at_price',
        'stock', 'low_stock_threshold', 'image', 'gallery_images', 'video_url',
        'status', 'brand_id', 'merchant_id',
        'is_new', 'on_sale', 'original_price',
        'is_featured', 'is_bestseller', 'is_exclusive', 'is_tester', 'is_authentic_guaranteed',
        'perfume_house', 'country_of_origin', 'concentration', 'gender',
        'volume_ml', 'volume_display', 'scent_family', 'season', 'occasion',
        'longevity', 'sillage', 'longevity_hours', 'launch_year',
        'weight_grams', 'is_flammable', 'available_for_international_shipping',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'decimal:2',
        'price_excluding_tax' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'compare_at_price' => 'decimal:2',
        'original_price' => 'decimal:2',
        'gallery_images' => 'array',
        'status' => 'boolean',
        'is_new' => 'boolean',
        'on_sale' => 'boolean',
        'is_featured' => 'boolean',
        'is_bestseller' => 'boolean',
        'is_exclusive' => 'boolean',
        'is_tester' => 'boolean',
        'is_authentic_guaranteed' => 'boolean',
        'is_flammable' => 'boolean',
        'available_for_international_shipping' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function (Product $product) {
            // Auto-calculate tax fields when price changes
            if ($product->isDirty('price') || $product->isDirty('tax_rate')) {
                $taxRate = $product->tax_rate ?? 5.00;
                $priceInclTax = (float) $product->price;

                if ($priceInclTax > 0) {
                    $product->price_excluding_tax = round($priceInclTax / (1 + ($taxRate / 100)), 2);
                    $product->tax_amount = round($priceInclTax - $product->price_excluding_tax, 2);
                }
            }
        });
    }

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
        return $this->belongsToMany(Note::class, 'product_notes')
            ->withPivot('type')
            ->wherePivot('type', 'top')
            ->withPivotValue('type', 'top');
    }

    /**
     * Get middle notes for the product.
     */
    public function middleNotes(): BelongsToMany
    {
        return $this->belongsToMany(Note::class, 'product_notes')
            ->withPivot('type')
            ->wherePivot('type', 'middle')
            ->withPivotValue('type', 'middle');
    }

    /**
     * Get base notes for the product.
     */
    public function baseNotes(): BelongsToMany
    {
        return $this->belongsToMany(Note::class, 'product_notes')
            ->withPivot('type')
            ->wherePivot('type', 'base')
            ->withPivotValue('type', 'base');
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
