@extends('layouts.app')

@section('title', ($page->meta_title ?: $page->title) . ' - AD Perfumes')
@section('description', $page->meta_description ?: 'AD Perfumes - ' . $page->title)

@section('content')
<!-- Page Header -->
<section class="bg-brand-dark text-white py-20">
    <div class="max-w-4xl mx-auto px-6 lg:px-10 text-center">
        @if($page->subtitle)
            <p class="text-[11px] text-brand-primary uppercase tracking-editorial font-semibold mb-4">{{ $page->subtitle }}</p>
        @endif
        <h1 class="font-display text-[48px] lg:text-[60px] font-bold mb-4 leading-tight">
            {{ $page->title }}
        </h1>
        <p class="text-[14px] text-brand-muted">
            Last updated: {{ $page->updated_at->format('F d, Y') }}
        </p>
    </div>
</section>

<!-- Page Content -->
<section class="bg-brand-ivory py-20">
    <div class="max-w-4xl mx-auto px-6 lg:px-10">
        <div class="prose prose-sm max-w-none
            prose-headings:text-brand-dark prose-headings:uppercase prose-headings:tracking-luxury prose-headings:font-bold
            prose-h2:text-[24px] prose-h2:mb-4 prose-h2:mt-8
            prose-h3:text-[20px] prose-h3:mb-3 prose-h3:mt-6
            prose-p:text-[15px] prose-p:text-brand-gray prose-p:leading-relaxed
            prose-li:text-[15px] prose-li:text-brand-gray
            prose-a:text-brand-primary hover:prose-a:underline
            prose-strong:text-brand-dark
            prose-ul:list-disc prose-ul:ml-4
            prose-ol:list-decimal prose-ol:ml-4">
            {!! $page->content !!}
        </div>
    </div>
</section>
@endsection
