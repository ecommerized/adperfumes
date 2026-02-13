<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Brand;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

echo "=== Final Logo Fetch - Official Websites ===\n\n";

// Direct URL mappings for remaining brands
$directLogos = [
    'afnan' => [
        'https://afnanperfumes.com',
        'https://www.afnanperfumes.com',
    ],
    'armaf' => [
        'https://armaf.co',
        'https://www.armaf.co',
        'https://armafperfume.com',
    ],
    'chanel' => [
        'https://www.chanel.com',
    ],
    'givenchy' => [
        'https://www.givenchybeauty.com',
        'https://www.givenchy.com',
    ],
    'guess' => [
        'https://www.guess.com',
    ],
    'jean-paul-gaultier' => [
        'https://www.jeanpaulgaultier.com',
    ],
    'room-1015' => [
        'https://room1015.com',
        'https://www.room1015.com',
    ],
    'six-scents' => [
        'https://sixscents.com',
    ],
];

$brands = Brand::whereNull('logo')
    ->whereNotIn('slug', ['my-store', 'ad-perfumes'])
    ->orderBy('name')
    ->get();

echo "Remaining brands: " . $brands->count() . "\n\n";
$imported = 0;
$failed = 0;

foreach ($brands as $brand) {
    echo "{$brand->name}... ";

    $imgUrl = null;
    $sites = $directLogos[$brand->slug] ?? ["https://www.{$brand->slug}.com", "https://{$brand->slug}.com"];

    foreach ($sites as $site) {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.5',
            ])->timeout(10)
              ->withOptions(['allow_redirects' => ['max' => 3]])
              ->get($site);

            if (!$response->successful()) continue;

            $html = $response->body();
            $baseUrl = $site;

            // Strategy 1: og:image meta tag
            if (preg_match('/<meta[^>]+property=["\']og:image["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $m)) {
                $imgUrl = $m[1];
            } elseif (preg_match('/<meta[^>]+content=["\']([^"\']+)["\'][^>]+property=["\']og:image["\']/i', $html, $m)) {
                $imgUrl = $m[1];
            }

            // Strategy 2: Look for logo in common patterns
            if (!$imgUrl) {
                $patterns = [
                    '/<img[^>]+(?:class|id)=["\'][^"\']*logo[^"\']*["\'][^>]+src=["\']([^"\']+)["\']/i',
                    '/<img[^>]+src=["\']([^"\']+)["\'][^>]+(?:class|id)=["\'][^"\']*logo[^"\']*["\']/i',
                    '/<a[^>]+(?:class|id)=["\'][^"\']*logo[^"\']*["\'][^>]*>.*?<img[^>]+src=["\']([^"\']+)["\']/is',
                    '/class=["\'][^"\']*header[^"\']*logo[^"\']*["\'][^>]*>.*?<img[^>]+src=["\']([^"\']+)["\']/is',
                    '/<link[^>]+rel=["\'](?:icon|apple-touch-icon)["\'][^>]+href=["\']([^"\']+)["\']/i',
                ];

                foreach ($patterns as $pattern) {
                    if (preg_match($pattern, $html, $m)) {
                        $imgUrl = $m[1];
                        break;
                    }
                }
            }

            // Strategy 3: Apple touch icon (high quality)
            if (!$imgUrl && preg_match('/<link[^>]+rel=["\']apple-touch-icon["\'][^>]+href=["\']([^"\']+)["\']/i', $html, $m)) {
                $imgUrl = $m[1];
            }

            if ($imgUrl) {
                // Make absolute
                if (strpos($imgUrl, '//') === 0) {
                    $imgUrl = 'https:' . $imgUrl;
                } elseif (strpos($imgUrl, '/') === 0) {
                    $imgUrl = $baseUrl . $imgUrl;
                } elseif (strpos($imgUrl, 'http') !== 0) {
                    $imgUrl = $baseUrl . '/' . $imgUrl;
                }
                break;
            }

        } catch (\Exception $e) {
            continue;
        }
    }

    // Last resort: Try Google favicon service
    if (!$imgUrl) {
        $domainMap = [
            'afnan' => 'afnanperfumes.com',
            'armaf' => 'armaf.co',
            'chanel' => 'chanel.com',
            'givenchy' => 'givenchy.com',
            'guess' => 'guess.com',
            'jean-paul-gaultier' => 'jeanpaulgaultier.com',
            'room-1015' => 'room1015.com',
            'six-scents' => 'sixscents.com',
        ];

        $domain = $domainMap[$brand->slug] ?? $brand->slug . '.com';
        $faviconUrl = "https://www.google.com/s2/favicons?domain={$domain}&sz=128";

        try {
            $testResp = Http::timeout(5)->get($faviconUrl);
            if ($testResp->successful() && strlen($testResp->body()) > 500) {
                $imgUrl = $faviconUrl;
            }
        } catch (\Exception $e) {
            // Skip
        }
    }

    if (!$imgUrl) {
        echo "NOT FOUND\n";
        $failed++;
        continue;
    }

    // Download
    try {
        $imgResponse = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        ])->timeout(15)->get($imgUrl);

        if (!$imgResponse->successful()) {
            echo "DOWNLOAD FAILED\n";
            $failed++;
            continue;
        }

        $contentType = $imgResponse->header('Content-Type') ?? '';
        if (!str_contains($contentType, 'image') && !str_contains($contentType, 'svg')) {
            echo "NOT IMAGE ($contentType)\n";
            $failed++;
            continue;
        }

        $extMap = [
            'image/jpeg' => 'jpg', 'image/jpg' => 'jpg',
            'image/png' => 'png', 'image/webp' => 'webp',
            'image/gif' => 'gif', 'image/svg+xml' => 'svg',
            'image/x-icon' => 'png', 'image/vnd.microsoft.icon' => 'png',
        ];
        $ext = $extMap[strtolower(explode(';', $contentType)[0])] ?? 'png';

        $path = 'brands/' . $brand->slug . '.' . $ext;
        Storage::disk('public')->put($path, $imgResponse->body());
        $brand->update(['logo' => $path]);

        echo "✓ IMPORTED ($path)\n";
        $imported++;

    } catch (\Exception $e) {
        echo "ERROR\n";
        $failed++;
    }

    usleep(500000);
}

echo "\n=== FINAL RESULTS ===\n";
echo "Imported: $imported\n";
echo "Failed: $failed\n";
echo "\nTotal brands: " . Brand::count() . "\n";
echo "With logos: " . Brand::whereNotNull('logo')->count() . "\n";
echo "Without logos: " . Brand::whereNull('logo')->count() . "\n";

// List any remaining
$remaining = Brand::whereNull('logo')->whereNotIn('slug', ['my-store', 'ad-perfumes'])->pluck('name')->toArray();
if (!empty($remaining)) {
    echo "\nRemaining brands without logos:\n";
    foreach ($remaining as $name) {
        echo "  - $name\n";
    }
}
