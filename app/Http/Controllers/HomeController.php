<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Brand;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Display home page with featured products and brands
     */
    public function index()
    {
        $products = Product::with(['brand', 'accords'])
            ->where('status', true)
            ->latest()
            ->take(8)
            ->get();

        $brands = Brand::where('status', true)
            ->withCount('products')
            ->having('products_count', '>', 0)
            ->get();

        return view('home', compact('products', 'brands'));
    }
}
