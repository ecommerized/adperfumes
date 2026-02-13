<?php

namespace App\Console\Commands;

use App\Models\Brand;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class ScrapeParfumoBrandLogos extends Command
{
    protected $signature = 'brands:scrape-parfumo-logos {--delay=1000 : Delay between requests in ms}';
    protected $description = 'Scrape brand logos from Parfumo.com for brands missing logos';

    private $imported = 0;
    private $skipped = 0;
    private $notFound = 0;

    // Map brand names to Parfumo designer slugs
    private $brandMap = [
        'Dior' => 'Dior', 'Chanel' => 'Chanel', 'Tom Ford' => 'Tom-Ford',
        'Creed' => 'Creed', 'Amouage' => 'Amouage',
        'Initio' => 'Initio-Parfums-Prives', 'Roja' => 'Roja-Dove',
        'Xerjoff' => 'XerJoff', 'Kilian' => 'By-Kilian', 'By Kilian' => 'By-Kilian',
        'Byredo' => 'Byredo', 'Nishane' => 'Nishane',
        'Montale' => 'Montale', 'Mancera' => 'Mancera',
        'Parfums de Marly' => 'Parfums-de-Marly',
        'Maison Francis Kurkdjian' => 'Maison-Francis-Kurkdjian',
        'Acqua di Parma' => 'Acqua-di-Parma',
        'Giorgio Armani' => 'Giorgio-Armani', 'Armani' => 'Giorgio-Armani',
        'YSL' => 'Yves-Saint-Laurent', 'Yves Saint Laurent' => 'Yves-Saint-Laurent',
        'Hugo Boss' => 'Hugo-Boss', 'Calvin Klein' => 'Calvin-Klein',
        'Carolina Herrera' => 'Carolina-Herrera',
        'Dolce & Gabbana' => 'Dolce-Gabbana', 'Dolce and Gabbana' => 'Dolce-Gabbana',
        'Dolce &amp; Gabbana' => 'Dolce-Gabbana',
        'Jean Paul Gaultier' => 'Jean-Paul-Gaultier',
        'Narciso Rodriguez' => 'Narciso-Rodriguez',
        'Thierry Mugler' => 'Thierry-Mugler', 'Mugler' => 'Thierry-Mugler',
        'Viktor & Rolf' => 'Viktor-Rolf', 'Viktor&Rolf' => 'Viktor-Rolf',
        'Maison Margiela' => 'Maison-Martin-Margiela',
        'Paco Rabanne' => 'Paco-Rabanne', 'Gucci' => 'Gucci',
        'Versace' => 'Versace', 'Prada' => 'Prada', 'Burberry' => 'Burberry',
        'Givenchy' => 'Givenchy', 'Hermes' => 'Hermes',
        'Lancome' => 'Lancome', 'Cartier' => 'Cartier', 'Bvlgari' => 'Bvlgari',
        'Guerlain' => 'Guerlain', 'Louis Vuitton' => 'Louis-Vuitton',
        'Chloé' => 'Chloe',
        'Attar Collection' => 'Attar-Collection',
        'Lattafa' => 'Lattafa-Perfumes', 'Lattafa Perfumes' => 'Lattafa-Perfumes',
        'Rasasi' => 'Rasasi', 'Swiss Arabian' => 'Swiss-Arabian',
        'Afnan' => 'Afnan', 'Armaf' => 'Armaf',
        'AJMAL' => 'Ajmal', 'Ajmal' => 'Ajmal', 'Al Haramain' => 'Al-Haramain',
        'Tiziana Terenzi' => 'Tiziana-Terenzi', 'Serge Lutens' => 'Serge-Lutens',
        'Frederic Malle' => 'Frederic-Malle', 'Penhaligon' => 'Penhaligon-s',
        "Penhaligon's" => 'Penhaligon-s',
        'Atkinsons' => 'Atkinsons', 'Clive Christian' => 'Clive-Christian',
        'Clive Christain' => 'Clive-Christian',
        'Lacoste' => 'Lacoste', 'Jimmy Choo' => 'Jimmy-Choo',
        'Montblanc' => 'Montblanc', 'Mont Blanc' => 'Montblanc',
        'Guess' => 'Guess', 'Azzaro' => 'Azzaro', 'Davidoff' => 'Davidoff',
        'Elie Saab' => 'Elie-Saab', 'Lalique' => 'Lalique',
        'Boucheron' => 'Boucheron', 'Roberto Cavalli' => 'Roberto-Cavalli',
        'Ralph Lauren' => 'Ralph-Lauren', 'Valentino' => 'Valentino',
        'Moschino' => 'Moschino', 'Kenzo' => 'Kenzo', 'Nina Ricci' => 'Nina-Ricci',
        'Marc Jacobs' => 'Marc-Jacobs', 'Chopard' => 'Chopard',
        'Bentley' => 'Bentley', 'Jaguar' => 'Jaguar',
        'Mercedes-Benz' => 'Mercedes-Benz', 'Aigner' => 'Aigner',
        'Escada' => 'Escada', 'Issey Miyake' => 'Issey-Miyake',
        'Rochas' => 'Rochas', 'Cacharel' => 'Cacharel',
        'Elizabeth Arden' => 'Elizabeth-Arden', 'Estee Lauder' => 'Estee-Lauder',
        'Nasomatto' => 'Nasomatto', 'Orto Parisi' => 'Orto-Parisi',
        'BDK Parfums' => 'BDK-Parfums', 'BDK Parfumes' => 'BDK-Parfums',
        'BDK Perfumes' => 'BDK-Parfums',
        'Histoires de Parfums' => 'Histoires-de-Parfums',
        'V Canto' => 'V-Canto', 'Sospiro Perfumes' => 'Sospiro',
        'Bois 1920' => 'Bois-1920', 'Electimuss' => 'Electimuss',
        'Accendis' => 'Accendis', 'Essential Parfums' => 'Essential-Parfums',
        'Atelier Cologne' => 'Atelier-Cologne', 'Atelier des Ors' => 'Atelier-des-Ors',
        'Orlov Paris' => 'Orlov-Paris', 'House Of Sillage' => 'House-of-Sillage',
        'Miller Harris' => 'Miller-Harris', 'Kajal' => 'Kajal',
        'Annick Goutal' => 'Annick-Goutal', 'Jeroboam' => 'Jeroboam',
        'Bond No 9' => 'Bond-No-9', 'Fendi' => 'Fendi',
        'Escentric Molecules' => 'Escentric-Molecules',
        'DIPTYQUE' => 'Diptyque', 'Vertus' => 'Vertus',
        'Marc-Antoine Barrois' => 'Marc-Antoine-Barrois',
        'Beaufort' => 'Beaufort-London', 'Gisada' => 'Gisada',
        'Moncler' => 'Moncler', 'Lorenzo Pazzaglia' => 'Lorenzo-Pazzaglia',
        'Mizensir' => 'Mizensir', 'Coach' => 'Coach',
        'Dunhill' => 'Dunhill', 'Alfred Dunhill' => 'Dunhill',
        'Ferragamo' => 'Salvatore-Ferragamo', 'Salvatore Ferragamo' => 'Salvatore-Ferragamo',
        'Philipp Plein' => 'Philipp-Plein', 'Police' => 'Police',
        'Tommy Hilfiger' => 'Tommy-Hilfiger',
        'Abercrombie & Fitch' => 'Abercrombie-Fitch',
        'Angel Schlesser' => 'Angel-Schlesser',
        'Terry de Gunzburg' => 'Terry-de-Gunzburg',
        'Ted Lapidus' => 'Ted-Lapidus',
        'Sospiro Perfumes' => 'Sospiro',
        'Jacques Bogart' => 'Jacques-Bogart',
        'J. Del Pozo' => 'Jesus-del-Pozo', 'Jesus Del Pozo' => 'Jesus-del-Pozo',
        'Nicolai Parfumeur Createur' => 'Nicolai',
        'Moschino' => 'Moschino',
        'Jaguar' => 'Jaguar',
        'Eutopie' => 'Eutopie',
        'Escada' => 'Escada',
        'Alfred Dunhill' => 'Dunhill',
        'Cerruti 1881' => 'Cerruti',
        'Aramis' => 'Aramis',
        'Tommy Hilfiger' => 'Tommy-Hilfiger',
        'Jacomo' => 'Jacomo',
        'Cacharel' => 'Cacharel',
        'Giorgio Beverly Hills' => 'Giorgio-Beverly-Hills',
        'Maurice Roucel' => 'Maurice-Roucel',
        'Antonio Visconti' => 'Antonio-Visconti',
        'Lengling Munich' => 'Lengling-Munich',
        'David Walter' => 'David-Walter',
        'Matiere Premier' => 'Matiere-Premiere', 'MATIER PREMIER' => 'Matiere-Premiere',
        'Les Liquides Imaginaires' => 'Les-Liquides-Imaginaires',
        'Viktor&Rolf' => 'Viktor-Rolf',
        'La Perla' => 'La-Perla',
        'Antonio Banderas' => 'Antonio-Banderas',
        'ALEXANDER J' => 'Alexandre-J',
        'Splendor' => 'Splendor',
        'Sezan' => null,
        'Milton Lloyd' => 'Milton-Lloyd',
        'Nikos' => 'Nikos',
    ];

    public function handle()
    {
        $brands = Brand::whereNull('logo')->get();
        $total = $brands->count();
        $delay = (int) $this->option('delay');

        $this->info("Found {$total} brands without logos. Searching Parfumo...");
        $this->newLine();

        $bar = $this->output->createProgressBar($total);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% | Imported: %message%');
        $bar->setMessage('0');

        foreach ($brands as $brand) {
            $parfumoSlug = $this->getParfumoSlug($brand->name);

            if ($parfumoSlug === null) {
                $this->skipped++;
                $bar->advance();
                continue;
            }

            $logoUrl = $this->findLogoOnParfumo($parfumoSlug);

            if ($logoUrl) {
                $this->downloadAndSaveLogo($brand, $logoUrl);
            } else {
                $this->notFound++;
            }

            $bar->setMessage((string) $this->imported);
            $bar->advance();

            usleep($delay * 1000);
        }

        $bar->finish();
        $this->newLine(2);

        $this->table(['Status', 'Count'], [
            ['Imported', $this->imported],
            ['Not found on Parfumo', $this->notFound],
            ['Skipped (no mapping)', $this->skipped],
        ]);

        $this->info('Brands with logo: ' . Brand::whereNotNull('logo')->count() . '/' . Brand::count());

        return 0;
    }

    private function getParfumoSlug(string $brandName): ?string
    {
        if (isset($this->brandMap[$brandName])) {
            return $this->brandMap[$brandName];
        }

        // Auto-generate slug
        $slug = str_replace([' ', '&', '.', "'"], ['-', '', '', ''], $brandName);
        $slug = preg_replace('/-+/', '-', $slug);
        return trim($slug, '-');
    }

    private function findLogoOnParfumo(string $parfumoSlug): ?string
    {
        $url = "https://www.parfumo.com/Designers/{$parfumoSlug}";

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.5',
                'Referer' => 'https://www.parfumo.com/',
            ])->timeout(15)->get($url);

            if (!$response->successful()) return null;

            $html = $response->body();

            // Look for brand logo/image on the page
            // Pattern 1: og:image meta tag (often has the brand logo)
            if (preg_match('/<meta[^>]+property="og:image"[^>]+content="([^"]+)"/i', $html, $m)) {
                $imgUrl = $m[1];
                // Skip generic Parfumo images
                if (!str_contains($imgUrl, 'parfumo_logo') && !str_contains($imgUrl, 'no_image')) {
                    return $imgUrl;
                }
            }

            // Pattern 2: Designer logo image
            if (preg_match('/<img[^>]+class="[^"]*designer[_-]?logo[^"]*"[^>]+src="([^"]+)"/i', $html, $m)) {
                return str_starts_with($m[1], '//') ? 'https:' . $m[1] : $m[1];
            }

            // Pattern 3: Brand image in the header area
            if (preg_match('/<div[^>]*class="[^"]*brand[_-]?(?:header|logo|image)[^"]*"[^>]*>.*?<img[^>]+src="([^"]+)"/is', $html, $m)) {
                return str_starts_with($m[1], '//') ? 'https:' . $m[1] : $m[1];
            }

            // Pattern 4: Any large image in the top section that's likely a logo
            if (preg_match_all('/<img[^>]+src="(https?:\/\/[^"]+(?:designer|brand|logo)[^"]*\.(?:jpg|jpeg|png|webp))"/i', $html, $m)) {
                return $m[1][0];
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    private function downloadAndSaveLogo(Brand $brand, string $imageUrl): void
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'Referer' => 'https://www.parfumo.com/',
            ])->timeout(30)->get($imageUrl);

            if (!$response->successful()) {
                $this->notFound++;
                return;
            }

            $contentType = $response->header('Content-Type');
            $extension = match (true) {
                str_contains($contentType ?? '', 'png') => 'png',
                str_contains($contentType ?? '', 'webp') => 'webp',
                str_contains($contentType ?? '', 'gif') => 'gif',
                default => 'jpg',
            };

            $filename = $brand->slug . '.' . $extension;
            $path = 'brands/' . $filename;

            Storage::disk('public')->put($path, $response->body());
            $brand->update(['logo' => $path]);

            $this->imported++;
        } catch (\Exception $e) {
            $this->notFound++;
        }
    }
}
