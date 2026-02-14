@props(['model' => null])

@php
    $settings = app(\App\Services\SettingsService::class);
    $seo = $model?->seoMeta ?? null;

    // Meta tags with fallbacks
    $metaTitle = $seo?->meta_title
        ?? ($model ? $model->seoTitle() . ' | ' . $settings->get('store_name', 'AD Perfumes') : null);

    $metaDescription = $seo?->meta_description
        ?? $settings->get('seo_default_description', 'Discover authentic luxury perfumes from world-renowned brands. Free shipping across UAE.');

    $canonicalUrl = $seo?->canonical_url ?? ($model ? $model->seoUrl() : url()->current());
    $robots = $seo?->robots ?? 'index, follow';

    // Open Graph
    $ogTitle = $seo?->og_title ?? $metaTitle ?? $settings->get('seo_default_title', 'AD Perfumes - Luxury Fragrances in UAE');
    $ogDescription = $seo?->og_description ?? $metaDescription;
    $ogType = $seo?->og_type ?? 'website';
    $ogImage = $seo?->og_image ?? ($model && method_exists($model, 'seoImages') && !empty($model->seoImages()) ? $model->seoImages()[0] : null) ?? $settings->get('seo_og_image');
    $ogUrl = $canonicalUrl;

    // Twitter
    $twitterCard = $seo?->twitter_card ?? 'summary_large_image';
    $twitterTitle = $seo?->twitter_title ?? $ogTitle;
    $twitterDescription = $seo?->twitter_description ?? $ogDescription;

    // Schema markup
    $schemas = $seo?->schema_markup ?? [];

    $storeName = $settings->get('store_name', 'AD Perfumes');
@endphp

{{-- Primary Meta Tags --}}
<meta name="robots" content="{{ $robots }}">
<meta name="description" content="{{ Str::limit($metaDescription, 160, '') }}">
@if($seo?->keywords)
    <meta name="keywords" content="{{ implode(', ', array_merge($seo->keywords['primary'] ?? [], $seo->keywords['secondary'] ?? [])) }}">
@endif
<link rel="canonical" href="{{ $canonicalUrl }}">

{{-- Open Graph --}}
<meta property="og:type" content="{{ $ogType }}">
<meta property="og:url" content="{{ $ogUrl }}">
<meta property="og:title" content="{{ Str::limit($ogTitle, 95, '') }}">
<meta property="og:description" content="{{ Str::limit($ogDescription, 200, '') }}">
@if($ogImage)
    <meta property="og:image" content="{{ Str::startsWith($ogImage, 'http') ? $ogImage : asset('storage/' . $ogImage) }}">
@endif
<meta property="og:locale" content="en_AE">
<meta property="og:site_name" content="{{ $storeName }}">

{{-- Twitter Card --}}
<meta name="twitter:card" content="{{ $twitterCard }}">
<meta name="twitter:title" content="{{ Str::limit($twitterTitle, 70, '') }}">
<meta name="twitter:description" content="{{ Str::limit($twitterDescription, 200, '') }}">
@if($ogImage)
    <meta name="twitter:image" content="{{ Str::startsWith($ogImage, 'http') ? $ogImage : asset('storage/' . $ogImage) }}">
@endif

{{-- JSON-LD Structured Data --}}
@if(!empty($schemas))
    @foreach($schemas as $schemaType => $schemaData)
        @if(!empty($schemaData))
            <script type="application/ld+json">{!! json_encode($schemaData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
        @endif
    @endforeach
@endif
