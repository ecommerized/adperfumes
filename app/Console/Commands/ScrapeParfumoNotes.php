<?php

namespace App\Console\Commands;

use App\Models\Accord;
use App\Models\Note;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ScrapeParfumoNotes extends Command
{
    protected $signature = 'products:scrape-parfumo {--limit=0 : Max products to process} {--brand= : Only process specific brand slug} {--delay=800 : Delay between requests in ms} {--debug : Show debug output} {--search-only : Only use search, skip direct URL attempts}';
    protected $description = 'Scrape fragrance notes and accords from Parfumo.com';

    protected $updated = 0;
    protected $notFound = 0;
    protected $skipped = 0;
    protected $errors = 0;
    protected $noteCache = [];
    protected $accordCache = [];
    protected $debug = false;

    protected $brandMap = [
        // Major designer brands
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
        'Givenchy' => 'Givenchy', 'Hermes' => 'Hermes', 'Hermès' => 'Hermes',
        'Lancome' => 'Lancome', 'Lancôme' => 'Lancome',
        'Cartier' => 'Cartier', 'Bvlgari' => 'Bvlgari',
        'Guerlain' => 'Guerlain', 'Louis Vuitton' => 'Louis-Vuitton',
        'Chloé' => 'Chloe', 'Chloe' => 'Chloe',

        // Arabian brands
        'Attar Collection' => 'Attar-Collection',
        'Lattafa' => 'Lattafa-Perfumes', 'Lattafa Perfumes' => 'Lattafa-Perfumes',
        'Rasasi' => 'Rasasi', 'Swiss Arabian' => 'Swiss-Arabian',
        'Afnan' => 'Afnan', 'Armaf' => 'Armaf',
        'AJMAL' => 'Ajmal', 'Ajmal' => 'Ajmal',
        'Al Haramain' => 'Al-Haramain',
        'Ard Al Zaafaran' => 'Ard-Al-Zaafaran',
        'Asdaaf' => 'Asdaaf',

        // Niche brands
        'Tiziana Terenzi' => 'Tiziana-Terenzi', 'Serge Lutens' => 'Serge-Lutens',
        'Frederic Malle' => 'Frederic-Malle', 'Penhaligon' => 'Penhaligon-s',
        "Penhaligon's" => 'Penhaligon-s',
        'Atkinsons' => 'Atkinsons', 'Clive Christian' => 'Clive-Christian',
        'Clive Christain' => 'Clive-Christian',
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
        'DIPTYQUE' => 'Diptyque', 'Diptyque' => 'Diptyque',
        'Vertus' => 'Vertus', 'Marc-Antoine Barrois' => 'Marc-Antoine-Barrois',
        'Beaufort' => 'Beaufort-London', 'Gisada' => 'Gisada',
        'Moncler' => 'Moncler', 'Lorenzo Pazzaglia' => 'Lorenzo-Pazzaglia',
        'Mizensir' => 'Mizensir', 'Coach' => 'Coach',
        'EX NIHILO' => 'Ex-Nihilo',
        'MAISON CRIVELLI' => 'Maison-Crivelli',
        'Matiere Premiere' => 'Matiere-Premiere', 'Matiere Premier' => 'Matiere-Premiere',
        'MATIER PREMIER' => 'Matiere-Premiere',
        'Les Liquides Imaginaires' => 'Les-Liquides-Imaginaires',
        'Lengling Munich' => 'Lengling-Munich',

        // Designer mid-range
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
        'Etienne Aigner' => 'Aigner',
        'Escada' => 'Escada', 'Issey Miyake' => 'Issey-Miyake',
        'Rochas' => 'Rochas', 'Cacharel' => 'Cacharel',
        'Elizabeth Arden' => 'Elizabeth-Arden', 'Estee Lauder' => 'Estee-Lauder',
        'Dunhill' => 'Dunhill', 'Alfred Dunhill' => 'Dunhill',
        'Ferragamo' => 'Salvatore-Ferragamo', 'Salvatore Ferragamo' => 'Salvatore-Ferragamo',
        'Philipp Plein' => 'Philipp-Plein', 'Police' => 'Police',
        'Replay' => 'Replay', 'Trussardi' => 'Trussardi',
        'Tommy Hilfiger' => 'Tommy-Hilfiger',
        'Abercrombie & Fitch' => 'Abercrombie-Fitch',

        // Additional niche/artisan
        'M. Micallef' => 'M-Micallef', 'Memo' => 'Memo-Paris', 'Memo Paris' => 'Memo-Paris',
        'MiN New York' => 'MiN-New-York',
        'Ormonde Jayne' => 'Ormonde-Jayne',
        'Ramon Monegal' => 'Ramon-Monegal',
        'Carner Barcelona' => 'Carner-Barcelona', 'Carner' => 'Carner-Barcelona',
        'Thameen' => 'Thameen',
        'The Merchant of Venice' => 'The-Merchant-of-Venice',
        'The Spirit of Dubai' => 'The-Spirit-of-Dubai',
        'Thomas Kosmala' => 'Thomas-Kosmala',
        'Vilhelm Parfumerie' => 'Vilhelm-Parfumerie',
        "Unique'e Luxury" => 'Uniquee-Luxury',
        'Santa Eulalia' => 'Santa-Eulalia',
        'Room 1015' => 'Room-1015',
        'Tauer Perfumes' => 'Tauer-Perfumes',
        'Bohoboco' => 'Bohoboco',
        'Boadicea' => 'Boadicea-the-Victorious',
        'Ella K' => 'Ella-K',
        'Giardini Di Toscana' => 'Giardini-di-Toscana',
        'Gissah Perfumes' => 'Gissah', 'Gissah perfume' => 'Gissah',
        'ROSENDO MATEU' => 'Rosendo-Mateu',
        'Alexandre J' => 'Alexandre-J', 'Alexandre.J' => 'Alexandre-J',
        'ALEXANDER J' => 'Alexandre-J',

        // Classic/mainstream
        'Angel Schlesser' => 'Angel-Schlesser',
        'Antonio Banderas' => 'Antonio-Banderas',
        'Aramis' => 'Aramis',
        'Baldessarini' => 'Baldessarini',
        'Caron' => 'Caron',
        'Cerruti 1881' => 'Cerruti',
        'Elizabeth Taylor' => 'Elizabeth-Taylor',
        'Emanuel Ungaro' => 'Emanuel-Ungaro',
        'Jacques Bogart' => 'Jacques-Bogart',
        'Jacomo' => 'Jacomo',
        'Jennifer Lopez' => 'Jennifer-Lopez',
        'Joop' => 'JOOP',
        'Juicy Couture' => 'Juicy-Couture',
        'Mauboussin' => 'Mauboussin',
        'Nicolai Parfumeur Createur' => 'Nicolai',
        'Pascal Morabito' => 'Pascal-Morabito',
        'Revlon' => 'Revlon',
        'Ted Lapidus' => 'Ted-Lapidus',
        'Terry de Gunzburg' => 'Terry-de-Gunzburg',
        'Vince Camuto' => 'Vince-Camuto',
        'Jovan' => 'Jovan',
        'Giorgio Beverly Hills' => 'Giorgio-Beverly-Hills',
        'Armand Basi' => 'Armand-Basi',
        '4711' => '4711',
        'Shaik' => 'Shaik',
        'IKKS' => 'IKKS',
        'De Gabor' => 'De-Gabor',

        // Additional niche
        'Amouroud' => 'Amouroud', 'Anfas' => 'Anfas',
        'Amorino' => 'Amorino', 'Agatho' => 'Agatho-Parfum',
        'A Lab On Fire' => 'A-Lab-on-Fire',
        'Aether' => 'Aether',
        'Borntostandout' => 'Borntostandout',
        'Fabbrica Della Musa' => 'Fabbrica-Della-Musa',
        'Antonio Visconti' => 'Antonio-Visconti',
        'David Walter' => 'David-Walter',
        'Billie Eilish' => 'Billie-Eilish',
        'Nejma' => 'Nejma',
        'Nayassia' => 'Nayassia',
        'Lord Of History' => 'Lord-of-History',
        'J. Del Pozo' => 'Jesus-del-Pozo', 'Jesus Del Pozo' => 'Jesus-del-Pozo',
        'Remy Marquis' => 'Remy-Marquis',
        'Six Scents' => 'Six-Scents',
        'Wesker' => 'Wesker',
        'Maurice Roucel' => 'Maurice-Roucel',
        'L\'ARC' => 'L-ARC',
        'Une Nuit Nomade' => 'Une-Nuit-Nomade',

        // Brands to skip (not on Parfumo / not perfume brands)
        'AD Perfumes' => null, 'My Store' => null, 'Abu Dhabi Store' => null,
        'De Luxe Collection' => null, 'Panier' => null,
        'Smile Perfumes' => null, 'Alrajul' => null,
        'Quaintness' => null, 'Quaintness Perfumes' => null,
        'Loui Martin' => null, 'Laverne' => null,
        'SAMAM' => null, 'Saman' => null,
        'Madawi' => null, 'Pensée' => null,
        'Datura Perfumes' => null, 'Mariya' => null,
        'Iven' => null, 'NOSTOS' => null,
        'Experimentum Crucis' => null, 'Kalikat' => null,
        'Derrah' => null, 'Deraah' => null,
        'Lunatique' => null, 'Box Asfer' => null,
        'Basma' => null, 'Foah' => null,
        'Dkhoun' => null, 'Dukhoon' => null,
        'Doranza' => null, 'DyRose Perfumes' => null,
        'Cavalier' => null, 'Ateej' => null,
        'Citrus Ester' => null, 'Anonymous' => null,
        'Marc Avento' => null, 'Violet' => null,
        'Zodiac' => null, 'Niche Emarati' => null,
        'Signature' => null, 'Al Ghabra' => null,
        'Alghabra' => null, 'Oman Luxury' => null,
        'Omanluxury' => null,
        'Jo Milano' => null, 'Osaïto' => null,
        'Al majed' => null,
    ];

    // Non-perfume product keywords to skip
    protected $skipKeywords = [
        'soap', 'shower gel', 'body lotion', 'body cream', 'body milk',
        'deodorant', 'deo stick', 'hair mist', 'body mist', 'aftershave balm',
        'shaving cream', 'hand cream', 'candle', 'diffuser', 'incense',
        'gift set', 'travel set', 'discovery set', 'sample', 'miniature',
        'body wash', 'body spray', 'fabric softener', 'laundry',
        'bakhoor', 'bukhoor', 'oud oil', 'attar oil', 'roll on',
    ];

    public function handle()
    {
        ini_set('memory_limit', '512M');
        $this->debug = $this->option('debug');

        $this->noteCache = Note::all()->mapWithKeys(fn($n) => [strtolower($n->name) => $n->id])->toArray();
        $this->accordCache = Accord::all()->mapWithKeys(fn($a) => [strtolower($a->name) => $a->id])->toArray();

        $query = Product::whereDoesntHave('notes')
            ->whereNotNull('brand_id')
            ->with('brand')
            ->orderBy('brand_id');

        if ($brandSlug = $this->option('brand')) {
            $query->whereHas('brand', fn($q) => $q->where('slug', $brandSlug));
        }

        $limit = (int) $this->option('limit');
        if ($limit > 0) {
            $query->limit($limit);
        }

        $products = $query->get()->filter(function ($product) {
            $brandName = $product->brand?->name;
            if (!$brandName) return false;
            if (isset($this->brandMap[$brandName]) && $this->brandMap[$brandName] === null) return false;
            return true;
        });

        $total = $products->count();
        $delay = (int) $this->option('delay');

        $this->info("Scraping notes from Parfumo.com for {$total} products...");
        $this->info("Delay: {$delay}ms between requests");
        $this->newLine();

        $bar = $this->output->createProgressBar($total);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% | Updated: %message%');
        $bar->setMessage('0');

        foreach ($products as $product) {
            $cleanName = $this->cleanPerfumeName($product->name, $product->brand->name);

            // Skip non-perfume products
            if ($this->isNonPerfume($product->name, $cleanName)) {
                $this->skipped++;
                $bar->advance();
                continue;
            }

            $data = $this->scrapeProduct($product, $product->brand->name, $cleanName);

            if ($data && (!empty($data['top']) || !empty($data['middle']) || !empty($data['base']))) {
                $this->applyData($product, $data);
                if ($this->debug) {
                    $this->newLine();
                    $this->line("  OK: {$product->name} => T:" . implode(',', $data['top']) .
                        " M:" . implode(',', $data['middle']) .
                        " B:" . implode(',', $data['base']) .
                        " A:" . implode(',', $data['accords']));
                }
            } else {
                $this->notFound++;
                if ($this->debug) {
                    $this->newLine();
                    $this->line("  MISS: {$product->name} => cleaned: {$cleanName}");
                }
            }

            $bar->setMessage((string) $this->updated);
            $bar->advance();

            usleep($delay * 1000);

            if (($this->updated + $this->notFound + $this->errors + $this->skipped) % 50 === 0) {
                gc_collect_cycles();
            }
        }

        $bar->finish();
        $this->newLine(2);

        $this->table(['Status', 'Count'], [
            ['Updated with notes', $this->updated],
            ['Not found on Parfumo', $this->notFound],
            ['Skipped (non-perfume)', $this->skipped],
            ['Errors', $this->errors],
        ]);

        $this->info('Total notes in DB: ' . Note::count());
        $this->info('Total accords in DB: ' . Accord::count());
        $this->info('Products with notes: ' . Product::whereHas('notes')->count());
        $this->info('Products with accords: ' . Product::whereHas('accords')->count());

        return 0;
    }

    protected function isNonPerfume(string $originalName, string $cleanName): bool
    {
        $lowerName = strtolower($originalName);
        foreach ($this->skipKeywords as $keyword) {
            if (str_contains($lowerName, $keyword)) {
                return true;
            }
        }
        if (strlen($cleanName) < 2) {
            return true;
        }
        return false;
    }

    protected function scrapeProduct(Product $product, string $brandName, string $cleanName): ?array
    {
        if (empty($cleanName)) return null;

        $delay = (int) $this->option('delay');
        $searchOnly = $this->option('search-only');

        // Strategy 1: Direct URL matching (fast, no search needed)
        if (!$searchOnly) {
            $parfumoBrand = $this->getParfumoBrand($brandName);
            if ($parfumoBrand) {
                $urls = $this->buildParfumoUrls($parfumoBrand, $cleanName, $product->name);

                foreach ($urls as $i => $url) {
                    if ($i > 0) usleep($delay * 1000);

                    $data = $this->fetchAndParsePage($url);
                    if ($data) return $data;
                }
            }
        }

        // Strategy 2: Search-based matching (slower but much higher hit rate)
        usleep($delay * 1000);
        $data = $this->searchParfumo($brandName, $cleanName);
        if ($data) return $data;

        return null;
    }

    /**
     * Search Parfumo.com and scrape the first matching result.
     */
    protected function searchParfumo(string $brandName, string $perfumeName): ?array
    {
        $searchQuery = $brandName . ' ' . $perfumeName;
        // Use the AJAX search endpoint for better results
        $searchUrl = 'https://www.parfumo.com/s_perfumes_x.php?string_search=' . urlencode($searchQuery);

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.5',
                'Referer' => 'https://www.parfumo.com/search',
            ])->timeout(15)->get($searchUrl);

            if (!$response->successful()) return null;

            $html = $response->body();

            // Look for perfume links in search results (both absolute and relative URLs)
            if (preg_match_all('/href="((?:https?:\/\/www\.parfumo\.com)?\/Perfumes\/[^"\/]+\/[^"]+)"/i', $html, $matches)) {
                $delay = (int) $this->option('delay');
                $seen = [];
                $tried = 0;

                foreach ($matches[1] as $path) {
                    if ($tried >= 2) break;

                    // Skip review/comment pages and brand-only pages
                    if (str_contains($path, '/Reviews') || str_contains($path, '/Comments')) continue;

                    // Normalize to full URL
                    $perfumeUrl = str_starts_with($path, 'http') ? $path : 'https://www.parfumo.com' . $path;

                    // Skip duplicates
                    if (isset($seen[$perfumeUrl])) continue;
                    $seen[$perfumeUrl] = true;

                    usleep($delay * 1000);

                    $data = $this->fetchAndParsePage($perfumeUrl);
                    if ($data) return $data;

                    $tried++;
                }
            }

            return null;
        } catch (\Exception $e) {
            $this->errors++;
            return null;
        }
    }

    /**
     * Fetch a Parfumo page and parse notes/accords from it.
     */
    protected function fetchAndParsePage(string $url): ?array
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.5',
                'Accept-Encoding' => 'gzip, deflate',
                'Connection' => 'keep-alive',
                'Referer' => 'https://www.parfumo.com/',
            ])->timeout(15)->get($url);

            if ($response->status() === 200) {
                $html = $response->body();

                // Parfumo returns 200 for 404 pages - check page size
                // Real pages are 15K+, error/block pages are ~2K
                if (strlen($html) < 10000) {
                    return null;
                }

                $data = $this->parseParfumoPage($html);
                if ($data && (!empty($data['top']) || !empty($data['middle']) || !empty($data['base']))) {
                    return $data;
                }
            }

            if ($response->status() === 429) {
                sleep(5);
            }
        } catch (\Exception $e) {
            $this->errors++;
        }

        return null;
    }

    protected function cleanPerfumeName(string $name, string $brandName): string
    {
        // Remove brand name from start
        if (!empty($brandName) && stripos($name, $brandName) === 0) {
            $name = trim(substr($name, strlen($brandName)));
        }
        // Also strip "Miss {Brand}" or similar
        $name = preg_replace('/^Miss\s+' . preg_quote($brandName, '/') . '\s*/i', 'Miss ' . $brandName . ' ', $name);

        // Remove "Christian" prefix with optional gendered words
        $name = preg_replace('/^Christian\s+(Men\'?s?|Women\'?s?|Unisex|Ladies)?\s*/i', '', $name);

        // Remove leading dashes/spaces
        $name = preg_replace('/^[\-\x{2013}\x{2014}\s]+/u', '', $name);

        // Remove "Men's", "Women's", "Ladies", "Unisex" prefix
        $name = preg_replace('/^(Men\'?s?|Women\'?s?|Ladies|Unisex)\s+/i', '', $name);

        // Remove "by Brand" or "by Christian Brand" suffix
        $name = preg_replace('/\s+by\s+(?:Christian\s+)?\w+.*$/i', '', $name);

        // Remove ALL parenthetical content
        $name = preg_replace('/\s*\([^)]*\)/i', '', $name);

        // Remove concentration keywords with everything after them
        $name = preg_replace('/\s*(?:Eau\s+de\s+(?:Parfum|Perfume|Toilette|Cologne)|EDP|EDT|EDC|Parfum|Cologne|Extrait(?:\s+de\s+Parfum)?)\s*(?:\d.*)?$/i', '', $name);

        // Remove size (ml, oz) and everything after
        $name = preg_replace('/\s*\d+(?:\.\d+)?\s*(?:ml|oz|fl\.?\s*oz)\b.*/i', '', $name);

        // Remove "Perfume", "Spray", "Fragrances" etc.
        $name = preg_replace('/\s*(?:Perfume|Perfumes?|Spray|Fragrances?|Spy)\s*$/i', '', $name);

        // Remove "EDP", "EDT" at end
        $name = preg_replace('/\s+(?:EDP|EDT|EDC)\s*$/i', '', $name);

        // Remove product type suffixes
        $name = preg_replace('/\s*(?:Tester|WOB|Travel\s*Pack|Gift\s*Set)\s*$/i', '', $name);

        // Remove "for Men/Women/Unisex"
        $name = preg_replace('/\s+for\s+(?:Men|Women|Him|Her|Unisex)\s*$/i', '', $name);

        // Remove concentrated perfume oil
        $name = preg_replace('/\s*Concentrated\s+Perfume\s+Oil.*$/i', '', $name);

        // Remove trailing/leading dashes, dots, spaces
        $name = preg_replace('/[\-\.\s]+$/', '', $name);
        $name = preg_replace('/^[\-\.\s]+/', '', $name);

        return trim($name);
    }

    protected function getParfumoBrand(string $brandName): ?string
    {
        if (isset($this->brandMap[$brandName])) {
            return $this->brandMap[$brandName];
        }

        $parfumoBrand = str_replace(' ', '-', $brandName);
        $parfumoBrand = preg_replace('/[&.\']/', '', $parfumoBrand);
        $parfumoBrand = preg_replace('/-+/', '-', $parfumoBrand);

        return $parfumoBrand;
    }

    protected function buildParfumoUrls(string $brand, string $perfumeName, string $originalName = ''): array
    {
        $urls = [];

        $urlName = $this->toUrlSlug($perfumeName);
        if (empty($urlName)) return [];

        $brandPrefix = str_replace(['-', '_'], ' ', $brand);
        $withBrandPrefix = $this->toUrlSlug($brandPrefix . ' ' . $perfumeName);

        // Detect concentration from original name
        $lowerOrig = strtolower($originalName);
        $concentrations = [];
        if (str_contains($lowerOrig, 'elixir')) {
            // Elixir is part of the name
        } elseif (str_contains($lowerOrig, 'eau de parfum') || str_contains($lowerOrig, '(edp)') || str_contains($lowerOrig, ' edp ')) {
            $concentrations[] = 'Eau_de_Parfum';
        } elseif (str_contains($lowerOrig, 'eau de toilette') || str_contains($lowerOrig, '(edt)') || str_contains($lowerOrig, ' edt ')) {
            $concentrations[] = 'Eau_de_Toilette';
        } elseif (str_contains($lowerOrig, 'parfum') && !str_contains($lowerOrig, 'eau de parfum')) {
            $concentrations[] = 'Parfum';
        } elseif (str_contains($lowerOrig, 'eau de cologne') || str_contains($lowerOrig, '(edc)')) {
            $concentrations[] = 'Eau_de_Cologne';
        }

        // Try WITH brand prefix first
        if ($withBrandPrefix !== $urlName) {
            $urls[] = "https://www.parfumo.com/Perfumes/{$brand}/{$withBrandPrefix}";
        }

        // Without brand prefix
        $urls[] = "https://www.parfumo.com/Perfumes/{$brand}/{$urlName}";

        // With concentration suffix
        foreach ($concentrations as $conc) {
            if ($withBrandPrefix !== $urlName) {
                $urls[] = "https://www.parfumo.com/Perfumes/{$brand}/{$withBrandPrefix}_{$conc}";
            }
            $urls[] = "https://www.parfumo.com/Perfumes/{$brand}/{$urlName}_{$conc}";
        }

        // Only add generic fallback if no concentration detected (limit to 4 URLs max)
        if (empty($concentrations) && count($urls) < 3) {
            $urls[] = "https://www.parfumo.com/Perfumes/{$brand}/{$urlName}_Eau_de_Parfum";
        }

        return array_values(array_unique($urls));
    }

    protected function toUrlSlug(string $text): string
    {
        // Parfumo uses underscores in URLs
        $slug = str_replace(' ', '_', $text);
        $slug = str_replace("'", '-', $slug); // J'adore -> J-adore
        $slug = preg_replace('/[^a-zA-Z0-9\-_]/', '', $slug);
        $slug = preg_replace('/[_]+/', '_', $slug);
        $slug = preg_replace('/[-]+/', '-', $slug);
        return trim($slug, '-_');
    }

    protected function parseParfumoPage(string $html): ?array
    {
        $data = ['top' => [], 'middle' => [], 'base' => [], 'accords' => []];

        $noteTypeMap = ['t' => 'top', 'm' => 'middle', 'b' => 'base'];

        // Method 1: clickable_note_img spans with data-nt attribute
        if (preg_match_all('/<span[^>]*class="clickable_note_img[^"]*"[^>]*data-nt="([tmb])"[^>]*>.*?<span[^>]*class="nowrap[^"]*"[^>]*>(?:<img[^>]+alt="([^"]+)"[^>]*)?\s*([^<]*)<\/span>/is', $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $type = $noteTypeMap[$match[1]] ?? null;
                $noteName = trim($match[2]) ?: trim($match[3]);
                if ($type && !empty($noteName) && strlen($noteName) >= 2) {
                    $data[$type][] = $noteName;
                }
            }
        }

        // Method 2: Fallback - pyramid blocks
        if (empty($data['top']) && empty($data['middle']) && empty($data['base'])) {
            $sections = ['nb_t' => 'top', 'nb_m' => 'middle', 'nb_b' => 'base'];

            foreach ($sections as $cssClass => $type) {
                $pattern = '/class="pyramid_block\s+' . preg_quote($cssClass) . '.*?<\/div>\s*<div[^>]*class="right"[^>]*>(.*?)(?=<div[^>]*class="pyramid_block|<\/div>\s*<\/div>\s*<\/div>)/is';
                if (preg_match($pattern, $html, $m)) {
                    if (preg_match_all('/alt="([^"]{2,40})"/i', $m[1], $nm)) {
                        $data[$type] = array_map('trim', $nm[1]);
                    }
                }
            }
        }

        // Method 3: Section headings
        if (empty($data['top']) && empty($data['middle']) && empty($data['base'])) {
            $sections = ['Top Notes' => 'top', 'Heart Notes' => 'middle', 'Base Notes' => 'base'];

            foreach ($sections as $heading => $type) {
                $pattern = '/' . preg_quote($heading) . '(.*?)(?=(?:Top|Heart|Base)\s*Notes|Main\s*Accords|Fragrance\s+Pyramid|<\/section)/is';
                if (preg_match($pattern, $html, $m)) {
                    if (preg_match_all('/alt="([^"]{2,40})"/i', $m[1], $nm)) {
                        $data[$type] = array_map('trim', $nm[1]);
                    }
                }
            }
        }

        // Method 4: data-note-name attributes (newer Parfumo layout)
        if (empty($data['top']) && empty($data['middle']) && empty($data['base'])) {
            if (preg_match_all('/data-nt="([tmb])"[^>]*data-note-name="([^"]+)"/i', $html, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $type = $noteTypeMap[$match[1]] ?? null;
                    $noteName = trim(html_entity_decode($match[2]));
                    if ($type && !empty($noteName) && strlen($noteName) >= 2) {
                        $data[$type][] = $noteName;
                    }
                }
            }
        }

        // Parse accords
        if (preg_match('/Main\s*Accords(.*?)(?=Fragrance\s+Pyramid|<\/div>\s*<\/div>\s*<\/div>|smell_it_container|<h2)/is', $html, $accordSection)) {
            if (preg_match_all('/<div[^>]*class="text-xs\s+grey"[^>]*>([^<]+)<\/div>/is', $accordSection[1], $am)) {
                foreach ($am[1] as $accord) {
                    $accord = trim($accord);
                    if (strlen($accord) >= 2 && strlen($accord) <= 30) {
                        $data['accords'][] = $accord;
                    }
                }
            }
        }

        // Alternative accord pattern
        if (empty($data['accords'])) {
            if (preg_match_all('/class="accord-bar"[^>]*>.*?<span[^>]*>([^<]+)<\/span>/is', $html, $am)) {
                foreach ($am[1] as $accord) {
                    $accord = trim($accord);
                    if (strlen($accord) >= 2 && strlen($accord) <= 30) {
                        $data['accords'][] = $accord;
                    }
                }
            }
        }

        if (empty($data['top']) && empty($data['middle']) && empty($data['base'])) {
            return null;
        }

        // Clean up
        $data['top'] = array_values(array_unique($data['top']));
        $data['middle'] = array_values(array_unique($data['middle']));
        $data['base'] = array_values(array_unique($data['base']));
        $data['accords'] = array_values(array_unique($data['accords']));

        // Filter false positives
        foreach (['top', 'middle', 'base'] as $type) {
            $data[$type] = array_values(array_filter($data[$type], function ($name) {
                $lower = strtolower($name);
                return !str_contains($lower, 'pyramid') && !str_contains($lower, 'note icon');
            }));
        }

        return $data;
    }

    protected function applyData(Product $product, array $data): void
    {
        $noteData = [];
        $accordData = [];

        foreach (['top', 'middle', 'base'] as $type) {
            foreach ($data[$type] as $noteName) {
                $noteId = $this->getOrCreateNote($noteName);
                if ($noteId) {
                    $noteData[$noteId] = ['type' => $type];
                }
            }
        }

        foreach ($data['accords'] as $accordName) {
            $accordId = $this->getOrCreateAccord($accordName);
            if ($accordId) {
                $accordData[$accordId] = ['percentage' => null];
            }
        }

        if (!empty($noteData) || !empty($accordData)) {
            try {
                // Re-check product still exists (may have been deleted by cleanup)
                if (!Product::where('id', $product->id)->exists()) {
                    return;
                }
                DB::transaction(function () use ($product, $noteData, $accordData) {
                    if (!empty($noteData)) {
                        $product->notes()->syncWithoutDetaching($noteData);
                    }
                    if (!empty($accordData)) {
                        $product->accords()->syncWithoutDetaching($accordData);
                    }
                });
                $this->updated++;
            } catch (\Exception $e) {
                $this->errors++;
            }
        }
    }

    protected function getOrCreateNote(string $name): ?int
    {
        $name = trim($name);
        if (empty($name) || strlen($name) < 2 || strlen($name) > 50) return null;

        $key = strtolower($name);
        if (isset($this->noteCache[$key])) return $this->noteCache[$key];

        $note = Note::firstOrCreate(['name' => $name]);
        $this->noteCache[$key] = $note->id;
        return $note->id;
    }

    protected function getOrCreateAccord(string $name): ?int
    {
        $name = trim($name);
        if (empty($name) || strlen($name) < 2 || strlen($name) > 40) return null;

        $key = strtolower($name);
        if (isset($this->accordCache[$key])) return $this->accordCache[$key];

        $accord = Accord::firstOrCreate(['name' => $name], ['slug' => Str::slug($name)]);
        $this->accordCache[$key] = $accord->id;
        return $accord->id;
    }
}
