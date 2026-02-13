<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ScrapeShopifyGtins extends Command
{
    protected $signature = 'products:scrape-gtins {--source=shopify : Data source (shopify, barcodelookup)} {--delay=500 : Delay between requests in ms} {--debug : Show debug output}';
    protected $description = 'Scrape GTIN/EAN barcodes from Shopify store and barcode databases';

    protected $updated = 0;
    protected $skipped = 0;
    protected $notFound = 0;
    protected $debug = false;

    public function handle()
    {
        $this->debug = $this->option('debug');
        $source = $this->option('source');

        $this->info("Current products with GTIN: " . Product::whereNotNull('gtin')->count() . "/" . Product::count());
        $this->newLine();

        if ($source === 'shopify') {
            $this->scrapeFromShopify();
        } elseif ($source === 'barcodelookup') {
            $this->scrapeFromBarcodeLookup();
        } else {
            // Run all sources
            $this->scrapeFromShopify();
            $this->newLine();
            $this->scrapeFromBarcodeLookup();
        }

        $this->newLine();
        $this->info("Final: Products with GTIN: " . Product::whereNotNull('gtin')->count() . "/" . Product::count());

        return 0;
    }

    /**
     * Scrape GTINs from the live Shopify store (adperfumes.ae).
     * The store has EAN-13 codes stored in the variant SKU field.
     */
    protected function scrapeFromShopify(): void
    {
        $this->info('=== Scraping GTINs from Shopify store (adperfumes.ae) ===');

        $page = 1;
        $perPage = 250;
        $totalProcessed = 0;
        $totalUpdated = 0;
        $totalPages = 0;

        // Build a lookup map of local products by shopify_id and by normalized name
        $localProducts = Product::whereNull('gtin')
            ->with('brand')
            ->get();

        $byShopifyId = [];
        $byNormalizedName = [];

        foreach ($localProducts as $product) {
            if ($product->shopify_id) {
                $byShopifyId[$product->shopify_id] = $product;
            }
            $key = $this->normalizeProductName($product->name);
            $byNormalizedName[$key] = $product;
        }

        $this->info("Local products without GTIN: " . $localProducts->count());

        while (true) {
            $url = "https://adperfumes.ae/products.json?limit={$perPage}&page={$page}";

            try {
                $response = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                ])->timeout(30)->get($url);

                if (!$response->successful()) {
                    $this->warn("Failed to fetch page {$page}: HTTP " . $response->status());
                    break;
                }

                $data = $response->json();
                $products = $data['products'] ?? [];

                if (empty($products)) {
                    break;
                }

                $totalPages++;

                foreach ($products as $shopifyProduct) {
                    $totalProcessed++;
                    $gtin = $this->extractGtinFromShopifyProduct($shopifyProduct);

                    if (!$gtin) continue;

                    // Try to match to local product
                    $localProduct = $this->findLocalProduct(
                        $shopifyProduct,
                        $byShopifyId,
                        $byNormalizedName
                    );

                    if ($localProduct && !$localProduct->gtin) {
                        $localProduct->update(['gtin' => $gtin]);
                        $totalUpdated++;

                        if ($this->debug) {
                            $this->line("  GTIN: {$localProduct->name} => {$gtin}");
                        }
                    }
                }

                $this->line("  Page {$page}: processed {$totalProcessed} Shopify products, updated {$totalUpdated} GTINs");

                $page++;
                usleep((int) $this->option('delay') * 1000);

            } catch (\Exception $e) {
                $this->error("Error on page {$page}: " . $e->getMessage());
                break;
            }
        }

        $this->info("Shopify scrape complete: {$totalUpdated} GTINs imported from {$totalProcessed} products across {$totalPages} pages");
    }

    /**
     * Extract a valid GTIN from a Shopify product's variants.
     */
    protected function extractGtinFromShopifyProduct(array $product): ?string
    {
        foreach ($product['variants'] ?? [] as $variant) {
            // Check barcode field first
            $barcode = trim($variant['barcode'] ?? '');
            if ($this->isValidGtin($barcode)) {
                return $barcode;
            }

            // Check SKU field (some stores put EAN in SKU)
            $sku = trim($variant['sku'] ?? '');
            if ($this->isValidGtin($sku)) {
                return $sku;
            }
        }

        return null;
    }

    /**
     * Check if a string is a valid GTIN (EAN-8, EAN-13, UPC-A, or GTIN-14).
     */
    protected function isValidGtin(string $value): bool
    {
        if (empty($value)) return false;

        // Must be 8-14 digits only
        if (!preg_match('/^\d{8,14}$/', $value)) return false;

        // Validate check digit for EAN-13 (most common for perfumes)
        if (strlen($value) === 13) {
            return $this->validateEan13CheckDigit($value);
        }

        // For other lengths, just check it's all digits
        return true;
    }

    /**
     * Validate EAN-13 check digit.
     */
    protected function validateEan13CheckDigit(string $ean): bool
    {
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $digit = (int) $ean[$i];
            $sum += ($i % 2 === 0) ? $digit : $digit * 3;
        }
        $checkDigit = (10 - ($sum % 10)) % 10;
        return $checkDigit === (int) $ean[12];
    }

    /**
     * Find a local product matching a Shopify product.
     */
    protected function findLocalProduct(
        array $shopifyProduct,
        array &$byShopifyId,
        array &$byNormalizedName
    ): ?Product {
        $shopifyId = $shopifyProduct['id'] ?? null;

        // Match by Shopify ID
        if ($shopifyId && isset($byShopifyId[$shopifyId])) {
            return $byShopifyId[$shopifyId];
        }

        // Match by normalized product title
        $title = $shopifyProduct['title'] ?? '';
        $key = $this->normalizeProductName($title);
        if (isset($byNormalizedName[$key])) {
            return $byNormalizedName[$key];
        }

        // Try matching with brand + title combination
        $vendor = $shopifyProduct['vendor'] ?? '';
        if ($vendor) {
            $fullName = $vendor . ' ' . $title;
            $key2 = $this->normalizeProductName($fullName);
            if (isset($byNormalizedName[$key2])) {
                return $byNormalizedName[$key2];
            }
        }

        // Try DB search by slug
        $handle = $shopifyProduct['handle'] ?? '';
        if ($handle) {
            $product = Product::whereNull('gtin')
                ->where(function ($q) use ($handle, $title) {
                    $q->where('slug', 'LIKE', '%' . Str::slug($handle) . '%')
                      ->orWhere('name', 'LIKE', '%' . substr($title, 0, 30) . '%');
                })
                ->first();

            if ($product) return $product;
        }

        return null;
    }

    /**
     * Normalize a product name for comparison.
     */
    protected function normalizeProductName(string $name): string
    {
        $name = strtolower($name);
        $name = preg_replace('/[^a-z0-9]/', '', $name);
        return $name;
    }

    /**
     * Scrape GTINs from open barcode database for products still missing GTINs.
     */
    protected function scrapeFromBarcodeLookup(): void
    {
        $this->info('=== Looking up GTINs from Open EAN Database ===');

        $products = Product::whereNull('gtin')
            ->with('brand')
            ->whereNotNull('brand_id')
            ->get()
            ->filter(function ($product) {
                // Only try well-known brands (local/house brands won't have GTINs)
                $skipBrands = [
                    'AD Perfumes', 'My Store', 'Abu Dhabi Store', 'De Luxe Collection',
                    'Smile Perfumes', 'Quaintness', 'Laverne', 'Pensée', 'Madawi',
                    'SAMAM', 'Saman', 'Iven', 'NOSTOS', 'Kalikat', 'Derrah', 'Deraah',
                    'Lunatique', 'Basma', 'Foah', 'Dkhoun', 'Dukhoon', 'Doranza',
                    'DyRose Perfumes', 'Cavalier', 'Ateej', 'Anonymous', 'Violet',
                    'Zodiac', 'Niche Emarati', 'Signature', 'Marc Avento', 'Loui Martin',
                    'Panier', 'Box Asfer', 'Datura Perfumes', 'Mariya', 'Citrus Ester',
                    'Al Ghabra', 'Alghabra', 'Alrajul', 'Jo Milano',
                ];
                return !in_array($product->brand->name, $skipBrands);
            });

        $total = $products->count();
        $this->info("Products to look up: {$total}");

        if ($total === 0) {
            $this->info('No products need barcode lookup.');
            return;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% | Found: %message%');
        $bar->setMessage('0');
        $found = 0;

        $delay = (int) $this->option('delay');

        foreach ($products as $product) {
            $brandName = $product->brand->name;
            $cleanName = $this->cleanProductNameForSearch($product->name, $brandName);

            if (empty($cleanName)) {
                $bar->advance();
                continue;
            }

            $gtin = $this->lookupGtinOnline($brandName, $cleanName);

            if ($gtin) {
                $product->update(['gtin' => $gtin]);
                $found++;

                if ($this->debug) {
                    $this->newLine();
                    $this->line("  GTIN: {$product->name} => {$gtin}");
                }
            }

            $bar->setMessage((string) $found);
            $bar->advance();

            usleep($delay * 1000);
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Barcode lookup complete: {$found} GTINs found");
    }

    /**
     * Clean product name for barcode search.
     */
    protected function cleanProductNameForSearch(string $name, string $brandName): string
    {
        // Remove brand prefix
        if (stripos($name, $brandName) === 0) {
            $name = trim(substr($name, strlen($brandName)));
        }

        // Remove leading dashes
        $name = preg_replace('/^[\-\s]+/', '', $name);

        // Remove size info
        $name = preg_replace('/\s*\d+(?:\.\d+)?\s*(?:ml|oz|fl\.?\s*oz)\b.*/i', '', $name);

        // Remove "(Edp)", "(Edt)" etc
        $name = preg_replace('/\s*\([^)]*\)/i', '', $name);

        // Remove "Tester", "WOB", etc
        $name = preg_replace('/\s*(?:Tester|WOB|Travel\s*Pack|Gift\s*Set)\s*$/i', '', $name);

        return trim($name);
    }

    /**
     * Look up GTIN from open barcode databases.
     */
    protected function lookupGtinOnline(string $brand, string $productName): ?string
    {
        // Try Open EAN Database API (free, no auth required)
        $query = $brand . ' ' . $productName;

        try {
            // Use world.openfoodfacts.org API (supports cosmetics too)
            $searchUrl = 'https://world.openbeautyfacts.org/cgi/search.pl?search_terms='
                . urlencode($query)
                . '&search_simple=1&action=process&json=1&page_size=3';

            $response = Http::withHeaders([
                'User-Agent' => 'ADPerfumes-GtinLookup/1.0',
            ])->timeout(10)->get($searchUrl);

            if ($response->successful()) {
                $data = $response->json();
                $products = $data['products'] ?? [];

                foreach ($products as $result) {
                    $code = $result['code'] ?? '';
                    if ($this->isValidGtin($code)) {
                        // Verify it's actually a match (check brand name appears)
                        $resultBrands = strtolower($result['brands'] ?? '');
                        $resultName = strtolower($result['product_name'] ?? '');
                        $lowerBrand = strtolower($brand);

                        if (str_contains($resultBrands, $lowerBrand) || str_contains($resultName, $lowerBrand)) {
                            return $code;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // Silently continue
        }

        // Try UPCitemdb.com free API
        try {
            $searchUrl = 'https://api.upcitemdb.com/prod/trial/search?s='
                . urlencode($brand . ' ' . $productName)
                . '&type=2';

            $response = Http::withHeaders([
                'User-Agent' => 'ADPerfumes-GtinLookup/1.0',
                'Accept' => 'application/json',
            ])->timeout(10)->get($searchUrl);

            if ($response->successful()) {
                $data = $response->json();
                $items = $data['items'] ?? [];

                foreach ($items as $item) {
                    $ean = $item['ean'] ?? '';
                    if ($this->isValidGtin($ean)) {
                        // Verify brand match
                        $itemBrand = strtolower($item['brand'] ?? '');
                        $lowerBrand = strtolower($brand);
                        if (str_contains($itemBrand, $lowerBrand) || str_contains($lowerBrand, $itemBrand)) {
                            return $ean;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // Silently continue
        }

        return null;
    }
}
