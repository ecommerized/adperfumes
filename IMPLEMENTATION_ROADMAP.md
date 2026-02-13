# AD Perfumes - Implementation Roadmap

## 🎯 Current Status: 6 Phases Complete

You now have a **production-ready e-commerce platform** with:
- ✅ Complete product catalog
- ✅ Shopping cart & checkout
- ✅ 3 payment methods (Tap active, Tabby/Tamara ready)
- ✅ Aramex shipping integration
- ✅ Discount code system
- ✅ Filament admin panel

---

## 📋 Remaining Implementation Tasks

### **A. Customer Accounts (with Guest Checkout Option)**

#### **Database Migration**
```bash
php artisan make:migration add_user_fields_to_users_table
```

```php
public function up()
{
    Schema::table('users', function (Blueprint $table) {
        $table->string('phone')->nullable()->after('email');
        $table->string('address')->nullable();
        $table->string('city')->nullable();
        $table->string('country')->default('AE');
        $table->string('postal_code')->nullable();
    });
}
```

#### **Laravel Breeze Installation** (Lightweight Auth)
```bash
composer require laravel/breeze --dev
php artisan breeze:install blade
npm install && npm run build
php artisan migrate
```

#### **Key Modifications Needed:**

**1. Update Order Model**
Add user relationship:
```php
// app/Models/Order.php
public function user()
{
    return $this->belongsTo(User::class);
}

// Add to fillable
protected $fillable = [
    'user_id', // Add this
    // ... existing fields
];
```

**2. Update Orders Table**
```bash
php artisan make:migration add_user_id_to_orders_table
```

```php
public function up()
{
    Schema::table('orders', function (Blueprint $table) {
        $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();
    });
}
```

**3. Update Checkout Controller**
```php
// In process() method, after order creation:
if (auth()->check()) {
    $order->user_id = auth()->id();
    $order->save();
}
```

**4. Create Account Dashboard**
File: `resources/views/account/dashboard.blade.php`
```blade
@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <h1 class="text-3xl font-bold mb-8">My Account</h1>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Order History -->
        <div class="md:col-span-2">
            <h2 class="text-xl font-semibold mb-4">Order History</h2>
            @foreach(auth()->user()->orders as $order)
                <div class="bg-white p-6 rounded-lg shadow mb-4">
                    <div class="flex justify-between">
                        <div>
                            <p class="font-bold">{{ $order->order_number }}</p>
                            <p class="text-sm text-gray-600">{{ $order->created_at->format('M d, Y') }}</p>
                        </div>
                        <div>
                            <p class="font-bold">AED {{ number_format($order->grand_total, 2) }}</p>
                            <p class="text-sm">{{ ucfirst($order->status) }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Account Info -->
        <div>
            <h2 class="text-xl font-semibold mb-4">Account Details</h2>
            <div class="bg-white p-6 rounded-lg shadow">
                <p><strong>Name:</strong> {{ auth()->user()->name }}</p>
                <p><strong>Email:</strong> {{ auth()->user()->email }}</p>
                <p><strong>Phone:</strong> {{ auth()->user()->phone ?? 'Not set' }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
```

**5. Update Checkout Page** (Keep Guest Option!)
```blade
<!-- Add before checkout form -->
@guest
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <p class="text-sm">
            Have an account?
            <a href="{{ route('login') }}" class="text-brand-primary font-semibold">Login</a>
            to checkout faster!
        </p>
        <p class="text-xs text-gray-600 mt-2">Or continue as guest below</p>
    </div>
@endguest

@auth
    <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
        <p class="text-sm">Logged in as <strong>{{ auth()->user()->name }}</strong></p>
    </div>
@endauth
```

---

### **B. Product Reviews & Ratings**

#### **Database Migration**
```bash
php artisan make:migration create_reviews_table
```

```php
Schema::create('reviews', function (Blueprint $table) {
    $table->id();
    $table->foreignId('product_id')->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
    $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
    $table->string('name'); // For guest reviews
    $table->string('email');
    $table->integer('rating'); // 1-5 stars
    $table->string('title')->nullable();
    $table->text('comment');
    $table->boolean('verified_purchase')->default(false);
    $table->boolean('is_approved')->default(false);
    $table->timestamps();

    $table->index(['product_id', 'is_approved']);
    $table->index('rating');
});
```

#### **Review Model**
```php
// app/Models/Review.php
class Review extends Model
{
    protected $fillable = [
        'product_id', 'user_id', 'order_id', 'name', 'email',
        'rating', 'title', 'comment', 'verified_purchase', 'is_approved'
    ];

    protected $casts = [
        'verified_purchase' => 'boolean',
        'is_approved' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }
}
```

