<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Brand;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

echo "=== Fetching Remaining Brand Logos (Alternate Slugs) ===\n\n";

// Map of brand slug => array of alternate slugs to try on the live site
$altSlugs = [
    'afnan' => ['afnan-perfumes', 'afnan-fragrance'],
    'armaf' => ['armaf-perfumes', 'armaf-club-de-nuit'],
    'chanel' => ['chanel-perfume', 'chanel-paris', 'chanel-fragrance'],
    'caron' => ['caron-paris', 'caron-perfumes'],
    'emanuel-ungaro' => ['ungaro', 'emanuel-ungaro-paris'],
    'giorgio-armani' => ['armani', 'armani-beauty', 'armani-perfume'],
    'givenchy' => ['givenchy-perfume', 'givenchy-paris', 'givenchy-beauty'],
    'guess' => ['guess-perfume', 'guess-fragrance'],
    'jf-schwarzlose-berlin' => ['j-f-schwarzlose-berlin', 'schwarzlose-berlin', 'jf-schwarzlose'],
    'jean-paul-gaultier' => ['jean-paul-gaultier-fragrance', 'jpgaultier', 'jpg'],
    'maison-francis-kurkdjian' => ['mfk', 'francis-kurkdjian', 'maison-francis-kurkdjian-paris'],
    'memo-paris' => ['memo', 'memo-parfums'],
    'penhaligon' => ['penhaligons', 'penhaligon-s'],
    'room-1015' => ['room1015'],
    'santa-eulalia' => ['santa-eulalia-barcelona'],
    'six-scents' => ['6-scents'],
    'tauer-perfumes' => ['tauer', 'andy-tauer'],
    'trussardi' => ['trussardi-perfume', 'trussardi-fragrance'],
];

// Skip these - not real perfume brands
$skipBrands = ['my-store', 'ad-perfumes'];

$imported = 0;
$failed = 0;

$brands = Brand::whereNull('logo')
    ->whereNotIn('slug', $skipBrands)
    ->orderBy('name')
    ->get();

echo "Trying alternate slugs for " . $brands->count() . " brands...\n\n";

foreach ($brands as $brand) {
    $slugsToTry = $altSlugs[$brand->slug] ?? [];

    echo "{$brand->name}... ";

    $imgUrl = null;

    foreach ($slugsToTry as $slug) {
        $url = "https://adperfumes.ae/collections/" . $slug;

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            ])->timeout(10)->get($url);

            if (!$response->successful()) continue;

            $html = $response->body();

            // Look for og:image
            if (preg_match('/<meta\s+(?:property="og:image"\s+content|content)="([^"]+)"(?:\s+property="og:image")?/i', $html, $m)) {
                $candidate = $m[1];
                if (strpos($candidate, 'cdn/shop/collections') !== false || strpos($candidate, 'cdn/shop/files') !== false) {
                    $imgUrl = $candidate;
                    break;
                }
            }

            // Look for collection image
            if (preg_match('/cdn\/shop\/collections\/([^"\s?]+)/i', $html, $m)) {
                $imgUrl = "https://adperfumes.ae/cdn/shop/collections/" . $m[1];
                break;
            }
        } catch (\Exception $e) {
            continue;
        }
    }

    // If still no image, try Clearbit Logo API for well-known brands
    if (!$imgUrl) {
        $domainMap = [
            'chanel' => 'chanel.com',
            'giorgio-armani' => 'armani.com',
            'givenchy' => 'givenchy.com',
            'guess' => 'guess.com',
            'jean-paul-gaultier' => 'jeanpaulgaultier.com',
            'maison-francis-kurkdjian' => 'franciskurkdjian.com',
            'memo-paris' => 'memoparis.com',
            'trussardi' => 'trussardi.com',
            'caron' => 'parfumscaron.com',
            'emanuel-ungaro' => 'ungaro.com',
            'penhaligon' => 'penhaligons.com',
            'afnan' => 'afnanperfumes.com',
            'armaf' => 'armaf.co',
            'tauer-perfumes' => 'tauerperfumes.com',
            'room-1015' => 'room1015.com',
            'santa-eulalia' => 'santaeulalia.com',
            'jf-schwarzlose-berlin' => 'schwarzlose.com',
        ];

        if (isset($domainMap[$brand->slug])) {
            $domain = $domainMap[$brand->slug];
            $clearbitUrl = "https://logo.clearbit.com/{$domain}";

            try {
                $testResponse = Http::timeout(5)->get($clearbitUrl);
                if ($testResponse->successful() && str_contains($testResponse->header('Content-Type') ?? '', 'image')) {
                    $imgUrl = $clearbitUrl;
                }
            } catch (\Exception $e) {
                // Try brand website directly for favicon/logo
                try {
                    $siteResponse = Http::withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    ])->timeout(10)->get("https://{$domain}");

                    if ($siteResponse->successful()) {
                        $siteHtml = $siteResponse->body();
                        // Look for logo
                        if (preg_match('/<img[^>]+(?:class|id)="[^"]*logo[^"]*"[^>]+src="([^"]+)"/i', $siteHtml, $m)) {
                            $imgUrl = $m[1];
                            if (strpos($imgUrl, '//') === 0) $imgUrl = 'https:' . $imgUrl;
                            elseif (strpos($imgUrl, '/') === 0) $imgUrl = "https://{$domain}" . $imgUrl;
                        }
                        // Also try og:image
                        if (!$imgUrl && preg_match('/<meta\s+(?:property="og:image"\s+content|content)="([^"]+)"(?:\s+property="og:image")?/i', $siteHtml, $m)) {
                            $imgUrl = $m[1];
                            if (strpos($imgUrl, '//') === 0) $imgUrl = 'https:' . $imgUrl;
                            elseif (strpos($imgUrl, '/') === 0) $imgUrl = "https://{$domain}" . $imgUrl;
                        }
                    }
                } catch (\Exception $e2) {
                    // Skip
                }
            }
        }
    }

    if (!$imgUrl) {
        echo "NOT FOUND\n";
        $failed++;
        continue;
    }

    // Ensure absolute URL
    if (strpos($imgUrl, '//') === 0) $imgUrl = 'https:' . $imgUrl;

    // Download
    try {
        $imgResponse = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        ])->timeout(15)->get($imgUrl);

        if (!$imgResponse->successful()) {
            echo "DOWNLOAD FAILED (HTTP " . $imgResponse->status() . ")\n";
            $failed++;
            continue;
        }

        $contentType = $imgResponse->header('Content-Type') ?? '';
        if (!str_contains($contentType, 'image')) {
            echo "NOT AN IMAGE ($contentType)\n";
            $failed++;
            continue;
        }

        $extMap = [
            'image/jpeg' => 'jpg', 'image/jpg' => 'jpg',
            'image/png' => 'png', 'image/webp' => 'webp',
            'image/gif' => 'gif', 'image/svg+xml' => 'svg',
        ];
        $ext = $extMap[strtolower(explode(';', $contentType)[0])] ?? 'png';

        $path = 'brands/' . $brand->slug . '.' . $ext;
        Storage::disk('public')->put($path, $imgResponse->body());
        $brand->update(['logo' => $path]);

        echo "✓ IMPORTED ($path)\n";
        $imported++;

    } catch (\Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
        $failed++;
    }

    usleep(300000); // 300ms delay
}

echo "\n=== RESULTS ===\n";
echo "Imported: $imported\n";
echo "Failed: $failed\n";
echo "\nTotal brands: " . Brand::count() . "\n";
echo "With logos: " . Brand::whereNotNull('logo')->count() . "\n";
echo "Without logos: " . Brand::whereNull('logo')->count() . "\n";
