<?php

namespace App\Jobs;

use App\Services\SeoAeoService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateSeoAeoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;
    public int $timeout = 180;

    public function __construct(
        public Model $model,
        public bool $force = false,
    ) {
        $this->onQueue('seo');
    }

    public function handle(SeoAeoService $service): void
    {
        $service->generate($this->model, $this->force);
    }
}