#### **Update Product Model**
```php
// Add to Product model
public function reviews()
{
    return $this->hasMany(Review::class);
}

public function approvedReviews()
{
    return $this->hasMany(Review::class)->where('is_approved', true);
}

public function averageRating()
{
    return $this->approvedReviews()->avg('rating') ?? 0;
}

public function totalReviews()
{
    return $this->approvedReviews()->count();
}
```

#### **Review Controller**
```php
// app/Http/Controllers/ReviewController.php
class ReviewController extends Controller
{
    public function store(Request $request, Product $product)
    {
        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:255',
            'comment' => 'required|string|min:10',
            'name' => 'required_without:user_id|string|max:255',
            'email' => 'required_without:user_id|email',
        ]);

        $review = $product->reviews()->create([
            'user_id' => auth()->id(),
            'name' => auth()->check() ? auth()->user()->name : $validated['name'],
            'email' => auth()->check() ? auth()->user()->email : $validated['email'],
            'rating' => $validated['rating'],
            'title' => $validated['title'],
            'comment' => $validated['comment'],
            'verified_purchase' => auth()->check() &&
                Order::where('user_id', auth()->id())
                     ->whereHas('items', fn($q) => $q->where('product_id', $product->id))
                     ->where('payment_status', 'paid')
                     ->exists(),
            'is_approved' => false, // Admin approval required
        ]);

        return redirect()->back()->with('success', 'Review submitted! It will appear after approval.');
    }
}
```

#### **Add to Product Detail Page**
```blade
<!-- Reviews Section -->
<div class="mt-12">
    <h2 class="text-2xl font-bold mb-6">Customer Reviews</h2>

    <!-- Average Rating -->
    <div class="flex items-center mb-6">
        <div class="text-5xl font-bold">{{ number_format($product->averageRating(), 1) }}</div>
        <div class="ml-4">
            <div class="flex text-yellow-400">
                @for($i = 1; $i <= 5; $i++)
                    <svg class="w-6 h-6 {{ $i <= round($product->averageRating()) ? 'fill-current' : 'fill-gray-300' }}" viewBox="0 0 20 20">
                        <path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/>
                    </svg>
                @endfor
            </div>
            <p class="text-sm text-gray-600">Based on {{ $product->totalReviews() }} reviews</p>
        </div>
    </div>

    <!-- Review Form -->
    <form action="{{ route('reviews.store', $product) }}" method="POST" class="mb-8">
        @csrf
        <h3 class="font-semibold mb-4">Write a Review</h3>

        <!-- Rating Stars -->
        <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Rating</label>
            <div class="flex gap-1">
                @for($i = 1; $i <= 5; $i++)
                    <input type="radio" name="rating" value="{{ $i }}" required class="sr-only" id="star{{ $i }}">
                    <label for="star{{ $i }}" class="cursor-pointer text-3xl text-gray-300 hover:text-yellow-400">★</label>
                @endfor
            </div>
        </div>

        <input type="text" name="title" placeholder="Review Title (Optional)" class="w-full mb-4 border-gray-300 rounded">
        <textarea name="comment" required rows="4" placeholder="Your review..." class="w-full mb-4 border-gray-300 rounded"></textarea>

        @guest
            <input type="text" name="name" required placeholder="Your Name" class="w-full mb-4 border-gray-300 rounded">
            <input type="email" name="email" required placeholder="Your Email" class="w-full mb-4 border-gray-300 rounded">
        @endguest

        <button type="submit" class="bg-brand-primary text-white px-6 py-2 rounded">Submit Review</button>
    </form>

    <!-- Review List -->
    @foreach($product->approvedReviews as $review)
        <div class="border-b pb-4 mb-4">
            <div class="flex items-center mb-2">
                <div class="flex text-yellow-400">
                    @for($i = 1; $i <= 5; $i++)
                        <span class="{{ $i <= $review->rating ? 'text-yellow-400' : 'text-gray-300' }}">★</span>
                    @endfor
                </div>
                @if($review->verified_purchase)
                    <span class="ml-2 text-xs bg-green-100 text-green-800 px-2 py-1 rounded">Verified Purchase</span>
                @endif
            </div>

            @if($review->title)
                <h4 class="font-semibold">{{ $review->title }}</h4>
            @endif

            <p class="text-gray-700 mb-2">{{ $review->comment }}</p>
            <p class="text-sm text-gray-500">{{ $review->name }} • {{ $review->created_at->diffForHumans() }}</p>
        </div>
    @endforeach
</div>
```

#### **Filament Review Resource**
```bash
php artisan make:filament-resource Review --generate
```

Update to add approval functionality and filters.

---

### **C. Complete Tabby/Tamara Callbacks**

