<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Brand;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

// Fetch the brands page
echo "Fetching brands page from live site...\n";

$response = Http::withHeaders([
    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
])->timeout(30)->get('https://adperfumes.ae/pages/brands');

if (!$response->successful()) {
    echo "Failed to fetch page: HTTP " . $response->status() . "\n";
    exit(1);
}

$html = $response->body();
echo "Page fetched. Size: " . strlen($html) . " bytes\n";

// Parse HTML
$dom = new DOMDocument();
@$dom->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
$xpath = new DOMXPath($dom);

// Find ALL links to /collections/
$links = $xpath->query('//a[contains(@href, "/collections/")]');
$brands = [];

foreach ($links as $link) {
    $href = $link->getAttribute('href');
    if (preg_match('/\/collections\/([^\/\?\s\"\']+)/', $href, $m)) {
        $slug = $m[1];

        // Skip generic collections
        if (in_array($slug, ['all', 'frontpage', 'vendors', 'best-sellers', 'new-arrivals'])) {
            continue;
        }

        // Check for image in this link
        $imgs = $xpath->query('.//img', $link);
        $imgSrc = null;
        $imgAlt = null;

        if ($imgs->length > 0) {
            $img = $imgs->item(0);
            $imgSrc = $img->getAttribute('src');
            $imgAlt = $img->getAttribute('alt');

            // Check for lazy-loaded images
            if (empty($imgSrc) || strpos($imgSrc, 'data:image') === 0) {
                $imgSrc = $img->getAttribute('data-src')
                    ?: $img->getAttribute('data-lazy-src')
                    ?: $img->getAttribute('data-srcset');
            }

            // Skip site logo
            if ($imgSrc && strpos($imgSrc, 'LOGO-AD-Perfumens') !== false) {
                $imgSrc = null;
            }
        }

        // Get text content
        $text = trim($link->textContent);
        $text = preg_replace('/\s+/', ' ', $text);

        // Clean up the name
        $text = trim($text);

        if (!isset($brands[$slug])) {
            $brands[$slug] = [
                'slug' => $slug,
                'name' => $text ?: $imgAlt ?: ucwords(str_replace('-', ' ', $slug)),
                'img' => $imgSrc,
            ];
        } elseif ($imgSrc && !$brands[$slug]['img']) {
            $brands[$slug]['img'] = $imgSrc;
        }
    }
}

// Also look for images with collection URLs in their parent elements
$allImgs = $xpath->query('//img[contains(@src, "cdn/shop/collections")]');
foreach ($allImgs as $img) {
    $src = $img->getAttribute('src');
    $alt = $img->getAttribute('alt');

    // Skip logo
    if (strpos($src, 'LOGO-AD-Perfumens') !== false) continue;

    // Try to find parent link
    $parent = $img->parentNode;
    while ($parent && $parent->nodeName !== 'a') {
        $parent = $parent->parentNode;
    }

    if ($parent && $parent->nodeName === 'a') {
        $href = $parent->getAttribute('href');
        if (preg_match('/\/collections\/([^\/\?\s\"\']+)/', $href, $m)) {
            $slug = $m[1];
            if (!isset($brands[$slug])) {
                $brands[$slug] = [
                    'slug' => $slug,
                    'name' => $alt ?: ucwords(str_replace('-', ' ', $slug)),
                    'img' => $src,
                ];
            } elseif (!$brands[$slug]['img']) {
                $brands[$slug]['img'] = $src;
            }
        }
    }
}

$withImg = 0;
$withoutImg = 0;
foreach ($brands as $b) {
    if (!empty($b['img'])) $withImg++;
    else $withoutImg++;
}

echo "\n=== BRAND ANALYSIS ===\n";
echo "Total unique brands found: " . count($brands) . "\n";
echo "With images: $withImg\n";
echo "Without images (text links only): $withoutImg\n";

// Now download logos and update brands
echo "\n=== DOWNLOADING LOGOS ===\n";

$imported = 0;
$created = 0;
$skipped = 0;
$failed = 0;

