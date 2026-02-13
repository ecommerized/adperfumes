@extends('layouts.app')

@section('title', 'Contact Us - ' . ($storeName ?? 'AD Perfumes'))
@section('description', 'Get in touch with AD Perfumes. We are here to help with any questions about our luxury fragrances.')

@section('content')
<!-- Page Header -->
<section class="bg-brand-dark text-white py-20">
    <div class="max-w-6xl mx-auto px-6 lg:px-10 text-center">
        <p class="text-[11px] text-brand-primary uppercase tracking-editorial font-semibold mb-4">Get in Touch</p>
        <h1 class="font-display text-[48px] lg:text-[60px] font-bold mb-4 leading-tight">
            Contact Us
        </h1>
        <p class="text-[15px] text-brand-muted max-w-3xl mx-auto leading-relaxed">
            We're here to help. Reach out with any questions or concerns.
        </p>
    </div>
</section>

<!-- Contact Section -->
<section class="bg-brand-ivory py-20">
    <div class="max-w-6xl mx-auto px-6 lg:px-10">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-16">
            <!-- Contact Information -->
            <div>
                <h2 class="font-display text-[28px] lg:text-[36px] font-bold text-brand-dark mb-6 leading-tight">
                    Get In Touch
                </h2>
                <p class="text-[14px] text-brand-gray mb-10 leading-relaxed">
                    Have a question about our fragrances? Need help with an order? Our team is ready to assist you.
                </p>

                <div class="space-y-6">
                    @foreach([
                        ['icon' => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z', 'title' => 'Email', 'content' => 'info@adperfumes.ae', 'href' => 'mailto:info@adperfumes.ae'],
                        ['icon' => 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z', 'title' => 'Phone', 'content' => '+971 50 123 4567', 'href' => 'tel:+971501234567'],
                    ] as $info)
                    <div class="flex gap-4">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 bg-brand-light flex items-center justify-center">
                                <svg class="w-5 h-5 text-brand-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="{{ $info['icon'] }}"/>
                                </svg>
                            </div>
                        </div>
                        <div>
                            <h3 class="text-[11px] font-bold text-brand-dark mb-1 uppercase tracking-luxury">{{ $info['title'] }}</h3>
                            <a href="{{ $info['href'] }}" class="text-[14px] text-brand-gray hover:text-brand-primary transition-colors">
                                {{ $info['content'] }}
                            </a>
                        </div>
                    </div>
                    @endforeach

                    <div class="flex gap-4">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 bg-brand-light flex items-center justify-center">
                                <svg class="w-5 h-5 text-brand-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </div>
                        <div>
                            <h3 class="text-[11px] font-bold text-brand-dark mb-1 uppercase tracking-luxury">Business Hours</h3>
                            <p class="text-[14px] text-brand-gray">Saturday - Thursday: 9:00 AM - 6:00 PM</p>
                            <p class="text-[14px] text-brand-gray">Friday: Closed</p>
                        </div>
                    </div>

                    <div class="flex gap-4">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 bg-brand-light flex items-center justify-center">
                                <svg class="w-5 h-5 text-brand-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                        </div>
                        <div>
                            <h3 class="text-[11px] font-bold text-brand-dark mb-1 uppercase tracking-luxury">Location</h3>
                            <p class="text-[14px] text-brand-gray">United Arab Emirates</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Form -->
            <div class="bg-white border border-brand-border p-8 lg:p-10">
                <h2 class="font-display text-[24px] font-bold text-brand-dark mb-6 leading-tight">
                    Send Us A Message
                </h2>

                <form method="POST" action="{{ route('contact.submit') }}" class="space-y-5">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label for="first_name" class="block text-[11px] font-semibold text-brand-text mb-2 uppercase tracking-luxury">First Name *</label>
                            <input type="text" id="first_name" name="first_name" required
                                   class="w-full px-4 py-3 bg-white border border-brand-border text-[13px] focus:outline-none focus:border-brand-dark transition-colors"
                                   value="{{ old('first_name') }}">
                            @error('first_name')
                                <p class="mt-1 text-[11px] text-brand-sale">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="last_name" class="block text-[11px] font-semibold text-brand-text mb-2 uppercase tracking-luxury">Last Name *</label>
                            <input type="text" id="last_name" name="last_name" required
                                   class="w-full px-4 py-3 bg-white border border-brand-border text-[13px] focus:outline-none focus:border-brand-dark transition-colors"
                                   value="{{ old('last_name') }}">
                            @error('last_name')
                                <p class="mt-1 text-[11px] text-brand-sale">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label for="email" class="block text-[11px] font-semibold text-brand-text mb-2 uppercase tracking-luxury">Email *</label>
                        <input type="email" id="email" name="email" required
                               class="w-full px-4 py-3 bg-white border border-brand-border text-[13px] focus:outline-none focus:border-brand-dark transition-colors"
                               value="{{ old('email') }}">
                        @error('email')
                            <p class="mt-1 text-[11px] text-brand-sale">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="phone" class="block text-[11px] font-semibold text-brand-text mb-2 uppercase tracking-luxury">Phone Number</label>
                        <input type="tel" id="phone" name="phone"
                               class="w-full px-4 py-3 bg-white border border-brand-border text-[13px] focus:outline-none focus:border-brand-dark transition-colors"
                               value="{{ old('phone') }}">
                    </div>

                    <div>
                        <label for="message" class="block text-[11px] font-semibold text-brand-text mb-2 uppercase tracking-luxury">Message *</label>
                        <textarea id="message" name="message" rows="5" required
                                  class="w-full px-4 py-3 bg-white border border-brand-border text-[13px] focus:outline-none focus:border-brand-dark transition-colors resize-none">{{ old('message') }}</textarea>
                        @error('message')
                            <p class="mt-1 text-[11px] text-brand-sale">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" class="w-full bg-brand-dark text-white py-4 text-[11px] font-semibold uppercase tracking-luxury hover:bg-brand-primary transition-colors duration-300">
                        Send Message
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection
