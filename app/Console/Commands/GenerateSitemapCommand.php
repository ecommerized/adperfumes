<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class GenerateSitemapCommand extends Command
{
    protected $signature = 'sitemap:generate';

    protected $description = 'Regenerate and cache the XML sitemap';

    public function handle(): int
    {
        $this->info('Regenerating sitemap...');

        Cache::forget('sitemap_xml');

        // Trigger fresh generation by requesting it
        app(\App\Http\Controllers\SitemapController::class)->index();

        $this->info('Sitemap regenerated and cached.');
        return Command::SUCCESS;
    }
}
