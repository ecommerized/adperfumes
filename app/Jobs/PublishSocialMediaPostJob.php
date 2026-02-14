<?php

namespace App\Jobs;

use App\Models\SocialMediaPost;
use App\Services\FacebookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PublishSocialMediaPostJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;
    public int $timeout = 120;

    public function __construct(public SocialMediaPost $post)
    {
        $this->onQueue('seo');
    }

    public function handle(FacebookService $facebookService): void
    {
        if (!in_array($this->post->status, ['scheduled', 'draft', 'failed'])) {
            Log::info("PublishSocialMediaPostJob: Skipping post #{$this->post->id} (status: {$this->post->status})");
            return;
        }

        $this->post->markAsPosting();

        $caption = $this->post->full_caption;
        $result = $facebookService->postToPage($caption, $this->post->image_path);

        if ($result['success']) {
            $this->post->markAsPublished($result['post_id']);
            Log::info("PublishSocialMediaPostJob: Post #{$this->post->id} published", [
                'facebook_post_id' => $result['post_id'],
            ]);
        } else {
            $this->post->markAsFailed($result['error']);
            Log::error("PublishSocialMediaPostJob: Post #{$this->post->id} failed", [
                'error' => $result['error'],
            ]);
        }
    }
}
