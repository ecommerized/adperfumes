<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

use App\Models\Product;

echo "\n╔════════════════════════════════════════════════════════════╗\n";
echo "║               IMPORT STATUS SUMMARY                        ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

$total = Product::count();
$withShopifyId = Product::whereNotNull('shopify_id')->count();
$shopifyIds = Product::whereNotNull('shopify_id')->pluck('shopify_id');
$uniqueShopifyIds = $shopifyIds->unique()->count();

echo "📦 Total Products in Database:    {$total}\n";
echo "📦 Products with Shopify ID:      {$withShopifyId}\n";
echo "📦 Unique Shopify IDs:            {$uniqueShopifyIds}\n";
echo "📦 Duplicate Shopify IDs:         " . ($withShopifyId - $uniqueShopifyIds) . "\n\n";

if ($uniqueShopifyIds < $withShopifyId) {
    echo "⚠️  WARNING: There are duplicate Shopify IDs in the database!\n\n";
}

$latest = Product::whereNotNull('shopify_id')->orderBy('created_at', 'desc')->first();
echo "🕐 Latest import: " . ($latest ? $latest->created_at->diffForHumans() : 'Never') . "\n\n";

echo "🎯 Target: 12,931 products\n";
echo "📊 Progress: " . round(($total / 12931) * 100, 2) . "%\n\n";
