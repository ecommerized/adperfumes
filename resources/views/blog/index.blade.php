@extends('layouts.app')

@section('title', 'Blog - AD Perfumes | Fragrance Guides & Tips')
@section('description', 'Explore our perfume blog for expert fragrance guides, tips, reviews, and the latest trends in luxury perfumery.')

@section('content')
<div class="max-w-8xl mx-auto px-6 lg:px-10 py-16">
    <!-- Header -->
    <div class="text-center mb-16">
        <h1 class="text-3xl md:text-4xl font-serif font-bold text-brand-dark mb-4">The Fragrance Journal</h1>
        <p class="text-brand-muted text-[14px] max-w-2xl mx-auto">Expert guides, reviews, and insights from the world of luxury perfumery.</p>
    </div>

    @if($posts->count() > 0)
        <!-- Blog Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach($posts as $post)
                <article class="group">
                    <!-- Image -->
                    <a href="{{ route('blog.show', $post->slug) }}" class="block aspect-[16/10] bg-brand-light overflow-hidden mb-5">
                        @if($post->featured_image)
                            <img src="{{ Storage::url($post->featured_image) }}" alt="{{ $post->title }}"
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                        @else
                            <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-brand-light to-gray-100">
                                <svg class="w-12 h-12 text-brand-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"></path>
                                </svg>
                            </div>
                        @endif
                    </a>

                    <!-- Meta -->
                    <div class="mb-3">
                        <time datetime="{{ $post->published_at->toISOString() }}" class="text-[11px] text-brand-muted uppercase tracking-editorial">
                            {{ $post->published_at->format('F d, Y') }}
                        </time>
                    </div>

                    <!-- Title -->
                    <h2 class="text-[18px] font-serif font-semibold text-brand-dark mb-3 leading-snug">
                        <a href="{{ route('blog.show', $post->slug) }}" class="hover:text-brand-primary transition-colors duration-300">
                            {{ $post->title }}
                        </a>
                    </h2>

                    <!-- Excerpt -->
                    @if($post->excerpt)
                        <p class="text-[13px] text-brand-muted leading-relaxed mb-4">
                            {{ Str::limit($post->excerpt, 150) }}
                        </p>
                    @endif

                    <!-- Read More -->
                    <a href="{{ route('blog.show', $post->slug) }}" class="text-[12px] font-semibold text-brand-primary uppercase tracking-luxury hover:text-brand-dark transition-colors duration-300">
                        Read Article &rarr;
                    </a>
                </article>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="mt-16">
            {{ $posts->links() }}
        </div>
    @else
        <div class="text-center py-20">
            <p class="text-brand-muted text-[14px]">No articles published yet. Check back soon!</p>
        </div>
    @endif
</div>
@endsection
