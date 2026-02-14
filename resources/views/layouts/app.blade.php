<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', ($seoModel ?? null)?->seoMeta?->meta_title ?? (($storeName ?? 'AD Perfumes') . ' - Luxury Fragrances in UAE'))</title>
    @if(empty($seoModel))
        <meta name="description" content="@yield('description', 'Discover authentic luxury perfumes from world-renowned brands. Free shipping across UAE.')">
    @endif
    <x-seo-aeo-head :model="$seoModel ?? null" />
    @if(!empty($googleSiteVerification))
        <meta name="google-site-verification" content="{{ $googleSiteVerification }}">
    @endif

    @if(!empty($storeFavicon))
        <link rel="icon" type="image/png" href="{{ $storeFavicon }}">
        <link rel="apple-touch-icon" href="{{ $storeFavicon }}">
    @endif

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Instrument+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        [x-cloak] { display: none !important; }
        body {
            font-family: 'Instrument Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            font-size: 14px;
            line-height: 1.7;
            color: #2D2D2D;
        }
    </style>

    @include('partials.tracking-pixels')
</head>
<body class="bg-brand-ivory text-brand-text antialiased">
    @if(!empty($pixelGtm))
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $pixelGtm }}" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    @endif
    <!-- Announcement Bar -->
    <div class="bg-brand-dark text-white text-center py-2.5 text-[11px] uppercase tracking-editorial">
        <p>Complimentary Shipping on Orders Over AED 300</p>
    </div>

    <!-- Header -->
    <header class="sticky top-0 z-50 bg-white border-b border-brand-border" x-data="{ mobileMenu: false }">
        <nav class="max-w-8xl mx-auto px-6 lg:px-10">
            <div class="flex justify-between items-center h-20">
                <!-- Logo -->
                <div class="flex-shrink-0">
                    <a href="{{ route('home') }}" class="flex items-center">
                        @if(!empty($storeLogo))
                            <img src="{{ $storeLogo }}" alt="{{ $storeName ?? 'AD Perfumes' }}" class="h-14 w-auto">
                        @else
                            <span class="text-[26px] font-bold tracking-tight uppercase">
                                <span class="text-brand-dark">AD</span><span class="text-brand-primary ml-1">PERFUMES</span>
                            </span>
                        @endif
                    </a>
                </div>

                <!-- Desktop Navigation -->
                <div class="hidden lg:flex items-center gap-x-8">
                    <a href="{{ route('home') }}" class="text-[13px] text-brand-text hover:text-brand-primary transition-colors duration-300 font-medium uppercase tracking-wide {{ request()->routeIs('home') ? 'text-brand-primary' : '' }}">
                        Home
                    </a>
                    <a href="{{ route('products.index') }}" class="text-[13px] text-brand-text hover:text-brand-primary transition-colors duration-300 font-medium uppercase tracking-wide {{ request()->routeIs('products.*') ? 'text-brand-primary' : '' }}">
                        Shop All
                    </a>
                    <a href="{{ route('brands.index') }}" class="text-[13px] text-brand-text hover:text-brand-primary transition-colors duration-300 font-medium uppercase tracking-wide {{ request()->routeIs('brands.*') ? 'text-brand-primary' : '' }}">
                        Brands
                    </a>
                    <a href="{{ route('flash-sale') }}" class="text-[13px] text-brand-text hover:text-brand-sale transition-colors duration-300 font-medium uppercase tracking-wide {{ request()->routeIs('flash-sale') ? 'text-brand-sale' : '' }}">
                        Flash Sale
                    </a>
                    <a href="{{ route('gift-cards') }}" class="text-[13px] text-brand-text hover:text-brand-primary transition-colors duration-300 font-medium uppercase tracking-wide {{ request()->routeIs('gift-cards') ? 'text-brand-primary' : '' }}">
                        Gift Cards
                    </a>
                    <a href="{{ route('wholesale') }}" class="text-[13px] text-brand-text hover:text-brand-primary transition-colors duration-300 font-medium uppercase tracking-wide {{ request()->routeIs('wholesale') ? 'text-brand-primary' : '' }}">
                        Wholesale
                    </a>
                    <a href="{{ route('contact') }}" class="text-[13px] text-brand-text hover:text-brand-primary transition-colors duration-300 font-medium uppercase tracking-wide {{ request()->routeIs('contact') ? 'text-brand-primary' : '' }}">
                        Contact
                    </a>
                </div>

                <!-- Right Side -->
                <div class="flex items-center gap-6">
                    <!-- Search -->
                    <button class="text-brand-text hover:text-brand-primary transition-colors duration-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </button>

                    <!-- Cart -->
                    <a href="{{ route('cart.index') }}" class="relative text-brand-text hover:text-brand-primary transition-colors duration-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                        </svg>
                        @if(session('cart') && count(session('cart')) > 0)
                            <span class="absolute -top-2 -right-2.5 inline-flex items-center justify-center min-w-[18px] h-[18px] text-[10px] font-bold text-white bg-brand-primary rounded-full px-1">
                                {{ count(session('cart')) }}
                            </span>
                        @endif
                    </a>

                    <!-- Mobile Menu Button -->
                    <button @click="mobileMenu = !mobileMenu" class="lg:hidden text-brand-text">
                        <svg x-show="!mobileMenu" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                        <svg x-show="mobileMenu" x-cloak class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </nav>

        <!-- Mobile Menu -->
        <div x-show="mobileMenu" x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-1"
             class="lg:hidden border-t border-brand-border bg-white">
            <div class="max-w-8xl mx-auto px-6 py-6 space-y-1">
                <a href="{{ route('home') }}" class="block py-3 text-[14px] text-brand-text hover:text-brand-primary font-medium uppercase tracking-wide border-b border-brand-divider {{ request()->routeIs('home') ? 'text-brand-primary' : '' }}">Home</a>
                <a href="{{ route('products.index') }}" class="block py-3 text-[14px] text-brand-text hover:text-brand-primary font-medium uppercase tracking-wide border-b border-brand-divider {{ request()->routeIs('products.*') ? 'text-brand-primary' : '' }}">Shop All</a>
                <a href="{{ route('brands.index') }}" class="block py-3 text-[14px] text-brand-text hover:text-brand-primary font-medium uppercase tracking-wide border-b border-brand-divider {{ request()->routeIs('brands.*') ? 'text-brand-primary' : '' }}">Brands</a>
                <a href="{{ route('flash-sale') }}" class="block py-3 text-[14px] text-brand-text hover:text-brand-sale font-medium uppercase tracking-wide border-b border-brand-divider {{ request()->routeIs('flash-sale') ? 'text-brand-sale' : '' }}">Flash Sale</a>
                <a href="{{ route('gift-cards') }}" class="block py-3 text-[14px] text-brand-text hover:text-brand-primary font-medium uppercase tracking-wide border-b border-brand-divider {{ request()->routeIs('gift-cards') ? 'text-brand-primary' : '' }}">Gift Cards</a>
                <a href="{{ route('wholesale') }}" class="block py-3 text-[14px] text-brand-text hover:text-brand-primary font-medium uppercase tracking-wide border-b border-brand-divider {{ request()->routeIs('wholesale') ? 'text-brand-primary' : '' }}">Wholesale</a>
                <a href="{{ route('contact') }}" class="block py-3 text-[14px] text-brand-text hover:text-brand-primary font-medium uppercase tracking-wide {{ request()->routeIs('contact') ? 'text-brand-primary' : '' }}">Contact</a>
            </div>
        </div>
    </header>

    <!-- Flash Messages -->
    @if(session('success'))
        <div class="max-w-8xl mx-auto px-6 lg:px-10 mt-6">
            <div class="bg-brand-light border-l-2 border-brand-success px-5 py-4">
                <div class="flex items-center gap-3">
                    <svg class="h-4 w-4 text-brand-success flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <p class="text-[13px] text-brand-text font-medium">{{ session('success') }}</p>
                </div>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="max-w-8xl mx-auto px-6 lg:px-10 mt-6">
            <div class="bg-red-50 border-l-2 border-brand-sale px-5 py-4">
                <div class="flex items-center gap-3">
                    <svg class="h-4 w-4 text-brand-sale flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <p class="text-[13px] text-brand-sale font-medium">{{ session('error') }}</p>
                </div>
            </div>
        </div>
    @endif

    @if(session('info'))
        <div class="max-w-8xl mx-auto px-6 lg:px-10 mt-6">
            <div class="bg-brand-light border-l-2 border-brand-primary px-5 py-4">
                <div class="flex items-center gap-3">
                    <svg class="h-4 w-4 text-brand-primary flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    <p class="text-[13px] text-brand-text font-medium">{{ session('info') }}</p>
                </div>
            </div>
        </div>
    @endif

    <!-- Main Content -->
    <main class="min-h-screen">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-brand-dark text-white mt-24">
        <div class="max-w-8xl mx-auto px-6 lg:px-10 py-20">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-12">
                <!-- Brand -->
                <div class="md:col-span-5">
                    @if(!empty($storeLogo))
                        <a href="{{ route('home') }}" class="inline-block mb-6">
                            <img src="{{ $storeLogo }}" alt="{{ $storeName ?? 'AD Perfumes' }}" class="h-12 w-auto brightness-0 invert">
                        </a>
                    @else
                        <h3 class="text-[20px] font-bold mb-6 uppercase tracking-tight">
                            <span class="text-white">AD</span>
                            <span class="text-brand-primary ml-1">Perfumes</span>
                        </h3>
                    @endif
                    <p class="text-brand-muted text-[13px] leading-relaxed max-w-sm">
                        Your trusted destination for authentic luxury fragrances in the UAE.
                        Curating the world's finest perfumes, delivered to your doorstep.
                    </p>
                    <div class="mt-8 flex gap-4">
                        <a href="#" class="text-brand-muted hover:text-brand-primary transition-colors duration-300">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        </a>
                        <a href="#" class="text-brand-muted hover:text-brand-primary transition-colors duration-300">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                        </a>
                    </div>
                </div>

                <!-- Shop Links -->
                <div class="md:col-span-2">
                    <h4 class="text-[11px] font-semibold mb-6 uppercase tracking-editorial text-white">Shop</h4>
                    <ul class="space-y-3">
                        <li><a href="{{ route('products.index') }}" class="text-[13px] text-brand-muted hover:text-brand-primary transition-colors duration-300">All Perfumes</a></li>
                        <li><a href="{{ route('brands.index') }}" class="text-[13px] text-brand-muted hover:text-brand-primary transition-colors duration-300">Brands</a></li>
                        <li><a href="{{ route('flash-sale') }}" class="text-[13px] text-brand-muted hover:text-brand-primary transition-colors duration-300">Flash Sale</a></li>
                        <li><a href="{{ route('gift-cards') }}" class="text-[13px] text-brand-muted hover:text-brand-primary transition-colors duration-300">Gift Cards</a></li>
                    </ul>
                </div>

                <!-- Support Links -->
                <div class="md:col-span-2">
                    <h4 class="text-[11px] font-semibold mb-6 uppercase tracking-editorial text-white">Support</h4>
                    <ul class="space-y-3">
                        <li><a href="{{ route('about') }}" class="text-[13px] text-brand-muted hover:text-brand-primary transition-colors duration-300">About Us</a></li>
                        <li><a href="{{ route('contact') }}" class="text-[13px] text-brand-muted hover:text-brand-primary transition-colors duration-300">Contact</a></li>
                        <li><a href="{{ route('shipping-policy') }}" class="text-[13px] text-brand-muted hover:text-brand-primary transition-colors duration-300">Shipping</a></li>
                        <li><a href="{{ route('return-policy') }}" class="text-[13px] text-brand-muted hover:text-brand-primary transition-colors duration-300">Returns</a></li>
                    </ul>
                </div>

                <!-- Newsletter -->
                <div class="md:col-span-3">
                    <h4 class="text-[11px] font-semibold mb-6 uppercase tracking-editorial text-white">Newsletter</h4>
                    <p class="text-[13px] text-brand-muted mb-5 leading-relaxed">Exclusive offers and new arrivals, straight to your inbox.</p>
                    <form class="flex">
                        <input type="email" placeholder="Your email" class="flex-1 px-4 py-3 bg-brand-charcoal border border-gray-800 text-[13px] text-white placeholder-brand-muted focus:outline-none focus:border-brand-primary transition-colors duration-300">
                        <button type="submit" class="px-5 py-3 bg-brand-primary text-white text-[11px] font-semibold uppercase tracking-luxury hover:bg-brand-primary-soft transition-colors duration-300">
                            Join
                        </button>
                    </form>
                </div>
            </div>

            <!-- Bottom Bar -->
            <div class="mt-16 pt-8 border-t border-gray-800 flex flex-col md:flex-row justify-between items-center">
                <p class="text-[12px] text-brand-muted">&copy; {{ date('Y') }} {{ $storeName ?? 'AD Perfumes' }}. All rights reserved.</p>
                <div class="flex gap-8 mt-4 md:mt-0">
                    <a href="{{ route('privacy-policy') }}" class="text-[12px] text-brand-muted hover:text-white transition-colors duration-300">Privacy Policy</a>
                    <a href="{{ route('terms') }}" class="text-[12px] text-brand-muted hover:text-white transition-colors duration-300">Terms & Conditions</a>
                </div>
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
