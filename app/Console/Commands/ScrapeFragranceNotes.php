<?php

namespace App\Console\Commands;

use App\Models\Accord;
use App\Models\Note;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ScrapeFragranceNotes extends Command
{
    protected $signature = 'products:scrape-notes {--limit=0 : Max products to process} {--brand= : Only process specific brand slug}';
    protected $description = 'Scrape fragrance notes and accords from Fragrantica for products';

    protected $updated = 0;
    protected $notFound = 0;
    protected $errors = 0;
    protected $noteCache = [];
    protected $accordCache = [];

    public function handle()
    {
        ini_set('memory_limit', '512M');

        // Preload note and accord caches
        $this->noteCache = Note::all()->keyBy(fn($n) => strtolower($n->name))->toArray();
        $this->accordCache = Accord::all()->keyBy(fn($a) => strtolower($a->name))->toArray();

        // Get products without notes
        $query = Product::whereDoesntHave('notes')
            ->whereNotNull('brand_id')
            ->with('brand')
            ->orderBy('brand_id');

        if ($this->option('brand')) {
            $query->whereHas('brand', fn($q) => $q->where('slug', $this->option('brand')));
        }

        $limit = (int) $this->option('limit');
        if ($limit > 0) {
            $query->limit($limit);
        }

        $products = $query->get();
        $total = $products->count();

        $this->info("Scraping notes for {$total} products...");
        $this->info("Existing notes: " . count($this->noteCache) . ", accords: " . count($this->accordCache));
        $this->newLine();

        $bar = $this->output->createProgressBar($total);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% | Updated: %message%');
        $bar->setMessage('0');

        foreach ($products as $product) {
            $brandName = $product->brand?->name ?? '';
            $productName = $this->cleanProductName($product->name, $brandName);

            $data = $this->searchFragrantica($brandName, $productName);

            if ($data) {
                $this->applyFragranceData($product, $data);
            } else {
                $this->notFound++;
            }

            $bar->setMessage((string) $this->updated);
            $bar->advance();

            // Rate limit - 500ms between requests to be respectful
            usleep(500000);

            // Free memory periodically
            if (($this->updated + $this->notFound + $this->errors) % 100 === 0) {
                gc_collect_cycles();
            }
        }

        $bar->finish();
        $this->newLine(2);

        $this->table(['Status', 'Count'], [
            ['Updated with notes', $this->updated],
            ['Not found on Fragrantica', $this->notFound],
            ['Errors', $this->errors],
        ]);

        $this->info('Total notes in DB: ' . Note::count());
        $this->info('Total accords in DB: ' . Accord::count());
        $this->info('Products with notes: ' . Product::whereHas('notes')->count());

        return 0;
    }

    protected function cleanProductName(string $name, string $brandName): string
    {
        // Remove brand name from the beginning
        if (!empty($brandName) && stripos($name, $brandName) === 0) {
            $name = trim(substr($name, strlen($brandName)));
        }

        // Remove common suffixes like "EDP 100ml", "EDT 50ml", etc.
        $name = preg_replace('/\s*(EDP|EDT|Parfum|Cologne|EDP\/EDT)\s*\d+ml.*/i', '', $name);
        $name = preg_replace('/\s*\d+\s*ml.*/i', '', $name);
        $name = preg_replace('/\s*\(T\).*$/i', '', $name); // tester
        $name = preg_replace('/\s*Tester.*$/i', '', $name);
        $name = preg_replace('/\s*WOB.*$/i', '', $name); // without box
        $name = preg_replace('/\s*TRAVEL.*$/i', '', $name);
        $name = preg_replace('/\s*Travel Pack.*$/i', '', $name);
        $name = preg_replace('/\s*Gift Set.*$/i', '', $name);
        $name = preg_replace('/\s*Set\s*$/i', '', $name);
        $name = preg_replace('/\s*for\s+(Men|Women|Unisex)\s*$/i', '', $name);

        // Clean leading dash or hyphen
        $name = ltrim($name, '-– ');

        return trim($name);
    }

    protected function searchFragrantica(string $brand, string $perfume): ?array
    {
        if (empty($perfume)) return null;

        $searchQuery = $brand . ' ' . $perfume;
        $searchUrl = 'https://www.fragrantica.com/search/?query=' . urlencode($searchQuery);

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.5',
            ])->timeout(15)->get($searchUrl);

            if (!$response->successful()) return null;

            $html = $response->body();

            // Find first perfume link in search results
            if (preg_match('/href="(\/perfume\/[^"]+\.html)"/', $html, $m)) {
                $perfumeUrl = 'https://www.fragrantica.com' . $m[1];
                return $this->scrapeFragranticaPage($perfumeUrl);
            }

            return null;
        } catch (\Exception $e) {
            $this->errors++;
            return null;
        }
    }

    protected function scrapeFragranticaPage(string $url): ?array
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.5',
                'Referer' => 'https://www.fragrantica.com/',
            ])->timeout(15)->get($url);

            if (!$response->successful()) return null;

            $html = $response->body();
            $data = [
                'top_notes' => [],
                'middle_notes' => [],
                'base_notes' => [],
                'accords' => [],
            ];

            // Parse notes using pyramid structure
            // Top notes
            if (preg_match('/Top Notes.*?<div[^>]*>(.*?)<\/div>/is', $html, $section)) {
                $data['top_notes'] = $this->extractNotes($section[1]);
            }
            if (empty($data['top_notes']) && preg_match('/top\s*notes?[^<]*<[^>]*>(.*?)<(?:\/div|\/span|br)/is', $html, $section)) {
                $data['top_notes'] = $this->extractNotes($section[1]);
            }

            // Middle/Heart notes
            if (preg_match('/(?:Middle|Heart) Notes.*?<div[^>]*>(.*?)<\/div>/is', $html, $section)) {
                $data['middle_notes'] = $this->extractNotes($section[1]);
            }
            if (empty($data['middle_notes']) && preg_match('/(?:middle|heart)\s*notes?[^<]*<[^>]*>(.*?)<(?:\/div|\/span|br)/is', $html, $section)) {
                $data['middle_notes'] = $this->extractNotes($section[1]);
            }

            // Base notes
            if (preg_match('/Base Notes.*?<div[^>]*>(.*?)<\/div>/is', $html, $section)) {
                $data['base_notes'] = $this->extractNotes($section[1]);
            }
            if (empty($data['base_notes']) && preg_match('/base\s*notes?[^<]*<[^>]*>(.*?)<(?:\/div|\/span|br)/is', $html, $section)) {
                $data['base_notes'] = $this->extractNotes($section[1]);
            }

            // Alternative: Extract notes from img alt texts in note pyramid
            if (empty($data['top_notes']) && empty($data['middle_notes']) && empty($data['base_notes'])) {
                // Fragrantica uses pyramid with note names in spans
                if (preg_match_all('/<span[^>]*>\s*<a[^>]*href="[^"]*notes[^"]*"[^>]*>([^<]+)<\/a>/i', $html, $allNotes)) {
                    $notes = array_map('trim', $allNotes[1]);
                    // Distribute evenly if can't determine type
                    $third = ceil(count($notes) / 3);
                    $data['top_notes'] = array_slice($notes, 0, $third);
                    $data['middle_notes'] = array_slice($notes, $third, $third);
                    $data['base_notes'] = array_slice($notes, $third * 2);
                }
            }

            // Try yet another pattern - note images with alt text
            if (empty($data['top_notes']) && empty($data['middle_notes']) && empty($data['base_notes'])) {
                // Look for pyramid-section divs
                if (preg_match_all('/pyramid-level.*?<\/div>/is', $html, $sections)) {
                    foreach ($sections[0] as $i => $section) {
                        if (preg_match_all('/<img[^>]+alt="([^"]+)"[^>]*>/i', $section, $imgs)) {
                            $noteNames = array_map('trim', $imgs[1]);
                            if ($i === 0) $data['top_notes'] = $noteNames;
                            elseif ($i === 1) $data['middle_notes'] = $noteNames;
                            elseif ($i === 2) $data['base_notes'] = $noteNames;
                        }
                    }
                }
            }

            // Parse accords
            if (preg_match_all('/class="[^"]*accord[^"]*"[^>]*style="[^"]*width:\s*(\d+)[^"]*"[^>]*>\s*([^<]+)/i', $html, $accordMatches, PREG_SET_ORDER)) {
                foreach ($accordMatches as $match) {
                    $data['accords'][] = [
                        'name' => trim($match[2]),
                        'percentage' => (int) $match[1],
                    ];
                }
            }

            // Alternative accord parsing
            if (empty($data['accords']) && preg_match_all('/<div[^>]*class="[^"]*cell accord[^"]*"[^>]*>.*?<div[^>]*>([^<]+)<\/div>/is', $html, $accordMatches)) {
                foreach ($accordMatches[1] as $accordName) {
                    $data['accords'][] = [
                        'name' => trim($accordName),
                        'percentage' => null,
                    ];
                }
            }

            // Return null if we found nothing useful
            if (empty($data['top_notes']) && empty($data['middle_notes']) && empty($data['base_notes']) && empty($data['accords'])) {
                return null;
            }

            return $data;
        } catch (\Exception $e) {
            $this->errors++;
            return null;
        }
    }

    protected function extractNotes(string $html): array
    {
        $notes = [];

        // Extract from links
        if (preg_match_all('/<a[^>]*>([^<]+)<\/a>/i', $html, $matches)) {
            $notes = array_merge($notes, array_map('trim', $matches[1]));
        }

        // Extract from img alt texts
        if (preg_match_all('/<img[^>]+alt="([^"]+)"[^>]*>/i', $html, $matches)) {
            $notes = array_merge($notes, array_map('trim', $matches[1]));
        }

        // Extract from spans
        if (empty($notes) && preg_match_all('/<span[^>]*>([^<]+)<\/span>/i', $html, $matches)) {
            $notes = array_merge($notes, array_map('trim', $matches[1]));
        }

        // Clean up
        $notes = array_filter($notes, fn($n) => strlen($n) > 1 && strlen($n) < 50);
        return array_unique($notes);
    }

    protected function applyFragranceData(Product $product, array $data): void
    {
        $noteIds = [];

        foreach (['top' => 'top_notes', 'middle' => 'middle_notes', 'base' => 'base_notes'] as $type => $key) {
            foreach ($data[$key] as $noteName) {
                $noteId = $this->getOrCreateNote($noteName, $type);
                if ($noteId) {
                    $noteIds[] = $noteId;
                }
            }
        }

        $accordData = [];
        foreach ($data['accords'] as $accord) {
            $accordId = $this->getOrCreateAccord($accord['name']);
            if ($accordId) {
                $accordData[$accordId] = ['percentage' => $accord['percentage']];
            }
        }

        if (!empty($noteIds) || !empty($accordData)) {
            DB::transaction(function () use ($product, $noteIds, $accordData) {
                if (!empty($noteIds)) {
                    $product->notes()->syncWithoutDetaching($noteIds);
                }
                if (!empty($accordData)) {
                    $product->accords()->syncWithoutDetaching($accordData);
                }
            });

            $this->updated++;
        }
    }

    protected function getOrCreateNote(string $name, string $type): ?int
    {
        $name = trim($name);
        if (empty($name) || strlen($name) < 2) return null;

        $key = strtolower($name);

        if (isset($this->noteCache[$key])) {
            return $this->noteCache[$key]['id'];
        }

        $note = Note::firstOrCreate(
            ['name' => $name, 'type' => $type],
            ['name' => $name, 'type' => $type]
        );

        $this->noteCache[$key] = $note->toArray();
        return $note->id;
    }

    protected function getOrCreateAccord(string $name): ?int
    {
        $name = trim($name);
        if (empty($name) || strlen($name) < 2) return null;

        $key = strtolower($name);

        if (isset($this->accordCache[$key])) {
            return $this->accordCache[$key]['id'];
        }

        $accord = Accord::firstOrCreate(
            ['name' => $name],
            ['name' => $name, 'slug' => Str::slug($name)]
        );

        $this->accordCache[$key] = $accord->toArray();
        return $accord->id;
    }
}
