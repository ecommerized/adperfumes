<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Brand;
use App\Models\Accord;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display product listing with filters
     */
    public function index(Request $request)
    {
        $query = Product::with(['brand', 'accords'])->where('status', true);

        // Search by name, description, or brand
        if ($request->filled('q')) {
            $search = $request->q;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('brand', fn($bq) => $bq->where('name', 'like', "%{$search}%"));
            });
        }

        // Filter by brand
        if ($request->filled('brand')) {
            $query->whereHas('brand', fn($q) => $q->where('slug', $request->brand));
        }

        // Filter by accord
        if ($request->filled('accord')) {
            $query->whereHas('accords', fn($q) => $q->where('slug', $request->accord));
        }

        // Filter by price range
        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        // Sort
        match ($request->get('sort')) {
            'price_asc' => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'name_asc' => $query->orderBy('name', 'asc'),
            'newest' => $query->orderBy('created_at', 'desc'),
            default => $query->orderBy('created_at', 'desc'),
        };

        $products = $query->paginate(12)->withQueryString();
        $brands = Brand::where('status', true)->get();
        $accords = Accord::all();

        return view('products.index', compact('products', 'brands', 'accords'));
    }

    /**
     * Display product detail page
     */
    public function show($slug)
    {
        $product = Product::with(['brand', 'topNotes', 'middleNotes', 'baseNotes', 'accords', 'seoMeta'])
            ->where('slug', $slug)
            ->where('status', true)
            ->firstOrFail();

        // Related products from same brand
        $relatedProducts = Product::with(['brand', 'accords'])
            ->where('brand_id', $product->brand_id)
            ->where('id', '!=', $product->id)
            ->where('status', true)
            ->take(4)
            ->get();

        return view('products.show', [
            'product' => $product,
            'relatedProducts' => $relatedProducts,
            'seoModel' => $product,
        ]);
    }

    /**
     * Display all brands
     */
    public function brands()
    {
        $brands = Brand::where('status', true)
            ->withCount('products')
            ->having('products_count', '>', 0)
            ->orderBy('name', 'asc')
            ->get();

        return view('brands.index', compact('brands'));
    }

    /**
     * Display products by brand
     */
    public function byBrand($slug)
    {
        $brand = Brand::with('seoMeta')->where('slug', $slug)->where('status', true)->firstOrFail();

        $products = Product::with(['brand', 'accords'])
            ->where('brand_id', $brand->id)
            ->where('status', true)
            ->paginate(12);

        $accords = Accord::all();

        return view('products.by-brand', [
            'brand' => $brand,
            'products' => $products,
            'accords' => $accords,
            'seoModel' => $brand,
        ]);
    }
}
