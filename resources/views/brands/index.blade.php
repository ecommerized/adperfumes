@extends('layouts.app')

@section('title', 'All Brands - ' . ($storeName ?? 'AD Perfumes'))

@section('content')
<!-- Page Header -->
<div class="bg-brand-dark text-white">
    <div class="max-w-8xl mx-auto px-6 lg:px-10 py-14">
        <p class="text-[11px] text-brand-primary uppercase tracking-editorial font-semibold mb-3">Our Partners</p>
        <h1 class="font-display text-[40px] lg:text-[52px] font-bold leading-tight mb-3">
            All Brands
        </h1>
        <p class="text-[13px] text-brand-muted">{{ $brands->count() }} premium perfume houses</p>
    </div>
</div>

<!-- Brands Grid -->
<div class="bg-brand-ivory">
    <div class="max-w-8xl mx-auto px-6 lg:px-10 py-12">
        @if($brands->count() > 0)
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                @foreach($brands as $brand)
                    <x-brand-card :brand="$brand" />
                @endforeach
            </div>
        @else
            <div class="text-center py-24">
                <svg class="w-20 h-20 text-brand-border mx-auto mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="0.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
                <h3 class="text-[16px] font-bold text-brand-dark mb-2 uppercase tracking-luxury">No Brands Found</h3>
                <p class="text-[13px] text-brand-gray mb-8">Check back soon for new brands</p>
                <x-button variant="outline" href="{{ route('home') }}">
                    Back to Home
                </x-button>
            </div>
        @endif
    </div>
</div>
@endsection
