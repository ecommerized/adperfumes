<?php

namespace App\Console\Commands;

use App\Models\Brand;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FetchBrandLogos extends Command
{
    protected $signature = 'brands:fetch-logos {--force : Re-download existing logos}';
    protected $description = 'Fetch brand logos from various online sources';

    private $imported = 0;
    private $skipped = 0;
    private $failed = 0;

    // Common sources for perfume brand logos
    private $logoSources = [
        'fragrantica' => 'https://fimgs.net/mdimg/perfume/375x500.',
        'fragrantica_brands' => 'https://fimgs.net/mdimg/brandlogo/m.',
        'shopify_cdn' => 'https://cdn.shopify.com/s/files/1/0640/8826/7547/collections/',
    ];

    public function handle()
    {
        $this->info('Fetching brand logos from online sources...');
        $this->newLine();

        // Get brands that need logos
        $query = Brand::query();

        if (!$this->option('force')) {
            $query->whereNull('logo');
        }

        $brands = $query->orderBy('name')->get();

        if ($brands->isEmpty()) {
            $this->info('All brands already have logos!');
            return 0;
        }

        $this->info('Found ' . $brands->count() . ' brands needing logos');
        $this->newLine();

        $bar = $this->output->createProgressBar($brands->count());
        $bar->start();

        foreach ($brands as $brand) {
            $this->processBrand($brand);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("✅ Logo fetch completed!");
        $this->info("   Imported: {$this->imported}");
        $this->info("   Skipped: {$this->skipped}");
        $this->info("   Failed: {$this->failed}");

        return 0;
    }

    private function processBrand(Brand $brand): void
    {
        // Skip if already has logo and not forcing
        if ($brand->logo && !$this->option('force')) {
            $this->skipped++;
            return;
        }

        $logoUrl = null;

        // Try multiple sources in order of preference
        $sources = [
            fn() => $this->tryFragranticaBrandLogo($brand),
            fn() => $this->tryGoogleImages($brand),
            fn() => $this->tryBrandOfficialSite($brand),
            fn() => $this->tryLogoDevApi($brand),
            fn() => $this->tryClearbitLogo($brand),
        ];

        foreach ($sources as $source) {
            $logoUrl = $source();
            if ($logoUrl) {
                break;
            }
        }

        if (!$logoUrl) {
            $this->failed++;
            \Log::warning("Could not find logo for {$brand->name}");
            return;
        }

        // Download and save the logo
        if ($this->downloadAndSaveLogo($brand, $logoUrl)) {
            $this->imported++;
        } else {
            $this->failed++;
        }
    }

    private function tryFragranticaBrandLogo(Brand $brand): ?string
    {
        // Fragrantica uses brand ID in URLs, try common variations
        $slug = Str::slug($brand->name);
        $variations = [
            $slug,
            str_replace('-', '', $slug),
            strtolower(str_replace([' ', '&', '.'], '', $brand->name)),
        ];

        foreach ($variations as $variation) {
            $url = "https://fimgs.net/mdimg/brandlogo/m.{$variation}.jpg";

            if ($this->checkUrlExists($url)) {
                return $url;
            }

            // Also try PNG
            $pngUrl = str_replace('.jpg', '.png', $url);
            if ($this->checkUrlExists($pngUrl)) {
                return $pngUrl;
            }
        }

        return null;
    }

    private function tryGoogleImages(Brand $brand): ?string
    {
        // Search for brand logo using Google Custom Search (if available)
        // For now, we'll construct a likely logo URL pattern

        // Try Wikipedia/Wikimedia Commons pattern
        $brandForUrl = str_replace(' ', '_', $brand->name);
        $wikiUrl = "https://upload.wikimedia.org/wikipedia/commons/";

        // Common brand logo patterns on Wikimedia
        $patterns = [
            "{$brandForUrl}_logo.svg",
            "{$brandForUrl}_logo.png",
            "{$brandForUrl}.svg",
        ];

        // Note: This is a simplified approach. In production, you'd want to use
        // Google Custom Search API or another proper image search service.

        return null; // Placeholder - would need API key for real implementation
    }

    private function tryBrandOfficialSite(Brand $brand): ?string
    {
        // Try to fetch logo from brand's official website
        $brandSlug = Str::slug($brand->name);

        // Common brand website patterns
        $domains = [
            "{$brandSlug}.com",
            "{$brandSlug}parfums.com",
            "{$brandSlug}perfumes.com",
            "www.{$brandSlug}.com",
        ];

        foreach ($domains as $domain) {
            try {
                $response = Http::timeout(10)
                    ->withHeaders(['User-Agent' => 'Mozilla/5.0'])
                    ->get("https://{$domain}");

                if ($response->successful()) {
                    $html = $response->body();

                    // Look for logo in common places
                    if (preg_match('/<img[^>]+(?:class|id)="[^"]*logo[^"]*"[^>]+src="([^"]+)"/i', $html, $matches)) {
                        $logoUrl = $matches[1];

                        // Make URL absolute if relative
                        if (!str_starts_with($logoUrl, 'http')) {
                            $logoUrl = "https://{$domain}" . $logoUrl;
                        }

                        return $logoUrl;
                    }
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return null;
    }

    private function tryLogoDevApi(Brand $brand): ?string
    {
        // Try Logo.dev API (free tier available)
        // Format: https://img.logo.dev/{domain}?token=YOUR_TOKEN

        $brandSlug = Str::slug($brand->name);
        $domain = "{$brandSlug}.com";

        // Without API token, we can try the public endpoint
        $url = "https://img.logo.dev/{$domain}?format=png&size=200";

        if ($this->checkUrlExists($url, false)) {
            return $url;
        }

        return null;
    }

    private function tryClearbitLogo(Brand $brand): ?string
    {
        // Clearbit Logo API (free, no auth required)
        $brandSlug = Str::slug($brand->name);
        $domain = "{$brandSlug}.com";

        $url = "https://logo.clearbit.com/{$domain}";

        if ($this->checkUrlExists($url, false)) {
            return $url;
        }

        // Also try with .fr, .it for European brands
        foreach (['.fr', '.it', '.uk'] as $tld) {
            $euUrl = "https://logo.clearbit.com/{$brandSlug}{$tld}";
            if ($this->checkUrlExists($euUrl, false)) {
                return $euUrl;
            }
        }

        return null;
    }

    private function checkUrlExists(string $url, bool $strict = true): bool
    {
        try {
            $response = Http::timeout(5)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0'])
                ->head($url);

            if ($strict) {
                return $response->successful();
            } else {
                // For services that redirect or don't support HEAD
                $getResponse = Http::timeout(5)
                    ->withHeaders(['User-Agent' => 'Mozilla/5.0'])
                    ->get($url);

                return $getResponse->successful() &&
                       $getResponse->header('Content-Type') &&
                       str_contains($getResponse->header('Content-Type'), 'image');
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    private function downloadAndSaveLogo(Brand $brand, string $url): bool
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0'])
                ->get($url);

            if (!$response->successful()) {
                \Log::warning("Failed to download logo for {$brand->name} from {$url}: HTTP {$response->status()}");
                return false;
            }

            // Validate it's an image
            $contentType = $response->header('Content-Type');
            if (!$contentType || !str_contains($contentType, 'image')) {
                \Log::warning("URL for {$brand->name} is not an image: {$url}");
                return false;
            }

            // Determine extension from content type or URL
            $extension = $this->getExtensionFromContentType($contentType);
            if (!$extension) {
                $extension = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);
            }

            if (empty($extension) || !in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'svg', 'gif'])) {
                $extension = 'png';
            }

            // Generate filename
            $filename = $brand->slug . '.' . $extension;
            $path = 'brands/' . $filename;

            // Save to storage
            Storage::disk('public')->put($path, $response->body());

            // Update brand
            $brand->update(['logo' => $path]);

            \Log::info("Successfully imported logo for {$brand->name} from {$url}");

            return true;

        } catch (\Exception $e) {
            \Log::error("Exception downloading logo for {$brand->name}: " . $e->getMessage());
            return false;
        }
    }

    private function getExtensionFromContentType(string $contentType): ?string
    {
        $map = [
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg',
            'image/gif' => 'gif',
        ];

        $contentType = strtolower(explode(';', $contentType)[0]);

        return $map[$contentType] ?? null;
    }
}
