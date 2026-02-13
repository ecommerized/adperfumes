<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">Product Catalog Feeds</x-slot>
            <x-slot name="description">Copy these feed URLs and paste them into each platform's catalog manager. Feeds auto-update with your latest products.</x-slot>

            <div class="space-y-6">
                {{-- Google Merchant Center --}}
                <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-8 h-8 bg-white dark:bg-gray-700 rounded-lg flex items-center justify-center shadow-sm">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-sm">Google Merchant Center</h3>
                            <p class="text-xs text-gray-500">XML feed for Google Shopping ads</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 mt-3">
                        <input type="text" readonly value="{{ $this->getGoogleFeedUrl() }}" class="flex-1 text-xs bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded px-3 py-2 font-mono" id="google-feed-url">
                        <button onclick="navigator.clipboard.writeText(document.getElementById('google-feed-url').value); this.textContent='Copied!'; setTimeout(() => this.textContent='Copy', 2000)" class="px-3 py-2 bg-primary-600 text-white text-xs font-semibold rounded hover:bg-primary-700 transition">Copy</button>
                    </div>
                </div>

                {{-- Meta (Facebook/Instagram) --}}
                <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-8 h-8 bg-white dark:bg-gray-700 rounded-lg flex items-center justify-center shadow-sm">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="#1877F2"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-sm">Meta Catalog (Facebook / Instagram)</h3>
                            <p class="text-xs text-gray-500">CSV feed for Meta Commerce Manager</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 mt-3">
                        <input type="text" readonly value="{{ $this->getMetaFeedUrl() }}" class="flex-1 text-xs bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded px-3 py-2 font-mono" id="meta-feed-url">
                        <button onclick="navigator.clipboard.writeText(document.getElementById('meta-feed-url').value); this.textContent='Copied!'; setTimeout(() => this.textContent='Copy', 2000)" class="px-3 py-2 bg-primary-600 text-white text-xs font-semibold rounded hover:bg-primary-700 transition">Copy</button>
                    </div>
                </div>

                {{-- TikTok --}}
                <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-8 h-8 bg-white dark:bg-gray-700 rounded-lg flex items-center justify-center shadow-sm">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.1v-3.5a6.37 6.37 0 00-.79-.05A6.34 6.34 0 003.15 15.2a6.34 6.34 0 0010.86 4.45v-7.5a8.16 8.16 0 004.77 1.53V10.3a4.85 4.85 0 01-.8.07 4.8 4.8 0 01-.39-3.68z"/></svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-sm">TikTok Catalog</h3>
                            <p class="text-xs text-gray-500">CSV feed for TikTok Shop & Ads</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 mt-3">
                        <input type="text" readonly value="{{ $this->getTiktokFeedUrl() }}" class="flex-1 text-xs bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded px-3 py-2 font-mono" id="tiktok-feed-url">
                        <button onclick="navigator.clipboard.writeText(document.getElementById('tiktok-feed-url').value); this.textContent='Copied!'; setTimeout(() => this.textContent='Copy', 2000)" class="px-3 py-2 bg-primary-600 text-white text-xs font-semibold rounded hover:bg-primary-700 transition">Copy</button>
                    </div>
                </div>

                {{-- Snapchat --}}
                <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-8 h-8 bg-white dark:bg-gray-700 rounded-lg flex items-center justify-center shadow-sm">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="#FFFC00"><path d="M12.206.793c.99 0 4.347.276 5.93 3.821.529 1.193.403 3.219.299 4.847l-.003.06c-.012.18-.022.345-.03.51.075.045.203.09.401.09.3-.016.659-.12 1.033-.301.165-.088.344-.104.464-.104.182 0 .359.029.509.09.45.149.734.479.734.838.015.449-.39.839-1.213 1.168-.089.029-.209.075-.344.119-.45.135-1.139.36-1.333.81-.09.224-.061.524.12.868l.015.015c.06.136 1.526 3.475 4.791 4.014.255.044.435.27.42.509 0 .075-.015.149-.045.225-.24.569-1.273.988-3.146 1.271-.059.091-.12.375-.164.57-.029.179-.074.36-.134.553-.076.271-.27.405-.555.405h-.03a3.314 3.314 0 01-.614-.074 5.48 5.48 0 00-1.213-.164c-.254 0-.464.03-.659.074-.225.06-.494.165-.793.296a7.043 7.043 0 01-2.55.584c-.03 0-.06-.015-.089-.015h-.061c-.03 0-.06.015-.089.015-1.004 0-1.862-.284-2.55-.584a5.04 5.04 0 00-.793-.296 3.15 3.15 0 00-.659-.074 5.63 5.63 0 00-1.213.164 3.29 3.29 0 01-.614.074h-.03c-.284 0-.479-.134-.554-.405a4.263 4.263 0 01-.135-.553c-.044-.195-.104-.479-.164-.57-1.872-.283-2.905-.702-3.146-1.271a.582.582 0 01-.044-.225c-.015-.24.164-.465.42-.509 3.264-.54 4.73-3.879 4.791-4.02l.016-.029c.18-.345.209-.644.119-.869-.195-.434-.884-.659-1.332-.809a4.12 4.12 0 01-.345-.12c-.6-.239-1.258-.659-1.213-1.168 0-.36.284-.689.734-.839.15-.06.328-.089.51-.089.119 0 .299.015.449.089.374.18.72.301 1.033.301.166 0 .301-.03.375-.074a38.61 38.61 0 01-.029-.51l-.003-.06c-.105-1.627-.225-3.654.3-4.848C7.846 1.069 11.205.793 12.196.793h.01z"/></svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-sm">Snapchat Catalog</h3>
                            <p class="text-xs text-gray-500">CSV feed for Snapchat Dynamic Ads</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 mt-3">
                        <input type="text" readonly value="{{ $this->getSnapchatFeedUrl() }}" class="flex-1 text-xs bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded px-3 py-2 font-mono" id="snapchat-feed-url">
                        <button onclick="navigator.clipboard.writeText(document.getElementById('snapchat-feed-url').value); this.textContent='Copied!'; setTimeout(() => this.textContent='Copy', 2000)" class="px-3 py-2 bg-primary-600 text-white text-xs font-semibold rounded hover:bg-primary-700 transition">Copy</button>
                    </div>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">How to Use</x-slot>
            <div class="prose prose-sm dark:prose-invert max-w-none">
                <ol class="text-sm space-y-2">
                    <li><strong>Google Merchant Center:</strong> Go to Products > Feeds > Add Feed > Scheduled fetch. Paste the Google feed URL.</li>
                    <li><strong>Meta Commerce Manager:</strong> Go to Catalog > Data Sources > Data Feed > Scheduled Feed. Paste the Meta feed URL.</li>
                    <li><strong>TikTok Ads Manager:</strong> Go to Assets > Catalogs > Add Catalog > Scheduled feed. Paste the TikTok feed URL.</li>
                    <li><strong>Snapchat Ads Manager:</strong> Go to Catalogs > New Catalog > Feed URL. Paste the Snapchat feed URL.</li>
                </ol>
                <p class="text-xs text-gray-500 mt-4">Feeds are generated in real-time from your active products. Set each platform to refresh the feed daily for best results.</p>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
