<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Http;

function fetchParfumo($url) {
    return Http::withHeaders([
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Accept-Language' => 'en-US,en;q=0.5',
    ])->timeout(10)->get($url);
}

// Parfumo URL pattern: /Perfumes/{Brand}/{Perfume-Name}
// Test different URL constructions
$tests = [
    ['Tom Ford', 'Oud Wood Eau de Parfum', 'Tom-Ford', 'Oud-Wood'],
    ['Initio', 'Side Effect', 'Initio-Parfums-Prives', 'Side-Effect'],
    ['Creed', 'Aventus', 'Creed', 'Aventus'],
    ['Amouage', 'Interlude Man', 'Amouage', 'Interlude-Man'],
    ['Lattafa', 'Raghba', 'Lattafa', 'Raghba'],
    ['Dior', 'Sauvage', 'Dior', 'Sauvage'],
    ['Montale', 'Intense Cafe', 'Montale', 'Intense-Cafe'],
];

echo "=== Testing Parfumo URL patterns ===\n\n";

foreach ($tests as [$brand, $name, $urlBrand, $urlName]) {
    $url = "https://www.parfumo.com/Perfumes/{$urlBrand}/{$urlName}";
    $response = fetchParfumo($url);
    $status = $response->status();

    $notes = '';
    if ($response->successful()) {
        $html = $response->body();
        // Quick note extraction
        if (preg_match_all('/<span[^>]*class="[^"]*note[^"]*"[^>]*>(.*?)<\/span>/is', $html, $m)) {
            $noteNames = array_map(fn($s) => trim(strip_tags($s)), $m[1]);
            $notes = implode(', ', array_slice($noteNames, 0, 5)) . '...';
        }
    }

    echo "{$brand} - {$name}: HTTP {$status}";
    if ($notes) echo " => {$notes}";
    echo "\n";
}

// Test Parfumo search API - they might have an AJAX endpoint
echo "\n=== Testing Parfumo AJAX Search ===\n\n";
$ajaxUrl = 'https://www.parfumo.com/ajax_search.php?q=' . urlencode('Sauvage Dior');
$response = fetchParfumo($ajaxUrl);
echo "AJAX search: HTTP {$response->status()}\n";
if ($response->successful()) {
    $body = $response->body();
    echo "Response length: " . strlen($body) . "\n";
    echo "First 500 chars: " . substr($body, 0, 500) . "\n";
}

// Try another AJAX pattern
$ajaxUrl2 = 'https://www.parfumo.com/ajax_search_2.php?q=' . urlencode('Sauvage');
$response2 = fetchParfumo($ajaxUrl2);
echo "\nAJAX search 2: HTTP {$response2->status()}\n";

// Try autocomplete endpoint
$autoUrl = 'https://www.parfumo.com/autocomplete.php?q=' . urlencode('Sauvage');
$response3 = fetchParfumo($autoUrl);
echo "Autocomplete: HTTP {$response3->status()}\n";
if ($response3->successful()) {
    echo "Body: " . substr($response3->body(), 0, 500) . "\n";
}
