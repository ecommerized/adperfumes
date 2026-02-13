# Perfume Data Architecture - Usage Guide

## 📊 Database Overview

Your luxury perfume marketplace now has a complete, normalized data structure:

- ✅ **60 Fragrance Notes** (20 top, 20 middle, 20 base)
- ✅ **20 Main Accords** (Woody, Floral, Fresh, Oriental, etc.)
- ✅ **Brands System** (logo, description, status)
- ✅ **Products** with full perfume relationships

---

## 🎯 Quick Start Examples

### Creating a Perfume Product

```php
use App\Models\Brand;
use App\Models\Product;
use App\Models\Note;
use App\Models\Accord;
use Illuminate\Support\Str;

// Create a brand
$brand = Brand::create([
    'name' => 'Dior',
    'slug' => 'dior',
    'description' => 'Luxury French fashion house',
    'status' => true,
]);

// Create a perfume product
$product = Product::create([
    'name' => 'Sauvage Eau de Parfum',
    'slug' => 'sauvage-edp',
    'description' => 'A powerful and noble fragrance...',
    'price' => 425.00,
    'stock' => 50,
    'brand_id' => $brand->id,
    'status' => true,
]);

// Attach fragrance notes
$bergamot = Note::where('name', 'Bergamot')->first();
$pepper = Note::where('name', 'Pink Pepper')->first();
$lavender = Note::where('name', 'Lavender')->first();
$patchouli = Note::where('name', 'Patchouli')->first();
$amber = Note::where('name', 'Amber')->first();

$product->notes()->attach([
    $bergamot->id,
    $pepper->id,
    $lavender->id,
    $patchouli->id,
    $amber->id,
]);

// Attach accords with intensity percentages
$woody = Accord::where('slug', 'woody')->first();
$fresh = Accord::where('slug', 'fresh')->first();
$aromatic = Accord::where('slug', 'aromatic')->first();

$product->accords()->attach([
    $woody->id => ['percentage' => 60],
    $fresh->id => ['percentage' => 25],
    $aromatic->id => ['percentage' => 15],
]);
```

---

## 🔍 Querying Perfume Data

### Get Product with All Relationships

```php
$product = Product::with(['brand', 'notes', 'accords'])->find(1);

echo $product->brand->name; // "Dior"
echo $product->notes->count(); // Number of notes
echo $product->accords->first()->pivot->percentage; // Accord intensity
```

### Get Notes by Type

```php
// Using relationships
$topNotes = $product->topNotes;
$middleNotes = $product->middleNotes;
$baseNotes = $product->baseNotes;

// Using scopes on Note model
$allTopNotes = Note::top()->get();
$allMiddleNotes = Note::middle()->get();
$allBaseNotes = Note::base()->get();
```

### Filter Products by Brand

```php
// By brand ID
$diorPerfumes = Product::where('brand_id', $brand->id)->get();

// By brand slug
$diorPerfumes = Product::whereHas('brand', function($q) {
    $q->where('slug', 'dior');
})->get();
```

### Filter Products by Note

```php
$roseProducts = Product::whereHas('notes', function($q) {
    $q->where('name', 'Rose');
})->get();
```

### Filter Products by Accord

```php
$woodyPerfumes = Product::whereHas('accords', function($q) {
    $q->where('slug', 'woody');
})->get();
```

### Complex Filtering (Brand + Accord)

```php
$diorWoodyPerfumes = Product::with(['brand', 'notes', 'accords'])
    ->whereHas('brand', fn($q) => $q->where('slug', 'dior'))
    ->whereHas('accords', fn($q) => $q->where('slug', 'woody'))
    ->get();
```

---

## 🌐 API-Ready Endpoints

### Example API Controller

Create: `app/Http/Controllers/Api/ProductController.php`

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['brand', 'notes', 'accords']);

        // Filter by brand
        if ($request->has('brand')) {
            $query->whereHas('brand', fn($q) =>
                $q->where('slug', $request->brand)
            );
        }

        // Filter by note
        if ($request->has('note')) {
            $query->whereHas('notes', fn($q) =>
                $q->where('name', $request->note)
            );
        }

        // Filter by accord
        if ($request->has('accord')) {
            $query->whereHas('accords', fn($q) =>
                $q->where('slug', $request->accord)
            );
        }

        return response()->json([
            'data' => $query->paginate(20)
        ]);
    }

    public function show(string $slug)
    {
        $product = Product::with(['brand', 'notes', 'accords'])
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json([
            'data' => $product
        ]);
    }
}
```

### API Routes

Add to `routes/api.php`:

```php
use App\Http\Controllers\Api\ProductController;

