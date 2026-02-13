<?php

namespace App\Console\Commands;

use App\Models\Accord;
use App\Models\Note;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ParseFragranceNotes extends Command
{
    protected $signature = 'products:parse-notes {--limit=0 : Max products to process} {--force : Re-parse even if product already has notes}';
    protected $description = 'Parse fragrance notes and accords from product descriptions';

    protected $updated = 0;
    protected $skipped = 0;
    protected $noteCache = []; // name => id
    protected $accordCache = []; // name => id

    // Common accord keywords found in descriptions
    protected $accordKeywords = [
        'woody' => 'Woody', 'wood' => 'Woody',
        'floral' => 'Floral', 'flower' => 'Floral',
        'citrus' => 'Citrus', 'citrusy' => 'Citrus',
        'oriental' => 'Oriental', 'eastern' => 'Oriental',
        'spicy' => 'Spicy', 'spice' => 'Spicy',
        'fresh' => 'Fresh', 'freshness' => 'Fresh',
        'sweet' => 'Sweet', 'sweetness' => 'Sweet',
        'musky' => 'Musky', 'musk' => 'Musky',
        'amber' => 'Amber', 'ambery' => 'Amber',
        'aquatic' => 'Aquatic', 'marine' => 'Aquatic', 'oceanic' => 'Aquatic',
        'gourmand' => 'Gourmand',
        'aromatic' => 'Aromatic',
        'fruity' => 'Fruity', 'fruit' => 'Fruity',
        'powdery' => 'Powdery', 'powder' => 'Powdery',
        'leather' => 'Leathery', 'leathery' => 'Leathery',
        'smoky' => 'Smoky', 'smoke' => 'Smoky',
        'green' => 'Green', 'herbal' => 'Green',
        'balsamic' => 'Balsamic',
        'earthy' => 'Earthy',
        'oud' => 'Oud', 'aoud' => 'Oud',
        'tobacco' => 'Tobacco',
        'vanilla' => 'Vanilla',
        'chypre' => 'Chypre',
        'fougere' => 'Fougere', 'fougère' => 'Fougere',
        'warm spicy' => 'Warm Spicy',
        'aldehydic' => 'Aldehydic',
    ];

    public function handle()
    {
        ini_set('memory_limit', '512M');

        // Preload caches
        $this->noteCache = Note::all()->mapWithKeys(fn($n) => [strtolower($n->name) => $n->id])->toArray();
        $this->accordCache = Accord::all()->mapWithKeys(fn($a) => [strtolower($a->name) => $a->id])->toArray();

        $query = Product::whereNotNull('description')
            ->where('description', '!=', '')
            ->where(function ($q) {
                $q->where('description', 'LIKE', '%top note%')
                    ->orWhere('description', 'LIKE', '%Top Note%')
                    ->orWhere('description', 'LIKE', '%middle note%')
                    ->orWhere('description', 'LIKE', '%Middle Note%')
                    ->orWhere('description', 'LIKE', '%heart note%')
                    ->orWhere('description', 'LIKE', '%Heart Note%')
                    ->orWhere('description', 'LIKE', '%base note%')
                    ->orWhere('description', 'LIKE', '%Base Note%');
            });

        if (!$this->option('force')) {
            $query->whereDoesntHave('notes');
        }

        $limit = (int) $this->option('limit');
        if ($limit > 0) {
            $query->limit($limit);
        }

        $products = $query->get();
        $total = $products->count();

        $this->info("Parsing notes from {$total} product descriptions...");
        $bar = $this->output->createProgressBar($total);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% | Updated: %message%');
        $bar->setMessage('0');

        foreach ($products as $product) {
            $this->parseProductDescription($product);
            $bar->setMessage((string) $this->updated);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->table(['Status', 'Count'], [
            ['Updated with notes', $this->updated],
            ['Skipped (no parseable notes)', $this->skipped],
        ]);

        $this->info('Total notes in DB: ' . Note::count());
        $this->info('Total accords in DB: ' . Accord::count());
        $this->info('Products with notes: ' . Product::whereHas('notes')->count());
        $this->info('Products with accords: ' . Product::whereHas('accords')->count());

        return 0;
    }

    protected function parseProductDescription(Product $product): void
    {
        $desc = $product->description;
        $noteData = []; // noteId => ['type' => ...]
        $accordData = [];

        // Parse top notes
        $topNotes = $this->extractNotesFromText($desc, 'top');
        foreach ($topNotes as $noteName) {
            $noteId = $this->getOrCreateNote($noteName);
            if ($noteId) $noteData[$noteId] = ['type' => 'top'];
        }

        // Parse middle/heart notes
        $middleNotes = $this->extractNotesFromText($desc, 'middle');
        foreach ($middleNotes as $noteName) {
            $noteId = $this->getOrCreateNote($noteName);
            if ($noteId) $noteData[$noteId] = ['type' => 'middle'];
        }

        // Parse base notes
        $baseNotes = $this->extractNotesFromText($desc, 'base');
        foreach ($baseNotes as $noteName) {
            $noteId = $this->getOrCreateNote($noteName);
            if ($noteId) $noteData[$noteId] = ['type' => 'base'];
        }

        // Extract accords from description
        $descLower = strtolower($desc);
        foreach ($this->accordKeywords as $keyword => $accordName) {
            if (strpos($descLower, $keyword) !== false) {
                $accordId = $this->getOrCreateAccord($accordName);
                if ($accordId && !isset($accordData[$accordId])) {
                    $accordData[$accordId] = ['percentage' => null];
                }
            }
        }

        if (!empty($noteData) || !empty($accordData)) {
            DB::transaction(function () use ($product, $noteData, $accordData) {
                if (!empty($noteData)) {
                    $product->notes()->syncWithoutDetaching($noteData);
                }
                if (!empty($accordData)) {
                    $product->accords()->syncWithoutDetaching($accordData);
                }
            });
            $this->updated++;
        } else {
            $this->skipped++;
        }
    }

    protected function extractNotesFromText(string $text, string $type): array
    {
        $notes = [];

        // Multiple regex patterns to handle various description formats
        $patterns = [];

        if ($type === 'top') {
            $patterns = [
                // "Top notes are X, Y and Z"
                '/top\s+notes?\s+(?:are|is|include|:)\s*([^.;]+?)(?:\.|;|$|(?:middle|heart|base)\s+note)/is',
                // "Top: X, Y, Z"
                '/top\s*:\s*([^.;]+?)(?:\.|;|$|(?:middle|heart|base))/is',
                // "Top Note - X, Y"
                '/top\s+notes?\s*[-–]\s*([^.;]+?)(?:\.|;|$|(?:middle|heart|base)\s+note)/is',
            ];
        } elseif ($type === 'middle') {
            $patterns = [
                '/(?:middle|heart)\s+notes?\s+(?:are|is|include|:)\s*([^.;]+?)(?:\.|;|$|base\s+note)/is',
                '/(?:middle|heart)\s*:\s*([^.;]+?)(?:\.|;|$|base)/is',
                '/(?:middle|heart)\s+notes?\s*[-–]\s*([^.;]+?)(?:\.|;|$|base\s+note)/is',
            ];
        } elseif ($type === 'base') {
            $patterns = [
                '/base\s+notes?\s+(?:are|is|include|:)\s*([^.;]+?)(?:\.|;|$)/is',
                '/base\s*:\s*([^.;]+?)(?:\.|;|$)/is',
                '/base\s+notes?\s*[-–]\s*([^.;]+?)(?:\.|;|$)/is',
            ];
        }

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $m)) {
                $noteStr = $m[1];
                $notes = $this->splitNoteString($noteStr);
                if (!empty($notes)) break;
            }
        }

        return $notes;
    }

    protected function splitNoteString(string $noteStr): array
    {
        // Clean up
        $noteStr = trim($noteStr);
        $noteStr = preg_replace('/\s+/', ' ', $noteStr);

        // Split by comma, "and", "&", or semicolon
        $parts = preg_split('/\s*[,;]\s*|\s+and\s+|\s*&\s*/', $noteStr);

        $notes = [];
        foreach ($parts as $part) {
            $part = trim($part);
            // Remove common prefixes/suffixes
            $part = preg_replace('/^(a |the |with |some |hint of |touch of |notes? of )/i', '', $part);
            $part = preg_replace('/\s*(note|accord|essence|extract|absolute|oil)s?\s*$/i', '', $part);
            $part = trim($part);

            // Validate - must be 2-40 chars, no numbers, no weird chars
            if (strlen($part) >= 2 && strlen($part) <= 40 && !preg_match('/\d/', $part) && preg_match('/^[\p{L}\s\'-]+$/u', $part)) {
                $notes[] = ucwords(strtolower($part));
            }
        }

        return array_unique($notes);
    }

    protected function getOrCreateNote(string $name): ?int
    {
        $name = trim($name);
        if (empty($name) || strlen($name) < 2) return null;

        $key = strtolower($name);

        if (isset($this->noteCache[$key])) {
            return $this->noteCache[$key];
        }

        $note = Note::firstOrCreate(['name' => $name]);

        $this->noteCache[$key] = $note->id;
        return $note->id;
    }

    protected function getOrCreateAccord(string $name): ?int
    {
        $name = trim($name);
        if (empty($name) || strlen($name) < 2) return null;

        $key = strtolower($name);

        if (isset($this->accordCache[$key])) {
            return $this->accordCache[$key];
        }

        $accord = Accord::firstOrCreate(
            ['name' => $name],
            ['name' => $name, 'slug' => Str::slug($name)]
        );

        $this->accordCache[$key] = $accord->id;
        return $accord->id;
    }
}
