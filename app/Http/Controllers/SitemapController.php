<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $xml = Cache::remember('sitemap_xml', 3600, function () {
            return $this->generateSitemap();
        });

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }

    private function generateSitemap(): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        // Homepage
        $xml .= $this->urlEntry(url('/'), now()->toW3cString(), 'daily', '1.0');

        // Products listing
        $xml .= $this->urlEntry(route('products.index'), null, 'daily', '0.8');

        // Brands listing
        $xml .= $this->urlEntry(route('brands.index'), null, 'weekly', '0.7');

        // Static pages
        $staticRoutes = [
            'about', 'contact', 'terms', 'return-policy',
            'shipping-policy', 'privacy-policy', 'wholesale',
            'flash-sale', 'gift-cards',
        ];

        foreach ($staticRoutes as $routeName) {
            $xml .= $this->urlEntry(route($routeName), null, 'monthly', '0.5');
        }

        // Individual products
        $products = Product::where('status', true)
            ->select('slug', 'updated_at')
            ->orderBy('updated_at', 'desc')
            ->get();

        foreach ($products as $product) {
            $xml .= $this->urlEntry(
                route('products.show', $product->slug),
                $product->updated_at->toW3cString(),
                'weekly',
                '0.8'
            );
        }

        // Individual brands
        $brands = Brand::where('status', true)
            ->select('slug', 'updated_at')
            ->orderBy('name')
            ->get();

        foreach ($brands as $brand) {
            $xml .= $this->urlEntry(
                route('products.byBrand', $brand->slug),
                $brand->updated_at->toW3cString(),
                'weekly',
                '0.7'
            );
        }

        // Categories
        $categories = Category::where('is_active', true)
            ->select('slug', 'updated_at')
            ->get();

        foreach ($categories as $category) {
            $xml .= $this->urlEntry(
                url("/categories/{$category->slug}"),
                $category->updated_at->toW3cString(),
                'weekly',
                '0.6'
            );
        }

        // Blog index
        $xml .= $this->urlEntry(route('blog.index'), null, 'daily', '0.7');

        // Blog posts
        $blogPosts = BlogPost::where('status', 'published')
            ->select('slug', 'updated_at')
            ->orderBy('published_at', 'desc')
            ->get();

        foreach ($blogPosts as $post) {
            $xml .= $this->urlEntry(
                route('blog.show', $post->slug),
                $post->updated_at->toW3cString(),
                'weekly',
                '0.7'
            );
        }

        $xml .= '</urlset>';

        return $xml;
    }

    private function urlEntry(string $loc, ?string $lastmod, string $changefreq, string $priority): string
    {
        $entry = '<url>';
        $entry .= '<loc>' . htmlspecialchars($loc) . '</loc>';

        if ($lastmod) {
            $entry .= '<lastmod>' . $lastmod . '</lastmod>';
        }

        $entry .= '<changefreq>' . $changefreq . '</changefreq>';
        $entry .= '<priority>' . $priority . '</priority>';
        $entry .= '</url>';

        return $entry;
    }
}