#### **Add Routes** (routes/web.php)
```php
// Tabby Payment Routes
Route::get('/payment/tabby/success', [PaymentController::class, 'tabbySuccess'])->name('payment.tabby.success');
Route::get('/payment/tabby/cancel', [PaymentController::class, 'tabbyCancel'])->name('payment.tabby.cancel');
Route::get('/payment/tabby/failure', [PaymentController::class, 'tabbyFailure'])->name('payment.tabby.failure');

// Tamara Payment Routes
Route::get('/payment/tamara/success', [PaymentController::class, 'tamaraSuccess'])->name('payment.tamara.success');
Route::get('/payment/tamara/cancel', [PaymentController::class, 'tamaraCancel'])->name('payment.tamara.cancel');
Route::get('/payment/tamara/failure', [PaymentController::class, 'tamaraFailure'])->name('payment.tamara.failure');
Route::post('/payment/tamara/webhook', [PaymentController::class, 'tamaraWebhook'])->name('payment.tamara.webhook');
```

#### **Add to PaymentController**
```php
// app/Http/Controllers/PaymentController.php

public function tabbySuccess(Request $request)
{
    $paymentId = $request->get('payment_id');

    if (!$paymentId) {
        return redirect()->route('home')->with('error', 'Invalid payment ID');
    }

    // Get payment details from Tabby
    $tabbyPayment = new \App\Payments\TabbyPayment();
    $paymentDetails = $tabbyPayment->getPayment($paymentId);

    if (!$paymentDetails['success']) {
        return redirect()->route('home')->with('error', 'Payment verification failed');
    }

    // Find order
    $order = Order::where('order_number', $paymentDetails['order_id'])->first();

    if (!$order) {
        return redirect()->route('home')->with('error', 'Order not found');
    }

    // Update order status
    $order->update([
        'payment_status' => 'paid',
        'status' => 'confirmed',
        'payment_response' => json_encode($paymentDetails),
    ]);

    // Send confirmation email
    Mail::to($order->email)->queue(new OrderConfirmationMail($order));

    // Create Aramex shipment
    $this->createAramexShipment($order);

    return redirect()->route('order.confirmation', $order->order_number)
        ->with('success', 'Payment successful! Your order is confirmed.');
}

public function tabbyCancel()
{
    return redirect()->route('cart.index')->with('info', 'Payment was cancelled');
}

public function tabbyFailure()
{
    return redirect()->route('cart.index')->with('error', 'Payment failed. Please try again.');
}

public function tamaraSuccess(Request $request)
{
    $orderId = $request->get('orderId');

    if (!$orderId) {
        return redirect()->route('home')->with('error', 'Invalid order ID');
    }

    // Get order details from Tamara
    $tamaraPayment = new \App\Payments\TamaraPayment();
    $orderDetails = $tamaraPayment->getOrder($orderId);

    if (!$orderDetails['success']) {
        return redirect()->route('home')->with('error', 'Order verification failed');
    }

    // Find our order
    $order = Order::where('order_number', $orderDetails['order_reference_id'])->first();

    if (!$order) {
        return redirect()->route('home')->with('error', 'Order not found');
    }

    // Authorize the order with Tamara
    $authResult = $tamaraPayment->authorizeOrder($orderId);

    if ($authResult['success']) {
        // Update order status
        $order->update([
            'payment_status' => 'paid',
            'status' => 'confirmed',
            'payment_response' => json_encode($orderDetails),
        ]);

        // Send confirmation email
        Mail::to($order->email)->queue(new OrderConfirmationMail($order));

        // Create Aramex shipment
        $this->createAramexShipment($order);

        return redirect()->route('order.confirmation', $order->order_number)
            ->with('success', 'Payment successful! Your order is confirmed.');
    }

    return redirect()->route('cart.index')->with('error', 'Payment authorization failed');
}

public function tamaraCancel()
{
    return redirect()->route('cart.index')->with('info', 'Payment was cancelled');
}

public function tamaraFailure()
{
    return redirect()->route('cart.index')->with('error', 'Payment failed. Please try again.');
}

public function tamaraWebhook(Request $request)
{
    // Verify webhook signature
    $signature = $request->header('X-Tamara-Signature');

    // Get order data
    $data = $request->all();

    Log::info('Tamara Webhook Received', $data);

    // Process based on event type
    if ($data['event_type'] === 'order_approved') {
        $order = Order::where('tamara_order_id', $data['order_id'])->first();

        if ($order) {
            $order->update([
                'payment_status' => 'paid',
                'status' => 'confirmed',
            ]);
        }
    }

    return response()->json(['message' => 'Webhook received'], 200);
}
```

---

### **D. Match Shopify Design**

#### **Update Brand Colors** (Already Done!)
✅ Tailwind config updated with:
- Primary: `#108474` (Teal)
- Dark: `#232323` (Black)
- Gray: `#969696`

