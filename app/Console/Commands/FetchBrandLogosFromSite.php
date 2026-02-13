<?php

namespace App\Console\Commands;

use App\Models\Brand;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FetchBrandLogosFromSite extends Command
{
    protected $signature = 'brands:fetch-logos {--force : Re-fetch logos even if brand already has one}';
    protected $description = 'Fetch brand logos from adperfumes.ae collection pages and fallback sources';

    protected $imported = 0;
    protected $failed = 0;
    protected $skipped = 0;

    public function handle()
    {
        $query = Brand::query()->withCount('products')->having('products_count', '>', 0);
        if (!$this->option('force')) {
            $query->whereNull('logo');
        }
        $brands = $query->orderByDesc('products_count')->get();

        $this->info("Fetching logos for {$brands->count()} brands...");
        $bar = $this->output->createProgressBar($brands->count());
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% | %message%');
        $bar->setMessage('Starting...');

        foreach ($brands as $brand) {
            $bar->setMessage($brand->name);

            $imgUrl = $this->tryAdPerfumesSite($brand);

            if (!$imgUrl) {
                $imgUrl = $this->tryClearbit($brand);
            }

            if (!$imgUrl) {
                $imgUrl = $this->tryGoogleFavicon($brand);
            }

            if ($imgUrl) {
                $saved = $this->downloadAndSave($brand, $imgUrl);
                if (!$saved) {
                    $this->failed++;
                }
            } else {
                $this->failed++;
            }

            $bar->advance();
            usleep(200000); // 200ms delay
        }

        $bar->finish();
        $this->newLine(2);

        $this->table(['Status', 'Count'], [
            ['Imported', $this->imported],
            ['Skipped', $this->skipped],
            ['Failed', $this->failed],
        ]);

        $this->info('Brands with logos: ' . Brand::whereNotNull('logo')->count());
        $this->info('Brands without logos: ' . Brand::whereNull('logo')->count());

        return 0;
    }

    protected function tryAdPerfumesSite(Brand $brand): ?string
    {
        $slugsToTry = [$brand->slug];

        // Add alternate slug formats
        $altSlug = Str::slug($brand->name);
        if ($altSlug !== $brand->slug) {
            $slugsToTry[] = $altSlug;
        }

        // Common alternate patterns
        $altMappings = [
            'ysl' => ['yves-saint-laurent'],
            'giorgio-armani' => ['armani'],
            'dior' => ['christian-dior'],
            'roja' => ['roja-parfums'],
            'kilian' => ['by-kilian', 'kilian-paris'],
            'hermes' => ['hermès', 'hermes-paris'],
            'lancome' => ['lancôme'],
            'penhaligon' => ['penhaligons'],
            'maison-francis-kurkdjian' => ['mfk', 'francis-kurkdjian'],
            'memo-paris' => ['memo'],
        ];

        if (isset($altMappings[$brand->slug])) {
            $slugsToTry = array_merge($slugsToTry, $altMappings[$brand->slug]);
        }

        foreach ($slugsToTry as $slug) {
            $url = "https://adperfumes.ae/collections/" . $slug;

            try {
                $response = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                ])->timeout(10)->get($url);

                if (!$response->successful()) continue;

                $html = $response->body();
                $imgUrl = null;

                // Try og:image
                if (preg_match('/<meta\s+property="og:image"\s+content="([^"]+)"/i', $html, $m)) {
                    $imgUrl = $m[1];
                }
                if (!$imgUrl && preg_match('/<meta\s+content="([^"]+)"\s+property="og:image"/i', $html, $m)) {
                    $imgUrl = $m[1];
                }

                // Try collection image
                if (!$imgUrl && preg_match('/cdn\/shop\/collections\/([^"\s?]+)/i', $html, $m)) {
                    $imgUrl = "https://adperfumes.ae/cdn/shop/collections/" . $m[1];
                }

                if ($imgUrl) {
                    if (strpos($imgUrl, '//') === 0) {
                        $imgUrl = 'https:' . $imgUrl;
                    }
                    return $imgUrl;
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return null;
    }

    protected function tryClearbit(Brand $brand): ?string
    {
        $domainMap = [
            'hugo-boss' => 'hugoboss.com',
            'prada' => 'prada.com',
            'burberry' => 'burberry.com',
            'azzaro' => 'azzaro.com',
            'armani' => 'armani.com',
            'montale' => 'montaleparfums.com',
            'kilian' => 'bykilian.com',
            'narciso-rodriguez' => 'narcisorodriguez.com',
            'acqua-di-parma' => 'acquadiparma.com',
            'ysl' => 'ysl.com',
            'jimmy-choo' => 'jimmychoo.com',
            'calvin-klein' => 'calvinklein.com',
            'gucci' => 'gucci.com',
            'versace' => 'versace.com',
            'montblanc' => 'montblanc.com',
            'lacoste' => 'lacoste.com',
            'givenchy' => 'givenchy.com',
            'guess' => 'guess.com',
            'chanel' => 'chanel.com',
            'dior' => 'dior.com',
            'hermes' => 'hermes.com',
            'cartier' => 'cartier.com',
            'bvlgari' => 'bulgari.com',
            'lancome' => 'lancome.com',
            'tom-ford' => 'tomford.com',
            'dolce-gabbana' => 'dolcegabbana.com',
            'carolina-herrera' => 'carolinaherrera.com',
            'jean-paul-gaultier' => 'jeanpaulgaultier.com',
            'thierry-mugler' => 'mugler.com',
            'viktor-rolf' => 'viktor-rolf.com',
            'maison-margiela' => 'maisonmargiela.com',
            'rasasi' => 'rasasi.com',
            'lattafa' => 'lattafa.com',
            'swiss-arabian' => 'swissarabian.com',
            'ajmal' => 'ajmalperfume.com',
            'afnan' => 'afnanperfumes.com',
            'al-haramain' => 'alharamain.com',
            'attar-collection' => 'attarcollection.com',
            'nishane' => 'nishane.com',
            'xerjoff' => 'xerjoff.com',
            'amouage' => 'amouage.com',
            'creed' => 'creedboutique.com',
            'byredo' => 'byredo.com',
            'mancera' => 'manceraparfums.com',
            'initio' => 'initioparfums.com',
            'parfums-de-marly' => 'parfums-de-marly.com',
            'penhaligon' => 'penhaligons.com',
            'frederic-malle' => 'fredericmalle.com',
        ];

        $domain = $domainMap[$brand->slug] ?? null;
        if (!$domain) {
            // Try constructing domain from slug
            $cleanSlug = str_replace('-', '', $brand->slug);
            $domain = $cleanSlug . '.com';
        }

        $clearbitUrl = "https://logo.clearbit.com/{$domain}";

        try {
            $response = Http::timeout(5)->get($clearbitUrl);
            if ($response->successful() && str_contains($response->header('Content-Type') ?? '', 'image')) {
                return $clearbitUrl;
            }
        } catch (\Exception $e) {
            // Skip
        }

        return null;
    }

    protected function tryGoogleFavicon(Brand $brand): ?string
    {
        $domainMap = [
            'hugo-boss' => 'hugoboss.com',
            'prada' => 'prada.com',
            'burberry' => 'burberry.com',
            'ysl' => 'ysl.com',
            'armani' => 'armani.com',
            'kilian' => 'bykilian.com',
            'lattafa' => 'lattafa.com',
            'rasasi' => 'rasasi.com',
            'montale' => 'montaleparfums.com',
            'narciso-rodriguez' => 'narcisorodriguez.com',
        ];

        $domain = $domainMap[$brand->slug] ?? str_replace('-', '', $brand->slug) . '.com';
        $faviconUrl = "https://www.google.com/s2/favicons?domain={$domain}&sz=128";

        try {
            $response = Http::timeout(5)->get($faviconUrl);
            if ($response->successful() && strlen($response->body()) > 500) {
                return $faviconUrl;
            }
        } catch (\Exception $e) {
            // Skip
        }

        return null;
    }

    protected function downloadAndSave(Brand $brand, string $imgUrl): bool
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            ])->timeout(15)->get($imgUrl);

            if (!$response->successful()) {
                return false;
            }

            $contentType = $response->header('Content-Type') ?? '';
            if (!str_contains($contentType, 'image') && !str_contains($contentType, 'svg')) {
                return false;
            }

            $extMap = [
                'image/jpeg' => 'jpg', 'image/jpg' => 'jpg',
                'image/png' => 'png', 'image/webp' => 'webp',
                'image/gif' => 'gif', 'image/svg+xml' => 'svg',
                'image/x-icon' => 'png', 'image/vnd.microsoft.icon' => 'png',
                'image/avif' => 'avif',
            ];
            $ext = $extMap[strtolower(explode(';', $contentType)[0])] ?? 'png';

            $path = 'brands/' . $brand->slug . '.' . $ext;
            Storage::disk('public')->put($path, $response->body());
            $brand->update(['logo' => $path]);

            $this->imported++;
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
