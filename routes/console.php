<?php

use App\Jobs\GenerateAutoSocialPostJob;
use App\Jobs\GenerateBlogTopicsJob;
use App\Jobs\ReoptimizeContentJob;
use App\Jobs\WriteBlogPostJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// SEO & Blog Automation Schedule (UAE timezone)

// Monday 5:00 AM - Generate blog topic ideas for the week
Schedule::job(new GenerateBlogTopicsJob(), 'seo')
    ->weeklyOn(1, '05:00')
    ->timezone('Asia/Dubai')
    ->withoutOverlapping();

// Weekdays 6:00 AM - Write one blog post from pending drafts
Schedule::job(new WriteBlogPostJob(), 'seo')
    ->weekdays()
    ->dailyAt('06:00')
    ->timezone('Asia/Dubai')
    ->withoutOverlapping();

// Daily 8:00 AM - Auto-publish qualifying drafts
Schedule::command('blog:auto --publish')
    ->dailyAt('08:00')
    ->timezone('Asia/Dubai')
    ->withoutOverlapping();

// Daily 9:00 AM - Regenerate XML sitemap
Schedule::command('sitemap:generate')
    ->dailyAt('09:00')
    ->timezone('Asia/Dubai');

// Wednesday 4:00 AM - Re-optimize low-scoring content
Schedule::job(new ReoptimizeContentJob(), 'seo')
    ->weeklyOn(3, '04:00')
    ->timezone('Asia/Dubai')
    ->withoutOverlapping();

// ── Social Media Auto-Posting Schedule ──────────

// Every minute: check for scheduled social posts that are due
Schedule::command('social:auto-post --publish-due')
    ->everyMinute()
    ->timezone('Asia/Dubai')
    ->withoutOverlapping()
    ->runInBackground();

// Daily 7:00 AM - Auto-pilot: generate new social posts
Schedule::job(new GenerateAutoSocialPostJob(), 'seo')
    ->dailyAt('07:00')
    ->timezone('Asia/Dubai')
    ->withoutOverlapping();

// ── Settlement Schedule ──────────

// Daily 2:00 AM - Calculate settlement eligibility for delivered orders
Schedule::command('settlements:calculate-eligibility')
    ->dailyAt('02:00')
    ->timezone('Asia/Dubai')
    ->withoutOverlapping();

// 1st, 8th, 15th, 22nd of each month at 6:00 AM - Process merchant settlements
Schedule::command('settlements:process')
    ->monthlyOn(1, '06:00')
    ->timezone('Asia/Dubai')
    ->withoutOverlapping();

Schedule::command('settlements:process')
    ->monthlyOn(8, '06:00')
    ->timezone('Asia/Dubai')
    ->withoutOverlapping();

Schedule::command('settlements:process')
    ->monthlyOn(15, '06:00')
    ->timezone('Asia/Dubai')
    ->withoutOverlapping();

Schedule::command('settlements:process')
    ->monthlyOn(22, '06:00')
    ->timezone('Asia/Dubai')
    ->withoutOverlapping();