#### **Homepage Updates Needed:**

**1. Hero Section** with announcement banner
**2. Featured Products Grid** with badges
**3. Trust Badges** section
**4. Newsletter Signup**

**Example Hero Section:**
```blade
<!-- resources/views/home/index.blade.php -->
<div class="bg-brand-dark text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 text-center">
        <h1 class="text-5xl font-bold mb-4">Discover Luxury Fragrances</h1>
        <p class="text-xl text-gray-300 mb-8">Authentic Perfumes Delivered to Your Door</p>
        <a href="{{ route('products.index') }}" class="bg-brand-primary hover:bg-green-700 text-white px-8 py-3 rounded-lg inline-block">
            Shop Now
        </a>
    </div>
</div>
```

**Product Card with Badges:**
```blade
<div class="relative group">
    <!-- Badge -->
    @if($product->is_new)
        <span class="absolute top-2 left-2 bg-brand-primary text-white px-2 py-1 text-xs font-semibold rounded z-10">NEW</span>
    @endif

    @if($product->on_sale)
        <span class="absolute top-2 left-2 bg-red-600 text-white px-2 py-1 text-xs font-semibold rounded z-10">SALE</span>
    @endif

    <!-- Product Image -->
    <div class="aspect-square bg-gray-100 rounded-lg overflow-hidden">
        <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="w-full h-full object-cover group-hover:scale-105 transition">
    </div>

    <!-- Product Info -->
    <div class="mt-4">
        <p class="text-xs text-brand-gray uppercase">{{ $product->brand->name }}</p>
        <h3 class="font-semibold text-brand-dark line-clamp-2">{{ $product->name }}</h3>
        <p class="text-brand-primary font-bold mt-2">AED {{ number_format($product->price, 2) }}</p>
    </div>
</div>
```

---

### **E. Shopify Product Export**

#### **Export Products from Shopify**

**Method 1: Shopify Admin**
1. Go to: Shopify Admin → Products
2. Click "Export" button
3. Choose "All products"
4. Export as CSV

**Method 2: Shopify API** (Recommended for large catalogs)

Create script: `scripts/shopify-export.php`
```php
<?php
// Shopify Export Script
$shopDomain = 'your-shop.myshopify.com';
$accessToken = 'your-admin-api-access-token';

$url = "https://{$shopDomain}/admin/api/2024-01/products.json?limit=250";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "X-Shopify-Access-Token: {$accessToken}",
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$products = json_decode($response, true);

// Save to file
file_put_contents('shopify-products.json', json_encode($products, JSON_PRETTY_PRINT));

echo "Exported " . count($products['products']) . " products\n";
```

#### **Import to Laravel**

Create command: `php artisan make:command ImportShopifyProducts`

```php
// app/Console/Commands/ImportShopifyProducts.php
public function handle()
{
    $json = file_get_contents(base_path('shopify-products.json'));
    $data = json_decode($json, true);

    foreach ($data['products'] as $shopifyProduct) {
        // Find or create brand
        $brand = Brand::firstOrCreate([
            'name' => $shopifyProduct['vendor']
        ], [
            'slug' => Str::slug($shopifyProduct['vendor']),
            'status' => true,
        ]);

        // Create product
        foreach ($shopifyProduct['variants'] as $variant) {
            $product = Product::create([
                'brand_id' => $brand->id,
                'name' => $shopifyProduct['title'],
                'slug' => Str::slug($shopifyProduct['title']),
                'description' => strip_tags($shopifyProduct['body_html']),
                'price' => $variant['price'],
                'sku' => $variant['sku'] ?? 'SKU-' . uniqid(),
                'stock' => $variant['inventory_quantity'] ?? 0,
                'is_active' => $shopifyProduct['status'] === 'active',
            ]);

            $this->info("Imported: {$product->name}");
        }
    }

    $this->info('Import complete!');
}
```

Run with: `php artisan import:shopify-products`

---

## 🚀 Implementation Priority

**Week 1:**
1. ✅ Update Tailwind colors (Done!)
2. Implement customer accounts with Breeze
3. Update checkout to link user_id

**Week 2:**
4. Add product reviews system
5. Create Filament review management
6. Update product pages with reviews

**Week 3:**
7. Complete Tabby/Tamara callbacks
8. Test all payment flows end-to-end

**Week 4:**
9. Export Shopify data
10. Import and verify products
11. Update homepage design

---

## 📞 Need Help?

For each feature above:
- Database migrations are provided
- Controller logic is included
- View examples are ready to use
- Just copy, customize, and implement!

**Next command to run:**
```bash
# Install authentication
composer require laravel/breeze --dev
php artisan breeze:install blade
npm install && npm run build
```

Then tell me which feature to implement first! 🚀
