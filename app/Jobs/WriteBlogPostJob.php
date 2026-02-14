<?php

namespace App\Jobs;

use App\Models\BlogPost;
use App\Services\AutoBlogService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class WriteBlogPostJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 600;

    public function __construct(public ?BlogPost $post = null)
    {
        $this->onQueue('seo');
    }

    public function handle(AutoBlogService $service): void
    {
        if ($this->post) {
            $service->writePost($this->post);
        } else {
            $service->writeNextDraft();
        }
    }
}
