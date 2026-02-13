<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class CatalogFeeds extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-rss';

    protected static ?string $navigationGroup = 'Marketing';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Catalog Feeds';

    protected static string $view = 'filament.pages.catalog-feeds';

    public function getGoogleFeedUrl(): string
    {
        return route('feed.google');
    }

    public function getMetaFeedUrl(): string
    {
        return route('feed.meta');
    }

    public function getTiktokFeedUrl(): string
    {
        return route('feed.tiktok');
    }

    public function getSnapchatFeedUrl(): string
    {
        return route('feed.snapchat');
    }
}
