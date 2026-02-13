<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportFromCSV extends Command
{
    protected $signature = 'products:import-csv {file?} {--all} {--skip-images : Skip image downloads for faster import}';
    protected $description = 'Import products from Shopify CSV export';

    protected $importedCount = 0;
    protected $skippedCount = 0;
    protected $errorCount = 0;

    public function handle()
    {
        ini_set('memory_limit', '512M');

        $this->info('Starting CSV product import...');
        $this->newLine();

        $files = [];

        if ($this->option('all')) {
            $files = [
                base_path('products_export_1.csv'),
                base_path('products_export_2.csv'),
            ];
        } elseif ($this->argument('file')) {
            $files = [base_path($this->argument('file'))];
        } else {
            $this->error('Please specify a file or use --all flag');
            return 1;
        }

        foreach ($files as $file) {
            if (!file_exists($file)) {
                $this->error("File not found: {$file}");
                continue;
            }

            $this->info("Processing: " . basename($file));
            $this->processCSV($file);
            $this->newLine();
        }

        $this->info('Import completed!');
        $this->newLine();
        $this->table(
            ['Status', 'Count'],
            [
                ['Imported', $this->importedCount],
                ['Skipped (existing)', $this->skippedCount],
                ['Errors', $this->errorCount],
            ]
        );

        $this->info('Total products in database: ' . Product::count());

        return 0;
    }

    protected function processCSV($file)
    {
        // Phase 1: Stream through CSV and collect unique handles (just handles + row data)
        $handle = fopen($file, 'r');
        $headers = fgetcsv($handle);
        $headerCount = count($headers);

        // First pass: count unique handles without storing all data
        $this->info('  Scanning CSV for unique products...');
        $uniqueHandles = [];
        $rowCount = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $rowCount++;
            if (count($row) !== $headerCount) continue;

            $handleIdx = array_search('Handle', $headers);
            $productHandle = $row[$handleIdx] ?? '';

            if (!empty($productHandle) && !isset($uniqueHandles[$productHandle])) {
                $uniqueHandles[$productHandle] = true;
            }
        }

        fclose($handle);

        $totalProducts = count($uniqueHandles);
        $this->info("  Found {$totalProducts} unique products in {$rowCount} rows");

        // Phase 2: Stream again and process each product as we encounter it
        $handle = fopen($file, 'r');
        fgetcsv($handle); // Skip header

        $currentHandle = null;
        $currentData = null;
        $processed = 0;

        $bar = $this->output->createProgressBar($totalProducts);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% | Imported: %message%');
        $bar->setMessage((string)$this->importedCount);

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) !== $headerCount) continue;

            $data = array_combine($headers, $row);
            $productHandle = $data['Handle'] ?? '';

            if (empty($productHandle)) continue;

            // If this is a new handle, process the previous product
            if ($productHandle !== $currentHandle) {
                // Process previous product if exists
                if ($currentHandle !== null && $currentData !== null) {
                    try {
                        $this->importProduct($currentData);
                    } catch (\Exception $e) {
                        $this->errorCount++;
                    }

                    $processed++;
                    $bar->setMessage((string)$this->importedCount);
                    $bar->advance();

                    // Free memory periodically
                    if ($processed % 100 === 0) {
                        gc_collect_cycles();
                    }
                }

                $currentHandle = $productHandle;
                $currentData = $data;
            }
            // Variant rows for same handle are skipped (we use first row only)
        }

        // Process the last product
        if ($currentHandle !== null && $currentData !== null) {
            try {
                $this->importProduct($currentData);
            } catch (\Exception $e) {
                $this->errorCount++;
            }
            $bar->advance();
        }

        fclose($handle);
        $bar->finish();
        $this->newLine();
    }

    protected function importProduct($data)
    {
        $productTitle = trim($data['Title'] ?? '', " \t\n\r\0\x0B\"'");

        if (empty($productTitle)) {
            $this->skippedCount++;
            return;
        }

        $slug = Str::slug($data['Handle'] ?? $productTitle);

        // Check if product already exists
        if (Product::where('slug', $slug)->exists()) {
            $this->skippedCount++;
            return;
        }

        // Extract brand
        $brandName = $this->extractBrandFromTitle($productTitle, $data['Vendor'] ?? 'Unknown');
        $brand = $this->getOrCreateBrand($brandName);

        // Get price
        $price = floatval($data['Variant Price'] ?? 0);
        $compareAtPrice = floatval($data['Variant Compare At Price'] ?? 0);

        // Download image (unless --skip-images)
        $imagePath = null;
        if (!$this->option('skip-images') && !empty($data['Image Src'])) {
            $imagePath = $this->downloadImage($data['Image Src'], $data['Handle']);
        }

        // Create product
        Product::create([
            'name' => $productTitle,
            'slug' => $slug,
            'description' => strip_tags($data['Body (HTML)'] ?? ''),
            'price' => $price,
            'original_price' => $compareAtPrice > 0 ? $compareAtPrice : null,
            'on_sale' => $compareAtPrice > $price,
            'stock' => intval($data['Variant Inventory Qty'] ?? 0),
            'brand_id' => $brand->id,
            'image' => $imagePath,
            'status' => strtolower($data['Status'] ?? 'active') === 'active',
            'is_new' => false,
        ]);

        $this->importedCount++;
    }

    protected function extractBrandFromTitle($productTitle, $vendor)
    {
        $knownBrands = [
            'Maison Francis Kurkdjian', 'Jean Paul Gaultier', 'Carolina Herrera',
            'Dolce & Gabbana', 'Viktor & Rolf', 'Narciso Rodriguez', 'Thierry Mugler',
            'Parfums de Marly', 'Acqua di Parma', 'Tiziana Terenzi', 'Attar Collection',
            'Frederic Malle', 'Serge Lutens', 'Swiss Arabian', 'Thomas Kosmala',
            'Angela Ciampagna', 'Vilhelm Parfumerie', 'Ormonde Jayne', 'BDK Parfums',
            'A Lab On Fire', 'J.F. Schwarzlose Berlin', 'Santa Eulalia', 'De Gabor',
            'Tom Ford', 'Al Haramain', 'Loui Martin', 'Memo Paris', 'Room 1015',
            'Six Scents', 'Initio', 'Dior', 'Chanel', 'Creed', 'Armani', 'Gucci',
            'Versace', 'Prada', 'Burberry', 'YSL', 'Givenchy', 'Hermes',
            'Lancome', 'Kilian', 'Byredo', 'Amouage', 'Roja', 'Xerjoff',
            'Montale', 'Mancera', 'Lattafa', 'Ajmal', 'Rasasi',
            'Afnan', 'Armaf', 'Nishane', 'Bvlgari', 'Cartier',
            'Atkinsons', 'Penhaligon', 'Amouroud', 'Caron', 'Thameen',
            'Wesker', 'Agatho', 'Argos', 'Basma', 'Trussardi',
            'Montblanc', 'Calvin Klein', 'Jimmy Choo', 'Hugo Boss',
            'Guess', 'Lacoste', 'Giorgio Armani', 'Yves Saint Laurent',
            'Maison Margiela', 'Emanuel Ungaro',
        ];

        // Sort by length descending so longer names match first
        usort($knownBrands, fn($a, $b) => strlen($b) - strlen($a));

        foreach ($knownBrands as $brand) {
            if (stripos($productTitle, $brand) === 0) {
                return $brand;
            }
        }

        if (stripos($vendor, 'abudhabi store') !== false ||
            stripos($vendor, 'ads') !== false ||
            stripos($vendor, 'ad perfumes') !== false) {
            return 'AD Perfumes';
        }

        return $vendor;
    }

    protected function getOrCreateBrand($brandName)
    {
        return Brand::firstOrCreate(
            ['name' => $brandName],
            ['slug' => Str::slug($brandName), 'status' => true]
        );
    }

    protected function downloadImage($url, $handle)
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0'])
                ->get($url);

            if (!$response->successful()) {
                return null;
            }

            $extension = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);
            if (empty($extension) || !in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                $extension = 'jpg';
            }

            $filename = 'product_' . Str::slug($handle) . '_' . uniqid() . '.' . $extension;
            $path = 'products/' . $filename;

            Storage::disk('public')->put($path, $response->body());

            return $path;
        } catch (\Exception $e) {
            return null;
        }
    }
}
