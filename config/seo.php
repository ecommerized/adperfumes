<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Anthropic API Configuration
    |--------------------------------------------------------------------------
    */
    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
        'model' => env('ANTHROPIC_MODEL', 'claude-sonnet-4-20250514'),
        'max_tokens' => (int) env('ANTHROPIC_MAX_TOKENS', 4096),
        'api_url' => 'https://api.anthropic.com/v1/messages',
    ],

    /*
    |--------------------------------------------------------------------------
    | Site Information (used in AI prompts)
    |--------------------------------------------------------------------------
    */
    'site' => [
        'name' => env('SEO_SITE_NAME', 'AD Perfumes'),
        'url' => env('APP_URL', 'https://adperfumes.ae'),
        'locale' => 'en_AE',
        'target_country' => 'UAE',
        'currency' => 'AED',
        'industry' => 'Luxury Perfumes & Fragrances',
        'brand_voice' => 'Sophisticated, knowledgeable, welcoming. Expert in fragrances with a luxury feel but accessible tone.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Scoring Thresholds
    |--------------------------------------------------------------------------
    */
    'scoring' => [
        'auto_publish_threshold' => (int) env('SEO_AUTO_PUBLISH_THRESHOLD', 70),
        'reoptimize_below' => (int) env('SEO_REOPTIMIZE_BELOW', 50),
    ],

    /*
    |--------------------------------------------------------------------------
    | Blog Automation
    |--------------------------------------------------------------------------
    */
    'blog' => [
        'topics_per_week' => (int) env('SEO_BLOG_TOPICS_PER_WEEK', 5),
        'posts_per_day' => (int) env('SEO_BLOG_POSTS_PER_DAY', 1),
        'auto_publish' => (bool) env('SEO_BLOG_AUTO_PUBLISH', true),
        'default_author' => 'AD Perfumes',
    ],

    /*
    |--------------------------------------------------------------------------
    | Content Types for SEO Generation
    |--------------------------------------------------------------------------
    */
    'content_types' => [
        'product' => App\Models\Product::class,
        'brand' => App\Models\Brand::class,
        'category' => App\Models\Category::class,
        'blog_post' => App\Models\BlogPost::class,
    ],

];