Route::prefix('v1')->group(function () {
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{slug}', [ProductController::class, 'show']);
});
```

### Example API Requests

```
GET /api/v1/products
GET /api/v1/products?brand=dior
GET /api/v1/products?note=Rose
GET /api/v1/products?accord=woody
GET /api/v1/products?brand=chanel&accord=floral
GET /api/v1/products/sauvage-edp
```

---

## 📱 JSON Response Example

```json
{
  "data": {
    "id": 1,
    "name": "Sauvage Eau de Parfum",
    "slug": "sauvage-edp",
    "description": "A powerful and noble fragrance...",
    "price": "425.00",
    "brand": {
      "id": 1,
      "name": "Dior",
      "slug": "dior",
      "logo": "brands/dior.png"
    },
    "notes": [
      {
        "id": 1,
        "name": "Bergamot",
        "type": "top"
      },
      {
        "id": 5,
        "name": "Pink Pepper",
        "type": "top"
      },
      {
        "id": 29,
        "name": "Lavender",
        "type": "top"
      },
      {
        "id": 45,
        "name": "Patchouli",
        "type": "base"
      },
      {
        "id": 43,
        "name": "Amber",
        "type": "base"
      }
    ],
    "accords": [
      {
        "id": 1,
        "name": "Woody",
        "slug": "woody",
        "pivot": {
          "percentage": 60
        }
      },
      {
        "id": 3,
        "name": "Fresh",
        "slug": "fresh",
        "pivot": {
          "percentage": 25
        }
      },
      {
        "id": 8,
        "name": "Aromatic",
        "slug": "aromatic",
        "pivot": {
          "percentage": 15
        }
      }
    ]
  }
}
```

---

## 🧪 Testing in Tinker

```php
php artisan tinker

// Create test brand
$brand = Brand::create(['name' => 'Chanel', 'slug' => 'chanel', 'status' => true]);

// Create test product
$product = Product::create([
    'name' => 'Coco Mademoiselle',
    'slug' => 'coco-mademoiselle',
    'price' => 495,
    'brand_id' => $brand->id,
    'status' => true
]);

// Attach notes
$rose = Note::where('name', 'Rose')->first();
$jasmine = Note::where('name', 'Jasmine')->first();
$patchouli = Note::where('name', 'Patchouli')->first();
$product->notes()->attach([$rose->id, $jasmine->id, $patchouli->id]);

// Attach accords
$floral = Accord::where('slug', 'floral')->first();
$product->accords()->attach([$floral->id => ['percentage' => 70]]);

// Query
$product->fresh()->load(['brand', 'topNotes', 'middleNotes', 'baseNotes', 'accords']);
```

---

## 📈 Available Notes Reference

### Top Notes (20)
Bergamot, Lemon, Lavender, Mandarin Orange, Pink Pepper, Grapefruit, Neroli, Cardamom, Ginger, Mint, Lemon Verbena, Petitgrain, Orange Blossom, Lime, Blackcurrant, Apple, Pineapple, Melon, Peach, Pear

### Middle Notes (20)
Rose, Jasmine, Iris, Geranium, Lily, Violet, Lily-of-the-Valley, Ylang-Ylang, Tuberose, Magnolia, Freesia, Peony, Orchid, Cinnamon, Nutmeg, Clove, Heliotrope, Rosemary, Thyme, Sage

### Base Notes (20)
Sandalwood, Musk, Amber, Vanilla, Patchouli, Cedarwood, Vetiver, Oakmoss, Tonka Bean, Benzoin, Incense, Leather, Oud, Labdanum, Ambergris, Guaiac Wood, Cashmere Wood, White Musk, Dark Musk, Tobacco

### Accords (20)
Woody, Floral, Fresh, Oriental, Citrus, Spicy, Aquatic, Aromatic, Fruity, Green, Powdery, Amber, Musky, Earthy, Smoky, Sweet, Leathery, Balsamic, Aldehydic, Gourmand

---

## 🎨 Frontend Display Example

### Blade Template

```blade
<div class="perfume-card">
    <h2>{{ $product->name }}</h2>
    <p class="brand">{{ $product->brand->name }}</p>
    <p class="price">AED {{ number_format($product->price, 2) }}</p>

    <div class="notes">
        <div class="note-category">
            <h4>Top Notes</h4>
            <ul>
                @foreach($product->topNotes as $note)
                    <li>{{ $note->name }}</li>
                @endforeach
            </ul>
        </div>

        <div class="note-category">
            <h4>Middle Notes</h4>
            <ul>
                @foreach($product->middleNotes as $note)
                    <li>{{ $note->name }}</li>
                @endforeach
            </ul>
        </div>

        <div class="note-category">
            <h4>Base Notes</h4>
            <ul>
                @foreach($product->baseNotes as $note)
                    <li>{{ $note->name }}</li>
                @endforeach
            </ul>
        </div>
    </div>

    <div class="accords">
        <h4>Main Accords</h4>
        @foreach($product->accords as $accord)
            <div class="accord-bar">
                <span>{{ $accord->name }}</span>
                <div class="progress" style="width: {{ $accord->pivot->percentage }}%"></div>
            </div>
        @endforeach
    </div>
</div>
```

---

## 🚀 Next Steps

1. **Create Admin Panel** - Use Laravel Nova or Filament to manage brands, products, notes
2. **Build Frontend** - Create product listing, filters, detail pages
3. **Add Images** - Multiple product images, brand logos
4. **Implement Search** - Laravel Scout with Algolia/Meilisearch
5. **Add Reviews** - Product reviews and ratings system
6. **Stock Management** - Real-time stock tracking
7. **Shopify Migration** - Import products from your current store

---

## 💡 Pro Tips

- Always eager load relationships to avoid N+1 queries
- Use API Resources for cleaner JSON responses
- Cache expensive queries (brand lists, accord lists)
- Add indexes on frequently filtered columns
- Consider adding product variants (sizes: 50ml, 100ml)
- Implement soft deletes for products and brands
- Add product SKUs for inventory management

---

## 📞 Need Help?

Reference the approved plan at:
`C:\Users\user\.claude\plans\effervescent-doodling-valiant.md`

All migrations, models, and seeders are production-ready and follow Laravel best practices.
