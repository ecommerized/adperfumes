<?php

namespace App\Console\Commands;

use App\Jobs\GenerateAutoSocialPostJob;
use App\Jobs\PublishSocialMediaPostJob;
use App\Models\SocialMediaPost;
use Illuminate\Console\Command;

class SocialAutoPostCommand extends Command
{
    protected $signature = 'social:auto-post
        {--generate : Generate a new auto-pilot post}
        {--publish-due : Publish all scheduled posts that are due}
        {--status : Show status of pending/scheduled posts}';

    protected $description = 'Social media auto-posting pipeline';

    public function handle(): int
    {
        $acted = false;

        if ($this->option('generate')) {
            $acted = true;
            $this->info('Dispatching auto social post generation...');
            GenerateAutoSocialPostJob::dispatch();
            $this->info('Job dispatched.');
        }

        if ($this->option('publish-due')) {
            $acted = true;
            $duePosts = SocialMediaPost::readyToPublish()->get();
            $this->info("Found {$duePosts->count()} posts due for publishing.");

            foreach ($duePosts as $index => $post) {
                PublishSocialMediaPostJob::dispatch($post)
                    ->delay(now()->addSeconds($index * 30));
                $this->info("  Dispatched post #{$post->id}: " . mb_substr($post->caption, 0, 50) . '...');
            }
        }

        if ($this->option('status')) {
            $acted = true;
            $this->table(
                ['Status', 'Count'],
                [
                    ['Draft', SocialMediaPost::draft()->count()],
                    ['Scheduled', SocialMediaPost::scheduled()->count()],
                    ['Published', SocialMediaPost::published()->count()],
                    ['Failed', SocialMediaPost::failed()->count()],
                    ['Auto-Pilot', SocialMediaPost::autoPilot()->count()],
                ]
            );
        }

        if (!$acted) {
            $this->error('Please specify at least one option: --generate, --publish-due, or --status');
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
