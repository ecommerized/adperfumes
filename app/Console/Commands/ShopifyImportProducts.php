<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Product;
use App\Services\ShopifyService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ShopifyImportProducts extends Command
{
    protected $signature = 'shopify:import-products {--limit=0 : Limit number of products to import (0 = all)}';
    protected $description = 'Import products from Shopify store';

    protected $shopify;
    protected $importedCount = 0;
    protected $skippedCount = 0;
    protected $errorCount = 0;

    public function handle()
    {
        $this->shopify = new ShopifyService();

        $this->info('🚀 Starting Shopify product import...');
        $this->newLine();

        // Process products in batches to avoid memory issues
        $this->info('📦 Fetching and processing products from Shopify...');

        $pageInfo = null;
        $totalProcessed = 0;
        $limit = (int) $this->option('limit');
        $batchSize = 250;

        do {
            // Fetch one page of products
            $data = $this->shopify->getProducts($batchSize, $pageInfo);
            $products = $data['products'];

            if (empty($products)) {
                break;
            }

            $this->info("Processing batch of " . count($products) . " products (Total: {$totalProcessed})");

            // Process each product in this batch
            foreach ($products as $shopifyProduct) {
                if ($limit > 0 && $totalProcessed >= $limit) {
                    break 2; // Break out of both loops
                }

                try {
                    $imported = $this->importProduct($shopifyProduct);
                    // Don't increment importedCount here - importProduct handles its own counters
                } catch (\Exception $e) {
                    $this->errorCount++;
                    $this->error("✗ Error importing '{$shopifyProduct['title']}': {$e->getMessage()}");
                }

                $totalProcessed++;

                // Show progress every 10 products
                if ($totalProcessed % 10 === 0) {
                    $this->info("  → Processed {$totalProcessed} products (Imported: {$this->importedCount}, Skipped: {$this->skippedCount}, Errors: {$this->errorCount})");
                }
            }

            // Get next page info
            $pageInfo = $this->shopify->extractNextPageInfo($data['link'] ?? null);

            // Free memory
            unset($products, $data);
            gc_collect_cycles();

            // Sleep to respect rate limits
            usleep(500000); // 0.5 seconds

        } while ($pageInfo !== null && ($limit === 0 || $totalProcessed < $limit));

        $this->newLine();
        $this->info('✅ Import completed!');
        $this->newLine();
        $this->table(
            ['Status', 'Count'],
            [
                ['✓ Imported', $this->importedCount],
                ['○ Skipped (duplicates)', $this->skippedCount],
                ['✗ Errors', $this->errorCount],
                ['Total Processed', $totalProcessed],
            ]
        );

        return 0;
    }

    protected function importProduct($shopifyProduct)
    {
        // Clean product title (remove quotes and trim)
        $productTitle = trim($shopifyProduct['title'], " \t\n\r\0\x0B\"'");

        // Extract brand from product title or vendor
        $brandName = $this->extractBrandFromTitle($productTitle, $shopifyProduct['vendor'] ?? 'Unknown');
        $brand = $this->getOrCreateBrand($brandName);

        // Check if product already exists (by Shopify ID)
        $existingProduct = Product::where('shopify_id', $shopifyProduct['id'])->first();

        if ($existingProduct) {
            $this->skippedCount++;
            return;
        }

        // Get first variant for pricing
        $variant = $shopifyProduct['variants'][0] ?? null;
        $price = $variant ? floatval($variant['price']) : 0;
        $compareAtPrice = $variant ? floatval($variant['compare_at_price'] ?? 0) : 0;

        // Download first image
        $imagePath = null;
        if (!empty($shopifyProduct['images'])) {
            $imageUrl = $shopifyProduct['images'][0]['src'];
            $imagePath = $this->downloadProductImage($imageUrl, $shopifyProduct['id']);
        }

        // Generate unique slug (append Shopify ID if duplicate exists)
        $baseSlug = Str::slug($productTitle);
        $slug = $baseSlug;

        // Check if slug exists, append Shopify ID to make it unique
        if (Product::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $shopifyProduct['id'];
        }

        // Create product
        Product::create([
            'shopify_id' => $shopifyProduct['id'],
            'name' => $productTitle,
            'slug' => $slug,
            'description' => strip_tags($shopifyProduct['body_html'] ?? ''),
            'price' => $price,
            'original_price' => $compareAtPrice > 0 ? $compareAtPrice : null,
            'on_sale' => $compareAtPrice > $price,
            'stock' => $variant['inventory_quantity'] ?? 0,
            'brand_id' => $brand->id,
            'image' => $imagePath,
            'status' => $shopifyProduct['status'] === 'active',
            'is_new' => false, // Set manually later if needed
        ]);

        $this->importedCount++;
    }

    protected function extractBrandFromTitle($productTitle, $vendor)
    {
        // List of known perfume brands to extract from product titles
        $knownBrands = [
            'Initio', 'Dior', 'Chanel', 'Tom Ford', 'Creed', 'Armani', 'Gucci',
            'Versace', 'Prada', 'Burberry', 'YSL', 'Givenchy', 'Hermes',
            'Dolce & Gabbana', 'Carolina Herrera', 'Viktor & Rolf', 'Lancome',
            'Narciso Rodriguez', 'Jean Paul Gaultier', 'Thierry Mugler', 'Kilian',
            'Maison Francis Kurkdjian', 'Byredo', 'Amouage', 'Roja', 'Parfums de Marly',
            'Montale', 'Mancera', 'Attar Collection', 'Lattafa', 'Ajmal', 'Rasasi',
            'Swiss Arabian', 'Afnan', 'Armaf', 'Nishane', 'Xerjoff', 'Tiziana Terenzi',
            'Loui Martin', 'Bvlgari', 'Cartier', 'Acqua di Parma', 'Penhaligon',
        ];

        // Check if product title starts with any known brand
        foreach ($knownBrands as $brand) {
            if (stripos($productTitle, $brand) === 0 || stripos($productTitle, $brand . '-') === 0) {
                return $brand;
            }
        }

        // If vendor is "Abudhabi Store" or similar, use "AD Perfumes"
        if (stripos($vendor, 'abudhabi store') !== false || stripos($vendor, 'ads') !== false) {
            return 'AD Perfumes';
        }

        // Otherwise use vendor name
        return $vendor;
    }

    protected function getOrCreateBrand($brandName)
    {
        // Normalize brand name
        $brandName = trim($brandName);

        // Replace Abudhabi Store variations with AD Perfumes
        if (stripos($brandName, 'abudhabi store') !== false || $brandName === 'ADS') {
            $brandName = 'AD Perfumes';
        }

        $brand = Brand::where('name', $brandName)->first();

        if (!$brand) {
            $brand = Brand::create([
                'name' => $brandName,
                'slug' => Str::slug($brandName),
                'description' => null,
                'status' => true,
            ]);
        }

        return $brand;
    }

    protected function downloadProductImage($imageUrl, $productId)
    {
        try {
            $imageData = $this->shopify->downloadImage($imageUrl);

            if (!$imageData) {
                return null;
            }

            // Extract file extension from URL
            $extension = pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION);
            $extension = $extension ?: 'jpg';

            // Generate filename
            $filename = 'products/' . uniqid('product_' . $productId . '_') . '.' . $extension;

            // Store image
            Storage::disk('public')->put($filename, $imageData);

            return $filename;

        } catch (\Exception $e) {
            $this->warn("Failed to download image: {$imageUrl}");
            return null;
        }
    }
}
