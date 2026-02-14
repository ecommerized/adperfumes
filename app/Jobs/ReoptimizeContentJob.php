<?php

namespace App\Jobs;

use App\Models\SeoMeta;
use App\Services\SeoAeoService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ReoptimizeContentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 600;

    public function __construct()
    {
        $this->onQueue('seo');
    }

    public function handle(SeoAeoService $service): void
    {
        $threshold = config('seo.scoring.reoptimize_below', 50);

        $lowScoring = SeoMeta::where('is_manually_edited', false)
            ->whereNotNull('scoring')
            ->get()
            ->filter(fn ($meta) => ($meta->scoring['overall_score'] ?? 100) < $threshold)
            ->take(10);

        foreach ($lowScoring as $meta) {
            $model = $meta->seoable;
            if ($model) {
                $service->generate($model, force: true);
            }
        }
    }
}
