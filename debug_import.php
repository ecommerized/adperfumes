<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\\Contracts\\Console\\Kernel')->bootstrap();

use App\Models\Product;
use App\Services\ShopifyService;

echo "===== DEBUG IMPORT ISSUE =====\n\n";

// Check database
echo "Products in database: " . Product::count() . "\n";
echo "Products with Shopify ID: " . Product::whereNotNull('shopify_id')->count() . "\n\n";

// Get first few products from Shopify
$shopify = new ShopifyService();
$data = $shopify->getProducts(5, null); // Get first 5 products
$products = $data['products'];

echo "Testing first 5 products from Shopify:\n\n";

foreach ($products as $product) {
    $shopifyId = $product['id'];
    $title = $product['title'];

    // Check if exists
    $exists = Product::where('shopify_id', $shopifyId)->first();

    echo "Product: {$title}\n";
    echo "Shopify ID: {$shopifyId}\n";
    echo "Exists in DB: " . ($exists ? 'YES (ID: ' . $exists->id . ')' : 'NO') . "\n";
    echo "---\n";
}
