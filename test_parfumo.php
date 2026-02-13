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
    ])->timeout(15)->get($url);
}

// Test 1: Search functionality
echo "=== PARFUMO SEARCH TEST ===\n\n";

$searches = [
    'Tom Ford Oud Wood',
    'Initio Side Effect',
    'Lattafa Ameer Al Oudh',
];

foreach ($searches as $query) {
    echo "Search: '{$query}'\n";
    $searchUrl = 'https://www.parfumo.com/s_perfumes.php?query=' . urlencode($query);
    $response = fetchParfumo($searchUrl);
    echo "  Status: {$response->status()}\n";

    if ($response->successful()) {
        $html = $response->body();
        // Find perfume links
        if (preg_match_all('/href="(\/Perfumes\/[^"]+)"/', $html, $m)) {
            $links = array_unique($m[1]);
            echo "  Found " . count($links) . " results\n";
            foreach (array_slice($links, 0, 3) as $link) {
                echo "    {$link}\n";
            }
        } else {
            echo "  No perfume links found\n";
            if (preg_match('/<title>([^<]+)<\/title>/i', $html, $t)) {
                echo "  Title: {$t[1]}\n";
            }
        }
    }
    echo "\n";
}

// Test 2: Product page parsing in detail
echo "=== PRODUCT PAGE PARSING ===\n\n";

$testUrl = 'https://www.parfumo.com/Perfumes/Dior/Sauvage-Elixir';
$response = fetchParfumo($testUrl);
$html = $response->body();

echo "URL: {$testUrl}\n";
echo "Status: {$response->status()}\n\n";

// Method 1: Look for note pyramid sections
echo "--- Method 1: Section-based ---\n";
$noteTypes = ['Top Notes', 'Heart Notes', 'Base Notes'];
foreach ($noteTypes as $type) {
    echo "{$type}: ";
    // Find the section header and following content
    if (preg_match('/' . preg_quote($type) . '\s*<\/\w+>(.*?)(?=(?:Top|Heart|Base|Main)\s*Notes|Accords|<\/section|<h[23])/is', $html, $m)) {
        if (preg_match_all('/<[^>]*>([A-Z][a-zà-ÿ][^<]{1,40})<\/[^>]*>/u', $m[1], $nm)) {
            echo implode(', ', array_map('trim', $nm[1]));
        }
    }
    echo "\n";
}

// Method 2: Look for specific CSS classes
echo "\n--- Method 2: CSS class based ---\n";
if (preg_match_all('/class="[^"]*notes?_name[^"]*"[^>]*>([^<]+)/i', $html, $m)) {
    echo "Notes found: " . implode(', ', array_map('trim', $m[1])) . "\n";
}

// Method 3: Look for spans with note data
echo "\n--- Method 3: Structured data ---\n";
// Check for JSON-LD
if (preg_match('/<script[^>]*type="application\/ld\+json"[^>]*>(.*?)<\/script>/is', $html, $jsonLd)) {
    $data = json_decode($jsonLd[1], true);
    if ($data) {
        echo "JSON-LD type: " . ($data['@type'] ?? 'unknown') . "\n";
        if (isset($data['name'])) echo "Name: {$data['name']}\n";
    }
}

// Method 4: Extract from specific patterns common to Parfumo
echo "\n--- Method 4: Parfumo-specific patterns ---\n";
// Look for note divs/spans
if (preg_match_all('/<span[^>]*class="[^"]*note[^"]*"[^>]*>(.*?)<\/span>/is', $html, $m)) {
    echo "Span notes: \n";
    foreach ($m[1] as $note) {
        echo "  " . trim(strip_tags($note)) . "\n";
    }
}

// Method 5: Just find all occurrences of note-related patterns
echo "\n--- Method 5: Raw text extraction around 'Notes' ---\n";
// Get 500 chars around each "Notes" mention
$offset = 0;
while (($pos = strpos($html, 'Notes', $offset)) !== false) {
    $start = max(0, $pos - 50);
    $snippet = substr($html, $start, 300);
    $snippet = strip_tags($snippet);
    $snippet = preg_replace('/\s+/', ' ', trim($snippet));
    if (strlen($snippet) > 10 && stripos($snippet, 'top') !== false || stripos($snippet, 'heart') !== false || stripos($snippet, 'base') !== false) {
        echo "  ...{$snippet}...\n";
    }
    $offset = $pos + 5;
    if ($offset > strlen($html) - 5) break;
}

// Method 6: Look for the notes pyramid/grid structure
echo "\n--- Method 6: Pyramid/Grid structure ---\n";
// Parfumo uses a notes pyramid with images and text
if (preg_match_all('/<div[^>]+class="[^"]*pyramid[^"]*"[^>]*>(.*?)<\/div>/is', $html, $pyramids)) {
    echo "Found " . count($pyramids[0]) . " pyramid divs\n";
}

// Look for note images with alt text
if (preg_match_all('/<img[^>]+alt="([^"]+)"[^>]+class="[^"]*note[^"]*"/i', $html, $noteImgs)) {
    echo "Note images: " . implode(', ', $noteImgs[1]) . "\n";
}

// Dump a section of the HTML around "Top Notes"
echo "\n--- Raw HTML around 'Top Notes' ---\n";
$pos = strpos($html, 'Top Notes');
if ($pos !== false) {
    echo substr($html, $pos - 100, 800) . "\n";
}
