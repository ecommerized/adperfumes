<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;

// Monitor import progress
$shopifyProducts = Product::whereNotNull('shopify_id')->count();
$totalProducts = Product::count();

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║           SHOPIFY IMPORT PROGRESS MONITOR                 ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

echo "📦 Products Imported from Shopify: {$shopifyProducts}\n";
echo "📦 Total Products in Database:     {$totalProducts}\n";
echo "🎯 Target:                         12,931 products\n\n";

$percentage = $shopifyProducts > 0 ? round(($shopifyProducts / 12931) * 100, 2) : 0;
$remaining = 12931 - $shopifyProducts;

echo "Progress: {$percentage}%\n";
echo "Remaining: {$remaining} products\n\n";

if ($shopifyProducts < 12931) {
    $productsPerMinute = 50; // Approximate rate
    $estimatedMinutes = ceil($remaining / $productsPerMinute);
    $estimatedHours = floor($estimatedMinutes / 60);
    $estimatedMins = $estimatedMinutes % 60;

    echo "⏱️  Estimated time remaining: ";
    if ($estimatedHours > 0) {
        echo "{$estimatedHours}h {$estimatedMins}m\n";
    } else {
        echo "{$estimatedMins} minutes\n";
    }
} else {
    echo "✅ Import Complete!\n";
}

echo "\n💡 Check full log: storage/logs/full_import.log\n";
echo "💡 To monitor live: tail -f storage/logs/full_import.log\n";
