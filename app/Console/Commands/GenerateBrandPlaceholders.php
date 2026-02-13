<?php

namespace App\Console\Commands;

use App\Models\Brand;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class GenerateBrandPlaceholders extends Command
{
    protected $signature = 'brands:generate-placeholders {--overwrite : Overwrite existing logos}';
    protected $description = 'Generate placeholder logo images for brands without logos';

    public function handle()
    {
        $query = Brand::query();

        if (!$this->option('overwrite')) {
            $query->whereNull('logo');
        }

        $brands = $query->get();
        $count = $brands->count();

        if ($count === 0) {
            $this->info('All brands already have logos!');
            return 0;
        }

        $this->info("Generating placeholder logos for {$count} brands...");

        $bar = $this->output->createProgressBar($count);
        $generated = 0;

        foreach ($brands as $brand) {
            $this->generatePlaceholder($brand);
            $generated++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Generated {$generated} placeholder logos.");
        $this->info('Brands with logo: ' . Brand::whereNotNull('logo')->count() . '/' . Brand::count());

        return 0;
    }

    private function generatePlaceholder(Brand $brand): void
    {
        $width = 400;
        $height = 400;

        $img = imagecreatetruecolor($width, $height);

        // Background: dark charcoal (#1a1a2e)
        $bg = imagecolorallocate($img, 26, 26, 46);
        imagefill($img, 0, 0, $bg);

        // Gold color for text (#C9A96E)
        $gold = imagecolorallocate($img, 201, 169, 110);

        // Subtle border
        $borderColor = imagecolorallocate($img, 201, 169, 110);
        imagerectangle($img, 5, 5, $width - 6, $height - 6, $borderColor);

        // Get initials (1-3 chars)
        $initials = $this->getInitials($brand->name);

        // Draw initials
        $fontSize = strlen($initials) <= 2 ? 80 : 55;

        // Use built-in font (font 5 is the largest built-in)
        // For better quality, use imagettftext if a TTF font is available
        $fontFile = $this->findFont();

        if ($fontFile) {
            // Calculate text bounding box for centering
            $bbox = imagettfbbox($fontSize, 0, $fontFile, $initials);
            $textWidth = $bbox[2] - $bbox[0];
            $textHeight = $bbox[1] - $bbox[7];
            $x = ($width - $textWidth) / 2 - $bbox[0];
            $y = ($height - $textHeight) / 2 - $bbox[7];

            imagettftext($img, $fontSize, 0, (int) $x, (int) $y, $gold, $fontFile, $initials);
        } else {
            // Fallback: use built-in fonts (less pretty but works)
            $font = 5; // largest built-in font
            $charWidth = imagefontwidth($font);
            $charHeight = imagefontheight($font);
            $textWidth = $charWidth * strlen($initials);
            $x = ($width - $textWidth) / 2;
            $y = ($height - $charHeight) / 2;

            // Scale up by drawing multiple times with slight offset for "bold" effect
            for ($dx = -1; $dx <= 1; $dx++) {
                for ($dy = -1; $dy <= 1; $dy++) {
                    imagestring($img, $font, (int) ($x + $dx), (int) ($y + $dy), $initials, $gold);
                }
            }
        }

        // Save as PNG
        $filename = $brand->slug . '.png';
        $path = 'brands/' . $filename;
        $fullPath = Storage::disk('public')->path($path);

        // Ensure directory exists
        $dir = dirname($fullPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        imagepng($img, $fullPath, 5);
        imagedestroy($img);

        $brand->update(['logo' => $path]);
    }

    private function getInitials(string $name): string
    {
        // Clean the name
        $name = preg_replace('/[&.]/', '', $name);
        $name = trim($name);

        $words = preg_split('/[\s\-]+/', $name);
        $words = array_filter($words, fn($w) => strlen($w) > 0);

        if (count($words) === 1) {
            // Single word: use first 2-3 chars
            return strtoupper(substr($words[0], 0, min(3, strlen($words[0]))));
        }

        // Multiple words: use first letter of each (up to 3)
        $initials = '';
        foreach (array_slice($words, 0, 3) as $word) {
            $initials .= strtoupper(mb_substr($word, 0, 1));
        }

        return $initials;
    }

    private function findFont(): ?string
    {
        // Try common system fonts
        $fonts = [
            'C:/Windows/Fonts/arial.ttf',
            'C:/Windows/Fonts/calibri.ttf',
            'C:/Windows/Fonts/segoeui.ttf',
            'C:/Windows/Fonts/times.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
            '/usr/share/fonts/TTF/DejaVuSans.ttf',
        ];

        foreach ($fonts as $font) {
            if (file_exists($font)) {
                return $font;
            }
        }

        return null;
    }
}
