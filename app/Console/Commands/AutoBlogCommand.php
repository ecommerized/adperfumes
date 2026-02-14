<?php

namespace App\Console\Commands;

use App\Jobs\GenerateBlogTopicsJob;
use App\Jobs\WriteBlogPostJob;
use App\Models\BlogPost;
use App\Services\AutoBlogService;
use Illuminate\Console\Command;

class AutoBlogCommand extends Command
{
    protected $signature = 'blog:auto
        {--topics : Generate new blog topics}
        {--write : Write content for the next unwritten draft}
        {--write-all : Write content for all unwritten drafts}
        {--publish : Auto-publish qualifying drafts}';

    protected $description = 'Automated blog content pipeline';

    public function handle(): int
    {
        $acted = false;

        if ($this->option('topics')) {
            $acted = true;
            $count = config('seo.blog.topics_per_week', 5);
            $this->info("Dispatching topic generation ({$count} topics)...");
            GenerateBlogTopicsJob::dispatch($count);
            $this->info('Job dispatched.');
        }

        if ($this->option('write')) {
            $acted = true;
            $this->info('Dispatching blog post writing (next draft)...');
            WriteBlogPostJob::dispatch();
            $this->info('Job dispatched.');
        }

        if ($this->option('write-all')) {
            $acted = true;
            $drafts = BlogPost::where('status', 'draft')->whereNull('content')->count();
            $this->info("Dispatching writing for {$drafts} unwritten drafts...");
            BlogPost::where('status', 'draft')
                ->whereNull('content')
                ->each(function ($post, $index) {
                    WriteBlogPostJob::dispatch($post)->delay(now()->addMinutes($index * 2));
                });
            $this->info('All jobs dispatched.');
        }

        if ($this->option('publish')) {
            $acted = true;
            $service = app(AutoBlogService::class);
            $pending = BlogPost::where('status', 'pending_review')
                ->whereNotNull('content')
                ->get();

            $published = 0;
            foreach ($pending as $post) {
                $service->evaluateForPublishing($post);
                if ($post->fresh()->status === 'published') {
                    $published++;
                }
            }
            $this->info("Auto-published {$published} of {$pending->count()} pending posts.");
        }

        if (!$acted) {
            $this->error('Please specify at least one option: --topics, --write, --write-all, or --publish');
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
