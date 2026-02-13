<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;
use Illuminate\Support\Facades\Http;

$product = Product::whereDoesntHave('notes')->whereNotNull('brand_id')->with('brand')->first();
echo "Product: {$product->name}\n";
echo "Brand: {$product->brand->name}\n";

// Clean name
$brandName = $product->brand->name;
$name = $product->name;
if (stripos($name, $brandName) === 0) {
    $name = trim(substr($name, strlen($brandName)));
}
$name = preg_replace('/\s*(EDP|EDT|Parfum|Cologne)\s*\d+ml.*/i', '', $name);
$name = ltrim($name, '-– ');
echo "Clean name: {$name}\n";

$searchQuery = $brandName . ' ' . $name;
echo "Search query: {$searchQuery}\n";

// Try Fragrantica search
$searchUrl = 'https://www.fragrantica.com/search/?query=' . urlencode($searchQuery);
echo "URL: {$searchUrl}\n\n";

$response = Http::withHeaders([
    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
    'Accept-Language' => 'en-US,en;q=0.5',
])->timeout(15)->get($searchUrl);

echo "Status: {$response->status()}\n";
echo "Body length: " . strlen($response->body()) . "\n";

$html = $response->body();

// Check for blocks
if (strpos($html, 'captcha') !== false || strpos($html, 'CAPTCHA') !== false) {
    echo "CAPTCHA detected!\n";
}
if (strpos($html, 'cloudflare') !== false || strpos($html, 'Cloudflare') !== false) {
    echo "Cloudflare protection detected!\n";
}
if (strpos($html, 'Access Denied') !== false) {
    echo "Access Denied!\n";
}

// Check for perfume links
if (preg_match_all('/href="(\/perfume\/[^"]+\.html)"/', $html, $matches)) {
    echo "\nFound perfume links:\n";
    foreach (array_unique($matches[1]) as $link) {
        echo "  https://www.fragrantica.com{$link}\n";
    }
} else {
    echo "\nNo perfume links found.\n";
    // Show title
    if (preg_match('/<title>([^<]+)<\/title>/i', $html, $m)) {
        echo "Page title: {$m[1]}\n";
    }
    echo "\nFirst 1000 chars:\n" . substr($html, 0, 1000) . "\n";
}
