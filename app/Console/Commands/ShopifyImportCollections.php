<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use App\Services\ShopifyService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ShopifyImportCollections extends Command
{
    protected $signature = 'shopify:import-collections';
    protected $description = 'Import collections from Shopify as categories';

    protected $shopify;
    protected $importedCount = 0;
    protected $skippedCount = 0;

    public function handle()
    {
        $this->shopify = new ShopifyService();

        $this->info('🚀 Starting Shopify collections import...');
        $this->newLine();

        // Fetch collections from Shopify
        $this->info('📦 Fetching collections from Shopify...');
        $collections = $this->shopify->getAllCollections();

        $totalCollections = count($collections);
        $this->info("✓ Found {$totalCollections} collections");
        $this->newLine();

        $bar = $this->output->createProgressBar($totalCollections);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% - %message%');

        foreach ($collections as $collection) {
            $bar->setMessage("Importing: {$collection['title']}");

            $this->importCollection($collection);

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info('✅ Import completed!');
        $this->newLine();
        $this->table(
            ['Status', 'Count'],
            [
                ['✓ Imported', $this->importedCount],
                ['○ Skipped (existing)', $this->skippedCount],
                ['Total', $totalCollections],
            ]
        );

        return 0;
    }

    protected function importCollection($collection)
    {
        // Check if category already exists
        $slug = Str::slug($collection['title']);
        $existing = Category::where('slug', $slug)->first();

        if ($existing) {
            $this->skippedCount++;
            return;
        }

        // Create category
        Category::create([
            'name' => $collection['title'],
            'slug' => $slug,
            'description' => strip_tags($collection['body_html'] ?? ''),
            'status' => true,
        ]);

        $this->importedCount++;
    }
}
