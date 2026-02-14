<?php

namespace App\Models;

use App\Traits\HasSeoAeo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends Model
{
    use HasSeoAeo;

    public function seoRelevantFields(): array
    {
        return ['name', 'description', 'slug'];
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
        return route('products.byBrand', $this->slug);
    }

    public function seoContentType(): string
    {
        return 'brand';
    }

    public function seoCategory(): string
    {
        return 'Brands';
    }

    public function seoImages(): array
    {
        return $this->logo ? [\Storage::url($this->logo)] : [];
    }
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'logo',
        'description',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => 'boolean',
    ];

    /**
     * Get the products for the brand.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
