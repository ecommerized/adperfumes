<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Accord extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
    ];

    /**
     * Get the products that have this accord.
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_accords')->withPivot('percentage');
    }
}
