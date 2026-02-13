<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Brand;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

echo "=== Fetching Brand Logos from Individual Collection Pages ===\n\n";

// Get all brands without logos
$brands = Brand::whereNull('logo')->orderBy('name')->get();
echo "Brands needing logos: " . $brands->count() . "\n\n";

$imported = 0;
$failed = 0;
$total = $brands->count();
$current = 0;

foreach ($brands as $brand) {
    $current++;
    echo "[$current/$total] {$brand->name} ({$brand->slug})... ";

    // Try the collection page on the live site
    $collectionUrl = "https://adperfumes.ae/collections/" . $brand->slug;

    try {
        $response = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        ])->timeout(15)->get($collectionUrl);

        if (!$response->successful()) {
            // Try alternate slug formats
            $altSlugs = [
                Str::slug($brand->name),
                strtolower(str_replace([' ', '&', '.', "'"], ['-', '', '', ''], $brand->name)),
                strtolower(str_replace([' ', '&', '.', "'"], ['-', '-and-', '', ''], $brand->name)),
            ];

            $found = false;
            foreach (array_unique($altSlugs) as $altSlug) {
                if ($altSlug === $brand->slug) continue;
                $altUrl = "https://adperfumes.ae/collections/" . $altSlug;
                $response = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                ])->timeout(15)->get($altUrl);

                if ($response->successful()) {
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                echo "NOT FOUND\n";
                $failed++;
                continue;
            }
        }

        $html = $response->body();

        // Try to find collection/brand image
        $imgUrl = null;

        // Method 1: Look for og:image meta tag (usually the collection image)
        if (preg_match('/<meta\s+property="og:image"\s+content="([^"]+)"/i', $html, $m)) {
            $imgUrl = $m[1];
        }
        if (!$imgUrl && preg_match('/<meta\s+content="([^"]+)"\s+property="og:image"/i', $html, $m)) {
            $imgUrl = $m[1];
        }

        // Method 2: Look for collection-hero image
        if (!$imgUrl && preg_match('/class="[^"]*collection[^"]*hero[^"]*"[^>]*>.*?<img[^>]+src="([^"]+)"/is', $html, $m)) {
            $imgUrl = $m[1];
        }

        // Method 3: Look for collection image in header/banner
        if (!$imgUrl && preg_match('/<img[^>]+src="([^"]+cdn\/shop\/collections\/[^"]+)"/i', $html, $m)) {
            $imgUrl = $m[1];
        }

        // Method 4: Look for collection image anywhere
        if (!$imgUrl && preg_match('/cdn\/shop\/collections\/([^"\s?]+)/i', $html, $m)) {
            $imgUrl = "https://adperfumes.ae/cdn/shop/collections/" . $m[1];
        }

        if (!$imgUrl) {
            echo "NO IMAGE FOUND\n";
            $failed++;
            continue;
        }

        // Make URL absolute
        if (strpos($imgUrl, '//') === 0) {
            $imgUrl = 'https:' . $imgUrl;
        }

        // Try higher resolution
        $imgUrl = preg_replace('/_\d+x(\.\w+)/', '$1', $imgUrl);

        // Download the image
        $imgResponse = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        ])->timeout(15)->get($imgUrl);

        if (!$imgResponse->successful()) {
            // Try with _600x
            $imgUrl = preg_replace('/(\.\w+)(\?|$)/', '_600x$1$2', $imgUrl);
            $imgResponse = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0',
            ])->timeout(15)->get($imgUrl);

            if (!$imgResponse->successful()) {
                echo "DOWNLOAD FAILED\n";
                $failed++;
                continue;
            }
        }

        // Verify it's an image
        $contentType = $imgResponse->header('Content-Type') ?? '';
        if (!str_contains($contentType, 'image')) {
            echo "NOT AN IMAGE\n";
            $failed++;
            continue;
        }

        // Get extension
        $extMap = [
            'image/jpeg' => 'jpg', 'image/jpg' => 'jpg',
            'image/png' => 'png', 'image/webp' => 'webp',
            'image/gif' => 'gif', 'image/svg+xml' => 'svg',
        ];
        $ext = $extMap[strtolower(explode(';', $contentType)[0])] ?? 'jpg';

        // Save
        $filename = $brand->slug . '.' . $ext;
        $path = 'brands/' . $filename;
        Storage::disk('public')->put($path, $imgResponse->body());
        $brand->update(['logo' => $path]);

        echo "✓ IMPORTED ($path)\n";
        $imported++;

    } catch (\Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
        $failed++;
    }

    // Small delay to be respectful
    usleep(200000); // 200ms
}

echo "\n=== FINAL RESULTS ===\n";
echo "Logos imported: $imported\n";
echo "Failed/Not found: $failed\n";
echo "\nTotal brands: " . Brand::count() . "\n";
echo "With logos: " . Brand::whereNotNull('logo')->count() . "\n";
echo "Without logos: " . Brand::whereNull('logo')->count() . "\n";
