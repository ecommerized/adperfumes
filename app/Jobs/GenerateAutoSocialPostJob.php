<?php

namespace App\Jobs;

use App\Services\SettingsService;
use App\Services\SocialMediaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateAutoSocialPostJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 300;

    public function __construct()
    {
        $this->onQueue('seo');
    }

    public function handle(SocialMediaService $service): void
    {
        $settings = app(SettingsService::class);

        if (!$settings->get('social_auto_pilot_enabled', false)) {
            Log::info('GenerateAutoSocialPostJob: Auto-pilot is disabled, skipping.');
            return;
        }

        $post = $service->generateAutoPost();

        if ($post) {
            Log::info("GenerateAutoSocialPostJob: Created auto-post #{$post->id}");
        } else {
            Log::warning('GenerateAutoSocialPostJob: Failed to create auto-post');
        }
    }
}
