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

        $converted = 0;
        $failed = 0;

        foreach ($products as $product) {
            $sourcePath = $product->image;

            if (!$disk->exists($sourcePath)) {
                $this->warn("  SKIP: {$sourcePath} (file not found)");
                $failed++;
                continue;
            }

            $ext = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));
            $newPath = preg_replace('/\.' . $ext . '$/', '.jpg', $sourcePath);

            // Skip if JPEG already exists
            if ($disk->exists($newPath)) {
                $product->update(['image' => $newPath]);
                $converted++;
                continue;
            }

            $fullPath = $disk->path($sourcePath);
            $image = match ($ext) {
                'webp' => @imagecreatefromwebp($fullPath),
                'avif' => @imagecreatefromavif($fullPath),
                default => null,
            };

            if (!$image) {
                $this->warn("  FAIL: {$sourcePath} (cannot read image)");
                $failed++;
                continue;
            }

            // Save as JPEG
            ob_start();
            imagejpeg($image, null, 85);
            $jpegData = ob_get_clean();
            imagedestroy($image);

            $disk->put($newPath, $jpegData);
            $product->update(['image' => $newPath]);
            $converted++;

            if ($converted % 100 === 0) {
                $this->info("  Converted {$converted} images...");
            }
        }

        $this->info("Done! Converted: {$converted}, Failed: {$failed}");

        return self::SUCCESS;
    }
}
