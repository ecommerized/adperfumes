@extends('layouts.app')

@section('title', $post->seoMeta?->meta_title ?? $post->title . ' - AD Perfumes Blog')

@section('content')
<article class="max-w-4xl mx-auto px-6 lg:px-10 py-16">
    <!-- Breadcrumb -->
    <nav class="mb-8">
        <ol class="flex items-center gap-2 text-[12px] text-brand-muted">
            <li><a href="{{ route('home') }}" class="hover:text-brand-primary transition-colors">Home</a></li>
            <li>&rsaquo;</li>
            <li><a href="{{ route('blog.index') }}" class="hover:text-brand-primary transition-colors">Blog</a></li>
            <li>&rsaquo;</li>
            <li class="text-brand-dark">{{ Str::limit($post->title, 50) }}</li>
        </ol>
    </nav>

    <!-- Header -->
    <header class="mb-10">
        <h1 class="text-3xl md:text-4xl font-serif font-bold text-brand-dark mb-4 leading-tight">{{ $post->title }}</h1>
        <div class="flex items-center gap-4 text-[12px] text-brand-muted">
            <span>By {{ $post->author }}</span>
            <span>&bull;</span>
            <time datetime="{{ $post->published_at->toISOString() }}">{{ $post->published_at->format('F d, Y') }}</time>
            @if($post->meta_data['word_count'] ?? false)
                <span>&bull;</span>
                <span>{{ ceil(($post->meta_data['word_count']) / 200) }} min read</span>
            @endif
        </div>
    </header>

    <!-- Featured Image -->
    @if($post->featured_image)
        <div class="aspect-[16/9] bg-brand-light overflow-hidden mb-10">
            <img src="{{ Storage::url($post->featured_image) }}" alt="{{ $post->title }}" class="w-full h-full object-cover">
        </div>
    @endif

    <!-- Content -->
    <div class="prose prose-lg max-w-none
        prose-headings:font-serif prose-headings:text-brand-dark
        prose-h2:text-2xl prose-h2:mt-10 prose-h2:mb-4
        prose-h3:text-xl prose-h3:mt-8 prose-h3:mb-3
        prose-p:text-[14px] prose-p:leading-relaxed prose-p:text-brand-text prose-p:mb-4
        prose-a:text-brand-primary prose-a:no-underline hover:prose-a:underline
        prose-strong:text-brand-dark
        prose-ul:text-[14px] prose-ol:text-[14px]
        prose-li:text-brand-text prose-li:leading-relaxed
        prose-blockquote:border-brand-primary prose-blockquote:text-brand-muted prose-blockquote:italic">
        {!! $post->content !!}
    </div>

    <!-- Share -->
    <div class="mt-12 pt-8 border-t border-brand-border">
        <p class="text-[11px] uppercase tracking-editorial text-brand-muted mb-4">Share this article</p>
        <div class="flex gap-4">
            <a href="https://twitter.com/intent/tweet?url={{ urlencode(url()->current()) }}&text={{ urlencode($post->title) }}" target="_blank" rel="noopener"
               class="text-brand-muted hover:text-brand-primary transition-colors">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/></svg>
            </a>
            <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(url()->current()) }}" target="_blank" rel="noopener"
               class="text-brand-muted hover:text-brand-primary transition-colors">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
            </a>
            <a href="https://www.linkedin.com/shareArticle?mini=true&url={{ urlencode(url()->current()) }}&title={{ urlencode($post->title) }}" target="_blank" rel="noopener"
               class="text-brand-muted hover:text-brand-primary transition-colors">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
            </a>
        </div>
    </div>
</article>

<!-- Related Posts -->
@if($relatedPosts->count() > 0)
    <section class="bg-brand-light py-16 mt-8">
        <div class="max-w-8xl mx-auto px-6 lg:px-10">
            <h2 class="text-[11px] uppercase tracking-editorial text-brand-muted mb-8 text-center">You May Also Like</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                @foreach($relatedPosts as $related)
                    <article class="group">
                        <a href="{{ route('blog.show', $related->slug) }}" class="block aspect-[16/10] bg-white overflow-hidden mb-4">
                            @if($related->featured_image)
                                <img src="{{ Storage::url($related->featured_image) }}" alt="{{ $related->title }}"
                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                            @else
                                <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-white to-gray-50">
                                    <svg class="w-10 h-10 text-brand-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"></path>
                                    </svg>
                                </div>
                            @endif
                        </a>
                        <h3 class="text-[16px] font-serif font-semibold text-brand-dark leading-snug">
                            <a href="{{ route('blog.show', $related->slug) }}" class="hover:text-brand-primary transition-colors duration-300">
                                {{ $related->title }}
                            </a>
                        </h3>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
@endif
@endsection
