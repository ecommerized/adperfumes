<?php

namespace App\Jobs;

use App\Services\AutoBlogService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateBlogTopicsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 300;

    public function __construct(public int $count = 5)
    {
        $this->onQueue('seo');
    }

    public function handle(AutoBlogService $service): void
    {
        $service->generateTopics($this->count);
    }
}
