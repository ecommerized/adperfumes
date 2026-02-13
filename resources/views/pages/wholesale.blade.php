@extends('layouts.app')

@section('title', 'Wholesale Program - ' . ($storeName ?? 'AD Perfumes'))
@section('description', 'Join AD Perfumes wholesale program and access luxury fragrances at competitive wholesale prices.')

@section('content')
<!-- Page Header -->
<section class="bg-brand-dark text-white py-20">
    <div class="max-w-6xl mx-auto px-6 lg:px-10 text-center">
        <p class="text-[11px] text-brand-primary uppercase tracking-editorial font-semibold mb-4">Partner with Us</p>
        <h1 class="font-display text-[48px] lg:text-[64px] font-bold mb-6 leading-tight">
            Wholesale Program
        </h1>
        <p class="text-[15px] text-brand-muted max-w-3xl mx-auto leading-relaxed">
            Partner with AD Perfumes and gain access to authentic luxury fragrances at exclusive wholesale prices.
            Perfect for retailers, boutiques, and e-commerce businesses.
        </p>
    </div>
</section>

<!-- Benefits Section -->
<section class="bg-white py-20">
    <div class="max-w-6xl mx-auto px-6 lg:px-10">
        <div class="text-center mb-16">
            <p class="text-[11px] text-brand-primary uppercase tracking-editorial font-semibold mb-3">Why Partner</p>
            <h2 class="font-display text-[32px] lg:text-[40px] font-bold text-brand-dark leading-tight">
                Wholesale Benefits
            </h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach([
                ['icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'title' => 'Competitive Pricing', 'desc' => 'Access exclusive wholesale rates with significant discounts on bulk orders.'],
                ['icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'title' => '100% Authentic', 'desc' => 'All products are guaranteed genuine and sourced from authorized distributors.'],
                ['icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4', 'title' => 'Wide Selection', 'desc' => 'Access to 12,000+ luxury fragrances from over 100 prestigious brands.'],
                ['icon' => 'M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4', 'title' => 'Fast Fulfillment', 'desc' => 'Quick order processing and delivery across UAE. Same-day dispatch before 2 PM.'],
                ['icon' => 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z', 'title' => 'Flexible Payment', 'desc' => 'Multiple payment options including Net 30/60 credit terms for established partners.'],
                ['icon' => 'M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z', 'title' => 'Dedicated Support', 'desc' => 'Personal account manager and priority customer service for all partners.'],
            ] as $benefit)
            <div class="bg-brand-light border border-brand-border p-8 text-center">
                <div class="inline-flex items-center justify-center w-12 h-12 mb-6">
                    <svg class="w-full h-full text-brand-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="{{ $benefit['icon'] }}"/>
                    </svg>
                </div>
                <h3 class="text-[13px] font-bold text-brand-dark mb-3 uppercase tracking-luxury">{{ $benefit['title'] }}</h3>
                <p class="text-[13px] text-brand-gray leading-relaxed">{{ $benefit['desc'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

<!-- Requirements & Form -->
<section class="bg-brand-light py-20" id="wholesale-form">
    <div class="max-w-6xl mx-auto px-6 lg:px-10">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-start">
            <div>
                <h2 class="font-display text-[32px] font-bold text-brand-dark mb-8 leading-tight">
                    Requirements
                </h2>
                <div class="space-y-6">
                    @foreach([
                        ['title' => 'Valid Business License', 'desc' => 'Active trade license issued in the UAE (DED, free zone, or mainland).'],
                        ['title' => 'Minimum Order Value', 'desc' => 'Initial order minimum of AED 5,000. Subsequent orders minimum AED 2,000.'],
                        ['title' => 'Business Type', 'desc' => 'Retail stores, boutiques, e-commerce businesses, hotels, spas, or authorized resellers.'],
                        ['title' => 'VAT Registration', 'desc' => 'Valid TRN (Tax Registration Number) for VAT compliance.'],
                    ] as $req)
                    <div class="flex gap-4">
                        <div class="flex-shrink-0 mt-0.5">
                            <div class="w-6 h-6 bg-brand-primary flex items-center justify-center">
                                <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                        </div>
                        <div>
                            <h3 class="text-[14px] font-bold text-brand-dark mb-1">{{ $req['title'] }}</h3>
                            <p class="text-[13px] text-brand-gray leading-relaxed">{{ $req['desc'] }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>

                <div class="mt-10 p-6 bg-white border-l-2 border-brand-primary">
                    <h3 class="text-[13px] font-bold text-brand-dark mb-3 uppercase tracking-luxury">Pricing Tiers</h3>
                    <ul class="space-y-2 text-[13px] text-brand-gray">
                        <li class="flex gap-2"><span class="text-brand-primary font-semibold">Silver:</span> AED 2,000 - 9,999 (15-20% off retail)</li>
                        <li class="flex gap-2"><span class="text-brand-primary font-semibold">Gold:</span> AED 10,000 - 24,999 (20-30% off retail)</li>
                        <li class="flex gap-2"><span class="text-brand-primary font-semibold">Platinum:</span> AED 25,000+ (30-40% off retail)</li>
                    </ul>
                </div>
            </div>

            <!-- Registration Form -->
            <div class="bg-white border border-brand-border p-8 lg:p-10">
                <h2 class="font-display text-[24px] font-bold text-brand-dark mb-2 leading-tight">
                    Register for Wholesale
                </h2>
                <p class="text-[13px] text-brand-gray mb-8">
                    Our team will contact you within 24-48 hours.
                </p>

                <form method="POST" action="{{ route('wholesale.submit') }}" class="space-y-5">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label for="first_name" class="block text-[11px] font-semibold text-brand-text mb-2 uppercase tracking-luxury">First Name *</label>
                            <input type="text" id="first_name" name="first_name" required
                                   class="w-full px-4 py-3 bg-white border border-brand-border text-[13px] focus:outline-none focus:border-brand-dark transition-colors"
                                   value="{{ old('first_name') }}">
                            @error('first_name') <p class="mt-1 text-[11px] text-brand-sale">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="last_name" class="block text-[11px] font-semibold text-brand-text mb-2 uppercase tracking-luxury">Last Name *</label>
                            <input type="text" id="last_name" name="last_name" required
                                   class="w-full px-4 py-3 bg-white border border-brand-border text-[13px] focus:outline-none focus:border-brand-dark transition-colors"
                                   value="{{ old('last_name') }}">
                            @error('last_name') <p class="mt-1 text-[11px] text-brand-sale">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label for="company_name" class="block text-[11px] font-semibold text-brand-text mb-2 uppercase tracking-luxury">Company Name *</label>
                        <input type="text" id="company_name" name="company_name" required
                               class="w-full px-4 py-3 bg-white border border-brand-border text-[13px] focus:outline-none focus:border-brand-dark transition-colors"
                               value="{{ old('company_name') }}">
                        @error('company_name') <p class="mt-1 text-[11px] text-brand-sale">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="company_address" class="block text-[11px] font-semibold text-brand-text mb-2 uppercase tracking-luxury">Company Address *</label>
                        <textarea id="company_address" name="company_address" rows="3" required
                                  class="w-full px-4 py-3 bg-white border border-brand-border text-[13px] focus:outline-none focus:border-brand-dark transition-colors resize-none">{{ old('company_address') }}</textarea>
                        @error('company_address') <p class="mt-1 text-[11px] text-brand-sale">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="email" class="block text-[11px] font-semibold text-brand-text mb-2 uppercase tracking-luxury">Email Address *</label>
                        <input type="email" id="email" name="email" required
                               class="w-full px-4 py-3 bg-white border border-brand-border text-[13px] focus:outline-none focus:border-brand-dark transition-colors"
                               value="{{ old('email') }}">
                        @error('email') <p class="mt-1 text-[11px] text-brand-sale">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="phone" class="block text-[11px] font-semibold text-brand-text mb-2 uppercase tracking-luxury">Phone Number *</label>
                        <input type="tel" id="phone" name="phone" required
                               class="w-full px-4 py-3 bg-white border border-brand-border text-[13px] focus:outline-none focus:border-brand-dark transition-colors"
                               value="{{ old('phone') }}">
                        @error('phone') <p class="mt-1 text-[11px] text-brand-sale">{{ $message }}</p> @enderror
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="w-full bg-brand-dark text-white py-4 text-[11px] font-semibold uppercase tracking-luxury hover:bg-brand-primary transition-colors duration-300">
                            Submit Application
                        </button>
                    </div>

                    <p class="text-[11px] text-brand-muted text-center">
                        By submitting, you agree to our <a href="{{ route('terms') }}" class="text-brand-primary hover:underline">Terms</a>
                        and <a href="{{ route('privacy-policy') }}" class="text-brand-primary hover:underline">Privacy Policy</a>.
                    </p>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section class="bg-white py-20">
    <div class="max-w-4xl mx-auto px-6 lg:px-10">
        <div class="text-center mb-16">
            <h2 class="font-display text-[32px] lg:text-[40px] font-bold text-brand-dark leading-tight">
                Frequently Asked Questions
            </h2>
        </div>

        <div class="space-y-0">
            @foreach([
                ['q' => 'How long does approval take?', 'a' => 'Our wholesale team reviews applications within 24-48 hours. Once approved, you\'ll receive your wholesale account credentials and access to our full product catalog with pricing.'],
                ['q' => 'What payment methods do you accept?', 'a' => 'We accept bank transfers, credit cards, and cash on delivery. Established partners with good payment history may qualify for Net 30 or Net 60 credit terms.'],
                ['q' => 'Do you offer dropshipping?', 'a' => 'Yes, we offer dropshipping services for qualified wholesale partners. Contact our team to discuss dropshipping arrangements, terms, and integration options.'],
                ['q' => 'Can I return wholesale orders?', 'a' => 'Wholesale orders can be returned within 7 days if products are unused, in original packaging, and due to our error. Returns for other reasons are subject to a 20% restocking fee.'],
                ['q' => 'Do you provide marketing materials?', 'a' => 'Yes, we provide high-quality product images, descriptions, and marketing materials to our wholesale partners. Additional brand assets available upon request.'],
            ] as $faq)
            <div class="border-b border-brand-border py-6">
                <h3 class="text-[14px] font-bold text-brand-dark mb-3">{{ $faq['q'] }}</h3>
                <p class="text-[13px] text-brand-gray leading-relaxed">{{ $faq['a'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="bg-brand-dark text-white py-20">
    <div class="max-w-4xl mx-auto px-6 lg:px-10 text-center">
        <h2 class="font-display text-[32px] lg:text-[40px] font-bold mb-6 leading-tight">
            Ready to Partner with Us?
        </h2>
        <p class="text-[14px] text-brand-muted mb-10 max-w-2xl mx-auto leading-relaxed">
            Join our wholesale program today and start offering authentic luxury fragrances to your customers.
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="#wholesale-form" class="inline-flex items-center justify-center bg-brand-primary text-white text-[11px] font-semibold uppercase tracking-luxury px-10 py-4 hover:bg-brand-primary-soft transition-colors duration-300">
                Apply Now
            </a>
            <a href="{{ route('contact') }}" class="inline-flex items-center justify-center bg-transparent text-white border border-white/30 text-[11px] font-semibold uppercase tracking-luxury px-10 py-4 hover:bg-white hover:text-brand-dark transition-all duration-300">
                Contact Us
            </a>
        </div>
    </div>
</section>
@endsection
