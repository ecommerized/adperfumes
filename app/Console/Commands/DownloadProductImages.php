<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DownloadProductImages extends Command
{
    protected $signature = 'products:download-images {--batch=50 : Number of products per batch} {--limit=0 : Max products to process (0 = all)}';
    protected $description = 'Download product images from CSV data for products missing images';

    protected $imported = 0;
    protected $failed = 0;
    protected $skipped = 0;

    public function handle()
    {
        ini_set('memory_limit', '512M');

        $this->info('Building image URL map from CSV files...');

        // Build handle => image URL map from both CSV files
        $imageMap = [];
        $csvFiles = [
            base_path('products_export_1.csv'),
            base_path('products_export_2.csv'),
        ];

        foreach ($csvFiles as $file) {
            if (!file_exists($file)) continue;

            $handle = fopen($file, 'r');
            $headers = fgetcsv($handle);
            $headerCount = count($headers);
            $handleIdx = array_search('Handle', $headers);
            $imgIdx = array_search('Image Src', $headers);

            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) !== $headerCount) continue;
                $productHandle = $row[$handleIdx] ?? '';
                $imgUrl = $row[$imgIdx] ?? '';

                if (!empty($productHandle) && !empty($imgUrl) && !isset($imageMap[$productHandle])) {
                    $imageMap[$productHandle] = $imgUrl;
                }
            }
            fclose($handle);
        }

        $this->info('Found ' . count($imageMap) . ' image URLs in CSV files');

        // Get products without images
        $query = Product::whereNull('image')->orderBy('id');
        $limit = (int) $this->option('limit');
        if ($limit > 0) {
            $query->limit($limit);
        }
        $total = $query->count();
        $this->info("Products needing images: {$total}");

        if ($total === 0) {
            $this->info('All products already have images!');
            return 0;
        }

        $batchSize = (int) $this->option('batch');
        $bar = $this->output->createProgressBar($total);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% | Downloaded: %message%');
        $bar->setMessage('0');

        // Process in batches
        $query->chunk($batchSize, function ($products) use ($imageMap, $bar) {
            // Collect URLs for this batch
            $downloads = [];
            foreach ($products as $product) {
                $slug = $product->slug;
                if (isset($imageMap[$slug])) {
                    $downloads[] = [
                        'product' => $product,
                        'url' => $imageMap[$slug],
                    ];
                } else {
                    $this->skipped++;
                    $bar->advance();
                }
            }

            // Download concurrently using Http pool
            if (!empty($downloads)) {
                $responses = Http::pool(function ($pool) use ($downloads) {
                    foreach ($downloads as $i => $item) {
                        $pool->as((string) $i)
                            ->timeout(15)
                            ->withHeaders(['User-Agent' => 'Mozilla/5.0'])
                            ->get($item['url']);
                    }
                });

                // Process responses
                foreach ($downloads as $i => $item) {
                    $response = $responses[(string) $i] ?? null;

                    if ($response && !($response instanceof \Exception) && $response->successful()) {
                        $this->saveImage($item['product'], $response, $item['url']);
                    } else {
                        $this->failed++;
                    }

                    $bar->setMessage((string) $this->imported);
                    $bar->advance();
                }
            }

            // Free memory
            gc_collect_cycles();
        });

        $bar->finish();
        $this->newLine(2);

        $this->table(
            ['Status', 'Count'],
            [
                ['Downloaded', $this->imported],
                ['Failed', $this->failed],
                ['No URL in CSV', $this->skipped],
            ]
        );

        $this->info('Products with images: ' . Product::whereNotNull('image')->count());
        $this->info('Products without images: ' . Product::whereNull('image')->count());

        return 0;
    }

    protected function saveImage(Product $product, $response, string $url): void
    {
        try {
            $extension = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);
            $extension = preg_replace('/\?.*/', '', $extension);

            if (empty($extension) || !in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif'])) {
                $contentType = $response->header('Content-Type') ?? '';
                $extMap = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/webp' => 'webp',
                    'image/gif' => 'gif',
                    'image/avif' => 'avif',
                ];
                $extension = $extMap[strtolower(explode(';', $contentType)[0])] ?? 'jpg';
            }

            $filename = 'product_' . Str::slug($product->slug) . '.' . $extension;
            $path = 'products/' . $filename;

            Storage::disk('public')->put($path, $response->body());
            $product->update(['image' => $path]);

            $this->imported++;
        } catch (\Exception $e) {
            $this->failed++;
        }
    }
}
