<?php

namespace App\Console\Commands;

use App\Models\Brand;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ScrapeBrandLogos extends Command
{
    protected $signature = 'brands:scrape-logos';
    protected $description = 'Scrape brand logos from live Shopify site';

    private $imported = 0;
    private $skipped = 0;
    private $failed = 0;

    public function handle()
    {
        $this->info('Fetching brands page from live site...');

        // Fetch the brands page
        $response = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        ])->timeout(30)->get('https://adperfumes.ae/pages/brands');

        if (!$response->successful()) {
            $this->error('Failed to fetch brands page: HTTP ' . $response->status());
            return 1;
        }

        $html = $response->body();

        // Extract brand data from the page
        // The page likely has a structure like: <a href="/collections/brand-slug"><img src="..."><span>Brand Name</span></a>
        $brandData = $this->extractBrandData($html);

        $this->info('Found ' . count($brandData) . ' brands on the page');
        $this->newLine();

        $bar = $this->output->createProgressBar(count($brandData));
        $bar->start();

        foreach ($brandData as $data) {
            $this->processBrand($data);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("✅ Scraping completed!");
        $this->info("   Imported: {$this->imported}");
        $this->info("   Skipped: {$this->skipped}");
        $this->info("   Failed: {$this->failed}");

        return 0;
    }

    private function extractBrandData(string $html): array
    {
        $brands = [];

        // Parse HTML using DOMDocument
        $dom = new \DOMDocument();
        @$dom->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new \DOMXPath($dom);

        // Find all links that point to /collections/*
        $links = $xpath->query('//a[contains(@href, "/collections/")]');

        foreach ($links as $link) {
            $href = $link->getAttribute('href');

            // Extract collection slug from URL
            if (preg_match('/\/collections\/([^\/\?]+)/', $href, $matches)) {
                $slug = $matches[1];

                // Find image within this link
                $images = $xpath->query('.//img', $link);
                if ($images->length > 0) {
                    $img = $images->item(0);
                    $imgSrc = $img->getAttribute('src');

                    // Convert protocol-relative URLs to https
                    if (str_starts_with($imgSrc, '//')) {
                        $imgSrc = 'https:' . $imgSrc;
                    }

                    // Extract brand name from the link text or image alt
                    $brandName = trim($link->textContent);
                    if (empty($brandName)) {
                        $brandName = $img->getAttribute('alt');
                    }

                    // Skip if it's the logo or navigation items
                    if (stripos($imgSrc, 'LOGO-AD-Perfumens') !== false) {
                        continue;
                    }

                    // Clean up brand name (remove "Buy", "Perfume", etc.)
                    $brandName = $this->cleanBrandName($brandName);

                    // If we still don't have a brand name, try to extract from filename
                    if (empty($brandName)) {
                        $brandName = $this->extractBrandNameFromFilename($imgSrc);
                    }

                    if (!empty($brandName) && !empty($imgSrc)) {
                        $brands[] = [
                            'name' => $brandName,
                            'img' => $imgSrc,
                            'slug' => $slug,
                        ];
                    }
                }
            }
        }

        // Remove duplicates based on slug
        $unique = [];
        foreach ($brands as $brand) {
            $unique[$brand['slug']] = $brand;
        }

        return array_values($unique);
    }

    private function cleanBrandName(string $name): string
    {
        // Remove common extra words
        $name = preg_replace('/\b(buy|shop|perfume|fragrance|for|women|men|niche|gallery|nichegallerie\.com)\b/i', '', $name);

        // Remove extra whitespace
        $name = preg_replace('/\s+/', ' ', $name);

        return trim($name);
    }

    private function extractBrandNameFromFilename(string $url): string
    {
        // Extract filename from URL
        $path = parse_url($url, PHP_URL_PATH);
        $filename = basename($path);

        // Remove size suffixes and extension
        $filename = preg_replace('/_\d+x\.(jpg|jpeg|png|webp|gif)$/i', '', $filename);

        // Remove "buy-" prefix and convert hyphens to spaces
        $name = str_replace(['buy-', '-'], ['', ' '], $filename);

        // Capitalize words
        $name = ucwords($name);

        return $this->cleanBrandName($name);
    }

    private function processBrand(array $data): void
    {
        $brandName = $data['name'];
        $imageUrl = $data['img'];
        $slug = $data['slug'];

        // Try to find brand in database
        // First try exact match
        $brand = Brand::whereRaw('LOWER(name) = ?', [strtolower($brandName)])->first();

        // Try slug match
        if (!$brand) {
            $brand = Brand::whereRaw('LOWER(slug) = ?', [strtolower($slug)])->first();
        }

        // Try partial match
        if (!$brand) {
            $brand = Brand::where(function($query) use ($brandName) {
                $query->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($brandName) . '%'])
                      ->orWhereRaw('LOWER(?) LIKE CONCAT("%", LOWER(name), "%")', [$brandName]);
            })->first();
        }

        // Special mappings
        if (!$brand) {
            $nameMap = [
                'Christian Dior' => 'Dior',
                'Roja Parfums' => 'Roja',
                'Hermès' => 'Hermes',
                'Lancôme' => 'Lancome',
                'Penhaligon\'s' => 'Penhaligon',
                'Tom Ford' => 'Tom Ford',
                'Maison Margiela' => 'Maison Margiela',
                'By Kilian' => 'By Kilian',
                'Dolce Gabbana' => 'Dolce & Gabbana',
                'D G' => 'Dolce & Gabbana',
                'Yves Saint Laurent' => 'Yves Saint Laurent',
            ];

            foreach ($nameMap as $search => $replace) {
                if (stripos($brandName, $search) !== false) {
                    $brand = Brand::whereRaw('LOWER(name) = ?', [strtolower($replace)])->first();
                    if ($brand) break;
                }
            }
        }

        if (!$brand) {
            $this->skipped++;
            return;
        }

        // Skip if already has logo
        if ($brand->logo) {
            $this->skipped++;
            return;
        }

        try {
            // Download image
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            ])->timeout(30)->get($imageUrl);

            if (!$response->successful()) {
                \Log::warning("Failed to download logo for {$brand->name} from {$imageUrl}: HTTP {$response->status()}");
                $this->failed++;
                return;
            }

            // Get extension from URL
            $extension = pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION);

            // Remove size suffix if present (e.g., _300x)
            $extension = preg_replace('/^_\d+x\./', '', $extension);

            if (empty($extension) || !in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                $extension = 'jpg';
            }

            // Generate filename
            $filename = $brand->slug . '.' . $extension;
            $path = 'brands/' . $filename;

            // Save to storage
            Storage::disk('public')->put($path, $response->body());

            // Update brand
            $brand->update(['logo' => $path]);

            \Log::info("Successfully imported logo for {$brand->name} from live site");
            $this->imported++;

        } catch (\Exception $e) {
            \Log::error("Exception importing logo for {$brand->name}: " . $e->getMessage());
            $this->failed++;
        }
    }
}
