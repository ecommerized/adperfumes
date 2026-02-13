<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ScrapeShopifyImages extends Command
{
    protected $signature = 'products:scrape-images {--delay=200 : Delay between image downloads in ms}';
    protected $description = 'Scrape product images from Shopify store for products missing images';

    protected $updated = 0;
    protected $skipped = 0;
    protected $failed = 0;

    public function handle()
    {
        $missing = Product::with('brand')
            ->where(function ($q) {
                $q->whereNull('image')->orWhere('image', '');
            })
            ->get();

        $this->info("Products missing images: {$missing->count()}");
        $this->info("Fetching product catalog from Shopify...");
        $this->newLine();

        // Build lookup maps for matching
        $byNormalizedName = [];
        $bySlug = [];
        foreach ($missing as $product) {
            $key = $this->normalizeProductName($product->name);
            $byNormalizedName[$key] = $product;

            $slugKey = Str::slug($product->name);
            $bySlug[$slugKey] = $product;
        }

        // Fetch all Shopify products
        $page = 1;
        $perPage = 250;
        $totalShopify = 0;
        $matched = 0;

        while (true) {
            $url = "https://adperfumes.ae/products.json?limit={$perPage}&page={$page}";

            try {
                $response = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                ])->timeout(30)->get($url);

                if (!$response->successful()) {
                    $this->warn("Failed to fetch page {$page}: HTTP {$response->status()}");
                    break;
                }

                $data = $response->json();
                $products = $data['products'] ?? [];

                if (empty($products)) break;

                $totalShopify += count($products);

                foreach ($products as $shopifyProduct) {
                    $images = $shopifyProduct['images'] ?? [];
                    if (empty($images)) continue;

                    $imageUrl = $images[0]['src'] ?? null;
                    if (!$imageUrl) continue;

                    // Try to match to a local product missing an image
                    $localProduct = $this->matchProduct($shopifyProduct, $byNormalizedName, $bySlug);
                    if (!$localProduct) continue;

                    // Download and save image
                    $saved = $this->downloadImage($localProduct, $imageUrl);
                    if ($saved) {
                        $matched++;
                        // Remove from lookup maps so we don't match again
                        $key = $this->normalizeProductName($localProduct->name);
                        unset($byNormalizedName[$key]);
                        $slugKey = Str::slug($localProduct->name);
                        unset($bySlug[$slugKey]);
                    }
                }

                $this->line("  Page {$page}: {$totalShopify} Shopify products scanned, {$matched} images matched");

                $page++;
                usleep(300000); // 300ms between pages

            } catch (\Exception $e) {
                $this->error("Error on page {$page}: " . $e->getMessage());
                break;
            }
        }

        $this->newLine();
        $this->info("Shopify scan complete: {$totalShopify} products scanned across {$page} pages");
        $this->info("Images downloaded: {$this->updated}");
        $this->info("Failed downloads: {$this->failed}");

        $remaining = Product::where(function ($q) {
            $q->whereNull('image')->orWhere('image', '');
        })->count();
        $this->newLine();
        $this->info("Still missing images: {$remaining}");

        return 0;
    }

    protected function matchProduct(array $shopifyProduct, array &$byName, array &$bySlug): ?Product
    {
        $title = $shopifyProduct['title'] ?? '';
        $vendor = $shopifyProduct['vendor'] ?? '';
        $handle = $shopifyProduct['handle'] ?? '';

        // Match by normalized name
        $key = $this->normalizeProductName($title);
        if (isset($byName[$key])) {
            return $byName[$key];
        }

        // Match by vendor + title combo
        $vendorTitle = $this->normalizeProductName($vendor . ' ' . $title);
        if (isset($byName[$vendorTitle])) {
            return $byName[$vendorTitle];
        }

        // Match by slug/handle
        $slugKey = Str::slug($title);
        if (isset($bySlug[$slugKey])) {
            return $bySlug[$slugKey];
        }

        // Match by handle
        if (isset($bySlug[$handle])) {
            return $bySlug[$handle];
        }

        // Fuzzy: try matching by removing brand prefix and size suffixes
        $cleanTitle = $this->cleanProductName($title);
        $cleanKey = $this->normalizeProductName($cleanTitle);
        if (isset($byName[$cleanKey])) {
            return $byName[$cleanKey];
        }

        // Try matching vendor + clean title
        $vendorClean = $this->normalizeProductName($vendor . ' ' . $cleanTitle);
        if (isset($byName[$vendorClean])) {
            return $byName[$vendorClean];
        }

        return null;
    }

    protected function downloadImage(Product $product, string $imageUrl): bool
    {
        $delay = (int) $this->option('delay');

        try {
            usleep($delay * 1000);

            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            ])->timeout(30)->get($imageUrl);

            if (!$response->successful()) {
                $this->failed++;
                return false;
            }

            // Determine extension
            $contentType = $response->header('Content-Type') ?? '';
            $extension = match (true) {
                str_contains($contentType, 'webp') => 'webp',
                str_contains($contentType, 'png') => 'png',
                str_contains($contentType, 'gif') => 'gif',
                default => 'jpg',
            };

            $filename = Str::slug($product->name) . '.' . $extension;
            $path = 'products/' . $filename;

            Storage::disk('public')->put($path, $response->body());
            $product->update(['image' => $path]);

            $this->updated++;
            return true;

        } catch (\Exception $e) {
            $this->failed++;
            return false;
        }
    }

    protected function normalizeProductName(string $name): string
    {
        $name = strtolower($name);
        $name = preg_replace('/\s*\(?(edp|edt|edc|parfum|eau de parfum|eau de toilette|eau de cologne)\)?\s*/i', ' ', $name);
        $name = preg_replace('/\s*\d+\s*(ml|oz)\s*/i', ' ', $name);
        $name = preg_replace('/\s*(for\s+)?(men|women|unisex|him|her)\s*/i', ' ', $name);
        $name = preg_replace('/\s*(spray|tester|set|gift)\s*/i', ' ', $name);
        $name = preg_replace('/[^a-z0-9\s]/', '', $name);
        $name = preg_replace('/\s+/', ' ', $name);
        return trim($name);
    }

    protected function cleanProductName(string $name): string
    {
        // Remove size info like "100ml", "3.4 oz"
        $name = preg_replace('/\s*-?\s*\d+(\.\d+)?\s*(ml|oz)\s*/i', ' ', $name);
        // Remove concentration
        $name = preg_replace('/\s*\(?(EDP|EDT|EDC|Eau de Parfum|Eau de Toilette)\)?\s*/i', ' ', $name);
        // Remove gender
        $name = preg_replace('/\s*(for\s+)?(Men|Women|Unisex|Him|Her)(\'?s?)?\s*/i', ' ', $name);
        // Remove extra descriptors
        $name = preg_replace('/\s*(Spray|Tester|Set|Gift|New|Limited)\s*/i', ' ', $name);
        return trim(preg_replace('/\s+/', ' ', $name));
    }
}
