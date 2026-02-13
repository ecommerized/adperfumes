<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Http;

echo "=== Testing nichegallerie.com ===\n\n";

// Test 1: Search
$searchUrl = 'https://nichegallerie.com/?s=' . urlencode('Dior Sauvage');
echo "Search URL: {$searchUrl}\n";
$response = Http::withHeaders([
    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
])->timeout(15)->get($searchUrl);
echo "Status: {$response->status()}\n";
echo "Body length: " . strlen($response->body()) . "\n";

// Find product links
$html = $response->body();
if (preg_match_all('/href="(https:\/\/nichegallerie\.com\/perfume\/[^"]+)"/', $html, $matches)) {
    echo "Found " . count(array_unique($matches[1])) . " product links:\n";
    foreach (array_unique(array_slice($matches[1], 0, 5)) as $link) {
        echo "  {$link}\n";
    }

    // Try first product page
    echo "\n--- Fetching first product page ---\n";
    $productUrl = $matches[1][0];
    $pResponse = Http::withHeaders([
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
    ])->timeout(15)->get($productUrl);
    echo "Status: {$pResponse->status()}\n";

    $pHtml = $pResponse->body();

    // Find notes sections
    foreach (['Top Notes', 'Middle Notes', 'Base Notes', 'Main Accords'] as $section) {
        echo "\n{$section}: ";
        // Look for heading followed by listing grid items
        $pattern = '/' . preg_quote($section) . '.*?jet-listing-grid(.*?)(?:elementor-heading-title|<\/section>)/is';
        if (preg_match($pattern, $pHtml, $sm)) {
            // Extract note names from links or text
            if (preg_match_all('/<a[^>]+href="[^"]*\/(?:notes|accords)\/[^"]*"[^>]*>([^<]*)</i', $sm[1], $noteMatches)) {
                echo implode(', ', array_map('trim', $noteMatches[1]));
            } elseif (preg_match_all('/class="jet-listing-dynamic-field__content">([^<]+)</i', $sm[1], $noteMatches)) {
                echo implode(', ', array_map('trim', $noteMatches[1]));
            } else {
                // Try just finding text content
                $text = strip_tags($sm[1]);
                $text = preg_replace('/\s+/', ' ', trim($text));
                echo substr($text, 0, 200);
            }
        } else {
            echo "NOT FOUND";
        }
    }
} else {
    echo "No product links found.\n";
    // Check for redirect or different format
    if (preg_match('/<title>([^<]+)<\/title>/i', $html, $m)) {
        echo "Page title: {$m[1]}\n";
    }
}

echo "\n\n=== Testing Parfumo.com ===\n\n";

$parfumoUrl = 'https://www.parfumo.com/Perfumes/Dior/Sauvage-Elixir';
echo "URL: {$parfumoUrl}\n";
$response2 = Http::withHeaders([
    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    'Accept' => 'text/html,application/xhtml+xml',
    'Accept-Language' => 'en-US,en;q=0.5',
])->timeout(15)->get($parfumoUrl);
echo "Status: {$response2->status()}\n";
echo "Body length: " . strlen($response2->body()) . "\n";

$pHtml2 = $response2->body();

// Check for notes
foreach (['Top Notes', 'Heart Notes', 'Base Notes'] as $section) {
    echo "\n{$section}: ";
    $patterns = [
        '/' . preg_quote($section) . '.*?<div[^>]*>(.*?)<\/div>/is',
        '/' . preg_quote($section, '/') . '\s*:?\s*([^<]+)/i',
    ];
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $pHtml2, $m)) {
            // Extract from links or plain text
            if (preg_match_all('/>([^<]{2,30})<\/a>/i', $m[1], $nm)) {
                echo implode(', ', array_map('trim', $nm[1]));
            } else {
                echo trim(strip_tags($m[1]));
            }
            break;
        }
    }
}

// Check for accords
echo "\n\nAccords: ";
if (preg_match_all('/class="[^"]*accord[^"]*"[^>]*>([^<]+)/i', $pHtml2, $accords)) {
    echo implode(', ', array_map('trim', $accords[1]));
}

echo "\n";