foreach ($brands as $brandData) {
    $imgUrl = $brandData['img'] ?? null;

    if (empty($imgUrl)) {
        $skipped++;
        continue;
    }

    // Make URL absolute
    if (strpos($imgUrl, '//') === 0) {
        $imgUrl = 'https:' . $imgUrl;
    } elseif (strpos($imgUrl, '/') === 0) {
        $imgUrl = 'https://adperfumes.ae' . $imgUrl;
    }

    // Clean up URL - get higher resolution
    // Replace _300x with _600x for better quality
    $imgUrl = preg_replace('/_\d+x\./', '_600x.', $imgUrl);

    $slug = $brandData['slug'];
    $name = $brandData['name'];

    // Clean up brand name
    $name = trim($name);
    if (empty($name) || strlen($name) < 2) {
        $name = ucwords(str_replace('-', ' ', $slug));
    }

    // Find or create brand in database
    $brand = Brand::where('slug', $slug)->first();

    if (!$brand) {
        // Try name match
        $brand = Brand::whereRaw('LOWER(name) = ?', [strtolower($name)])->first();
    }

    if (!$brand) {
        // Try partial match
        $brand = Brand::whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($name) . '%'])->first();
    }

    // Special mappings
    $nameMap = [
        'christian-dior' => 'Dior',
        'roja-parfums' => 'Roja',
        'ysl' => 'Yves Saint Laurent',
        'maison-margiela' => 'Maison Margiela',
        'kilian-paris' => 'By Kilian',
    ];

    if (!$brand && isset($nameMap[$slug])) {
        $brand = Brand::whereRaw('LOWER(name) = ?', [strtolower($nameMap[$slug])])->first();
    }

    if (!$brand) {
        // Create new brand
        $brand = Brand::create([
            'name' => $name,
            'slug' => $slug,
            'status' => true,
        ]);
        $created++;
        echo "  + Created brand: $name\n";
    }

    // Skip if already has logo
    if ($brand->logo) {
        $skipped++;
        continue;
    }

    // Download logo
    try {
        $imgResponse = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        ])->timeout(15)->get($imgUrl);

        if (!$imgResponse->successful()) {
            // Try original size
            $origUrl = preg_replace('/_\d+x\./', '.', $imgUrl);
            $imgResponse = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            ])->timeout(15)->get($origUrl);

            if (!$imgResponse->successful()) {
                echo "  ✗ Failed to download: $name (HTTP " . $imgResponse->status() . ")\n";
                $failed++;
                continue;
            }
        }

        // Get extension
        $contentType = $imgResponse->header('Content-Type') ?? '';
        $extMap = [
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            'image/svg+xml' => 'svg',
        ];

        $ext = $extMap[strtolower(explode(';', $contentType)[0])] ?? null;
        if (!$ext) {
            $ext = pathinfo(parse_url($imgUrl, PHP_URL_PATH), PATHINFO_EXTENSION);
            $ext = preg_replace('/\?.*/', '', $ext);
        }
        if (empty($ext)) $ext = 'jpg';

        // Save file
        $filename = $brand->slug . '.' . $ext;
        $path = 'brands/' . $filename;
        Storage::disk('public')->put($path, $imgResponse->body());

        // Update brand
        $brand->update(['logo' => $path]);

        echo "  ✓ $name => $path\n";
        $imported++;

    } catch (\Exception $e) {
        echo "  ✗ Error for $name: " . $e->getMessage() . "\n";
        $failed++;
    }
}

echo "\n=== RESULTS ===\n";
echo "Logos imported: $imported\n";
echo "New brands created: $created\n";
echo "Skipped (no image or already has logo): $skipped\n";
echo "Failed downloads: $failed\n";
echo "\nTotal brands in database: " . Brand::count() . "\n";
echo "Brands with logos: " . Brand::whereNotNull('logo')->count() . "\n";
echo "Brands without logos: " . Brand::whereNull('logo')->count() . "\n";
