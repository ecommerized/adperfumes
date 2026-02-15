<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ConvertProductImages extends Command
{
    protected $signature = 'products:convert-images {--format=avif : Format to convert (avif, webp, or all)}';

    protected $description = 'Convert product images from AVIF/WebP to JPEG for Google Merchant compatibility';

    public function handle(): int
    {
        $format = $this->option('format');
        $disk = Storage::disk('public');

        $query = Product::where('status', true)->whereNotNull('image');

        if ($format === 'avif') {
            $query->where('image', 'like', '%.avif');
        } elseif ($format === 'webp') {
            $query->where('image', 'like', '%.webp');
        } else {
            $query->where(function ($q) {
                $q->where('image', 'like', '%.avif')
                  ->orWhere('image', 'like', '%.webp');
            });
        }

        $products = $query->get();
        $this->info("Found {$products->count()} products with {$format} images to convert.");

        if ($products->isEmpty()) {
            return self::SUCCESS;
        }

        // Debug: show disk path
        $this->info("Storage path: " . $disk->path(''));

        $converted = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($products as $product) {
            $sourcePath = $product->image;
            $ext = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));
            $newPath = preg_replace('/\.' . preg_quote($ext, '/') . '$/', '.jpg', $sourcePath);

            // If JPEG version already exists, just update the DB
            if ($disk->exists($newPath)) {
                $product->update(['image' => $newPath]);
                $converted++;
                continue;
            }

            if (!$disk->exists($sourcePath)) {
                $this->warn("  SKIP: {$sourcePath} (file not found)");
                $skipped++;
                continue;
            }

            // Read file content and use imagecreatefromstring (auto-detects format)
            $fileContent = $disk->get($sourcePath);
            if (!$fileContent) {
                $this->warn("  FAIL: {$sourcePath} (cannot read file)");
                $failed++;
                continue;
            }

            $image = @imagecreatefromstring($fileContent);

            if (!$image) {
                // Fallback: try format-specific function with full path
                $fullPath = $disk->path($sourcePath);
                $image = match ($ext) {
                    'webp' => @imagecreatefromwebp($fullPath),
                    'avif' => @imagecreatefromavif($fullPath),
                    default => null,
                };
            }

            if (!$image) {
                $this->warn("  FAIL: {$sourcePath} (cannot decode image, size: " . strlen($fileContent) . " bytes)");
                $failed++;
                continue;
            }

            // Save as JPEG
            ob_start();
            imagejpeg($image, null, 85);
            $jpegData = ob_get_clean();
            imagedestroy($image);

            if (empty($jpegData)) {
                $this->warn("  FAIL: {$sourcePath} (JPEG encoding failed)");
                $failed++;
                continue;
            }

            $disk->put($newPath, $jpegData);
            $product->update(['image' => $newPath]);
            $converted++;

            if ($converted % 50 === 0) {
                $this->info("  Converted {$converted} images...");
            }
        }

        $this->info("Done! Converted: {$converted}, Skipped: {$skipped}, Failed: {$failed}");

        return self::SUCCESS;
    }
}
