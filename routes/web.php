<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\CatalogFeedController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\RobotsController;

// Home
Route::get('/', [HomeController::class, 'index'])->name('home');

// Products
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{slug}', [ProductController::class, 'show'])->name('products.show');

// Brands
Route::get('/brands', [ProductController::class, 'brands'])->name('brands.index');
Route::get('/brands/{slug}', [ProductController::class, 'byBrand'])->name('products.byBrand');

// Cart
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::post('/cart/update', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/remove/{id}', [CartController::class, 'remove'])->name('cart.remove');
Route::delete('/cart/clear', [CartController::class, 'clear'])->name('cart.clear');

// Checkout
Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
Route::post('/checkout/process', [CheckoutController::class, 'process'])->name('checkout.process');

// Payment Callbacks
Route::post('/payment/callback/tap', [PaymentController::class, 'tapCallback'])->name('payment.callback.tap');
Route::get('/payment/return/tap', [PaymentController::class, 'tapReturn'])->name('payment.return.tap');

// Order Confirmation & Tracking
Route::get('/order/{orderNumber}', [PaymentController::class, 'orderConfirmation'])->name('order.confirmation');
Route::get('/track-order', [PaymentController::class, 'trackOrder'])->name('order.track');

// Static Pages
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');
Route::post('/contact', [PageController::class, 'contactSubmit'])->name('contact.submit');
Route::get('/terms-conditions', [PageController::class, 'terms'])->name('terms');
Route::get('/return-refund-policy', [PageController::class, 'returnPolicy'])->name('return-policy');
Route::get('/shipping-policy', [PageController::class, 'shippingPolicy'])->name('shipping-policy');
Route::get('/privacy-policy', [PageController::class, 'privacyPolicy'])->name('privacy-policy');
Route::get('/wholesale', [PageController::class, 'wholesale'])->name('wholesale');
Route::post('/wholesale', [PageController::class, 'wholesaleSubmit'])->name('wholesale.submit');
Route::get('/flash-sale', [PageController::class, 'flashSale'])->name('flash-sale');
Route::get('/gift-cards', [PageController::class, 'giftCards'])->name('gift-cards');

// Blog
Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');

// Catalog Feeds
Route::get('/feed/google', [CatalogFeedController::class, 'google'])->name('feed.google');
Route::get('/feed/meta', [CatalogFeedController::class, 'meta'])->name('feed.meta');
Route::get('/feed/tiktok', [CatalogFeedController::class, 'tiktok'])->name('feed.tiktok');
Route::get('/feed/snapchat', [CatalogFeedController::class, 'snapchat'])->name('feed.snapchat');

// SEO
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/robots.txt', [RobotsController::class, 'index'])->name('robots');
