@extends('layouts.app')

@section('title', 'About Us - ' . ($storeName ?? 'AD Perfumes'))
@section('description', 'Learn about AD Perfumes, your trusted destination for authentic luxury fragrances in the UAE.')

@section('content')
<!-- Page Header -->
<section class="bg-brand-dark text-white py-20">
    <div class="max-w-6xl mx-auto px-6 lg:px-10 text-center">
        <p class="text-[11px] text-brand-primary uppercase tracking-editorial font-semibold mb-4">Our Story</p>
        <h1 class="font-display text-[48px] lg:text-[60px] font-bold mb-4 leading-tight">
            About AD Perfumes
        </h1>
        <p class="text-[15px] text-brand-muted max-w-3xl mx-auto leading-relaxed">
            Your trusted destination for authentic luxury fragrances in the UAE
        </p>
    </div>
</section>

<!-- Our Story -->
<section class="bg-white py-20">
    <div class="max-w-6xl mx-auto px-6 lg:px-10">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
            <div>
                <h2 class="font-display text-[32px] lg:text-[40px] font-bold text-brand-dark mb-6 leading-tight">
                    The Art of<br>Curation
                </h2>
                <div class="space-y-4 text-[14px] text-brand-gray leading-relaxed">
                    <p>
                        AD Perfumes was founded with a singular vision: to bring the world's finest luxury fragrances
                        to discerning customers across the United Arab Emirates. We believe that a signature scent is
                        more than just a fragrance — it's an expression of identity, confidence, and personal style.
                    </p>
                    <p>
                        Our carefully curated collection features authentic perfumes from the most prestigious perfume
                        houses globally, from timeless classics to contemporary masterpieces. Every bottle we offer is
                        guaranteed to be 100% genuine, sourced directly from authorized distributors.
                    </p>
                    <p>
                        What sets us apart is our commitment to authenticity, exceptional customer service, and deep
                        understanding of fragrance artistry. Our team of fragrance experts is passionate about helping
                        you discover scents that resonate with your personality and occasion.
                    </p>
                </div>
            </div>

            <div class="relative">
                <div class="aspect-[4/5] bg-brand-light flex items-center justify-center">
                    <svg class="w-24 h-24 text-brand-border" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="0.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Our Values -->
<section class="bg-brand-light py-20">
    <div class="max-w-6xl mx-auto px-6 lg:px-10">
        <div class="text-center mb-16">
            <p class="text-[11px] text-brand-primary uppercase tracking-editorial font-semibold mb-3">Our Principles</p>
            <h2 class="font-display text-[32px] lg:text-[40px] font-bold text-brand-dark leading-tight">
                Our Values
            </h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white border border-brand-border p-8 text-center">
                <div class="inline-flex items-center justify-center w-12 h-12 mb-6">
                    <svg class="w-full h-full text-brand-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="text-[13px] font-bold text-brand-dark mb-3 uppercase tracking-luxury">100% Authenticity</h3>
                <p class="text-[13px] text-brand-gray leading-relaxed">
                    Every fragrance is guaranteed genuine and sourced from authorized distributors.
                </p>
            </div>

            <div class="bg-white border border-brand-border p-8 text-center">
                <div class="inline-flex items-center justify-center w-12 h-12 mb-6">
                    <svg class="w-full h-full text-brand-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
                <h3 class="text-[13px] font-bold text-brand-dark mb-3 uppercase tracking-luxury">Expert Guidance</h3>
                <p class="text-[13px] text-brand-gray leading-relaxed">
                    Our fragrance specialists help you discover your perfect scent with personalized recommendations.
                </p>
            </div>

            <div class="bg-white border border-brand-border p-8 text-center">
                <div class="inline-flex items-center justify-center w-12 h-12 mb-6">
                    <svg class="w-full h-full text-brand-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                    </svg>
                </div>
                <h3 class="text-[13px] font-bold text-brand-dark mb-3 uppercase tracking-luxury">Luxury Experience</h3>
                <p class="text-[13px] text-brand-gray leading-relaxed">
                    From browsing to delivery, a premium experience matching the quality of our fragrances.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Why Choose Us -->
<section class="bg-white py-20">
    <div class="max-w-6xl mx-auto px-6 lg:px-10">
        <div class="text-center mb-16">
            <h2 class="font-display text-[32px] lg:text-[40px] font-bold text-brand-dark leading-tight">
                Why Choose Us
            </h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-4xl mx-auto">
            @foreach([
                ['title' => 'Extensive Collection', 'desc' => 'Access to thousands of authentic fragrances from top international brands.'],
                ['title' => 'Competitive Pricing', 'desc' => 'Luxury fragrances at prices that offer exceptional value without compromise.'],
                ['title' => 'Fast UAE Delivery', 'desc' => 'Express shipping across all Emirates with tracking and secure packaging.'],
                ['title' => 'Easy Returns', 'desc' => 'Hassle-free returns and exchanges within 14 days for your peace of mind.'],
            ] as $feature)
            <div class="flex gap-4">
                <div class="flex-shrink-0 mt-0.5">
                    <div class="w-6 h-6 bg-brand-primary flex items-center justify-center">
                        <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                </div>
                <div>
                    <h3 class="text-[14px] font-bold text-brand-dark mb-1">{{ $feature['title'] }}</h3>
                    <p class="text-[13px] text-brand-gray leading-relaxed">{{ $feature['desc'] }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="bg-brand-dark text-white py-20">
    <div class="max-w-4xl mx-auto px-6 lg:px-10 text-center">
        <h2 class="font-display text-[32px] lg:text-[40px] font-bold mb-6 leading-tight">
            Start Your Fragrance Journey
        </h2>
        <p class="text-[14px] text-brand-muted mb-10 max-w-2xl mx-auto leading-relaxed">
            Explore our curated collection of luxury fragrances and discover your signature scent today.
        </p>
        <a href="{{ route('products.index') }}" class="inline-flex items-center justify-center bg-brand-primary text-white text-[11px] font-semibold uppercase tracking-luxury px-10 py-4 hover:bg-brand-primary-soft transition-colors duration-300">
            Shop Our Collection
        </a>
    </div>
</section>
@endsection
