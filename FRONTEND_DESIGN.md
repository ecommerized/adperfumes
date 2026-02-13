# AD Perfumes - Frontend Design System
## Luxury Perfume Marketplace UI/UX

Inspired by adperfumes.ae minimalist luxury aesthetic

---

## Design Philosophy

**Minimalist Luxury**
- Deep blacks (#232323), whites, and grays
- Maximum white space
- Clean typography hierarchy
- Premium spacing patterns
- Mobile-first approach
- Fast loading, no heavy animations

---

## Tech Stack

- **Laravel Blade** - Server-side templating
- **Tailwind CSS** - Utility-first styling
- **Alpine.js** - Minimal JavaScript interactions
- **Instrument Sans** - Professional sans-serif font

---

## Design Specifications

### Typography

```
Headings: 18-64px, 700 weight, UPPERCASE, 0.05em letter-spacing
Body: 14px, 400 weight, 24px line-height
Navigation: 12px, 500 weight, UPPERCASE
Buttons: 11-14px, 600 weight, UPPERCASE
```

### Colors

```php
brand-primary: #108474  // Teal accent
brand-dark:    #232323  // Deep black
brand-gray:    #969696  // Gray text
brand-light:   #F8F8F8  // Light background
brand-sale:    #e95144  // Sale badge red
```

### Spacing

```
Container: max-width 1600px (max-w-8xl)
Padding: 6-10 on containers (24-40px)
Gaps: 4-6 for grids (16-24px)
Border Radius: 6px (rounded-luxury)
```

---

## Reusable Blade Components

### 1. Product Card (`<x-product-card />`)

**Location:** `resources/views/components/product-card.blade.php`

**Features:**
- Square aspect ratio image
- Brand name (11px, uppercase, gray)
- Product title (14px, 2-line clamp)
- Price display with sale support
- "Add to Cart" button (black bg, white text)
- Wishlist icon on hover
- NEW/SALE badges
- Hover shadow effect

**Usage:**
```blade
<x-product-card :product="$product" />
```

---

### 2. Brand Card (`<x-brand-card />`)

**Location:** `resources/views/components/brand-card.blade.php`

**Features:**
- Logo display with grayscale to color transition
- Product count
- "Shop Now" link on hover
- Clean border hover effect

**Usage:**
```blade
<x-brand-card :brand="$brand" />
```

---

### 3. Note Badge (`<x-note-badge />`)

**Location:** `resources/views/components/note-badge.blade.php`

**Features:**
- White background with border
- Leaf icon
- 12px text
- For Top/Middle/Base notes display

**Usage:**
```blade
<x-note-badge :note="$note" />
```

---

### 4. Accord Badge (`<x-accord-badge />`)

**Location:** `resources/views/components/accord-badge.blade.php`

**Features:**
- Black background, white text
- Uppercase, 11px
- For Main Accords display

**Usage:**
```blade
<x-accord-badge :accord="$accord" />
```

---

### 5. Button Component (`<x-button />`)

**Location:** `resources/views/components/button.blade.php`

**Variants:**
- `primary` - Black background, white text
- `secondary` - Teal background, white text
- `outline` - White background, black border

**Sizes:**
- `sm` - 11px, px-4 py-2
- `default` - 12px, px-6 py-3
- `lg` - 14px, px-8 py-4

**Usage:**
```blade
<x-button variant="primary" size="lg" href="{{ route('products.index') }}">
    Shop Now
</x-button>

<x-button variant="outline" type="submit">
    Add to Cart
</x-button>
```

---

## Current Pages

### ✅ Home Page

**File:** `resources/views/home.blade.php`

**Sections:**
1. **Hero** - Large headline, CTA buttons, minimal image
2. **Shop by Brand** - Grid of brand cards (5 per row on desktop)
3. **Featured Products** - 4-column product grid
4. **Trust Indicators** - 4 USP icons (Authentic, Free Shipping, Fast Delivery, Secure Payment)

**Container:** `max-w-8xl` (1600px)
**Spacing:** Clean borders between sections, py-20 vertical spacing

---

### ✅ Layout (app.blade.php)

**File:** `resources/views/layouts/app.blade.php`

**Header:**
- Sticky navigation
- Uppercase menu items (12px)
- Search icon
- Cart with counter badge
- 80px height
- Black announcement bar (12px uppercase)

**Footer:**
- Black background
- Logo + description
- Shop links
- Support links
- Newsletter subscription
- Social media icons
- Copyright

---

## Pages To Build Next

### 1. Product Detail Page

**File:** `resources/views/products/show.blade.php`

**Layout:**
```
Grid Layout (2 columns)

LEFT COLUMN:
- Large product image gallery
- Thumbnail navigation

RIGHT COLUMN:
- Brand name (uppercase, gray, 12px)
- Product title (32px, bold)
- Price (24px, bold)
- Quantity selector
- Add to Cart button (full width, black)
- Add to Wishlist button (outline)

FULL WIDTH BELOW:
- Product Description
- Fragrance Notes Section:
  - Top Notes (badges)
  - Middle Notes (badges)
  - Base Notes (badges)
- Main Accords (black badges)
- Product Details (table)

RELATED PRODUCTS:
- 4-column grid
- "You May Also Like" heading
```

**Key Components:**
- Use `<x-note-badge />` for notes
- Use `<x-accord-badge />` for accords
- Sticky purchase area on scroll (desktop)

---

### 2. Collection/Products Page

**File:** `resources/views/products/index.blade.php`

**Layout:**
```
Desktop: 2 columns (sidebar + grid)
Mobile: Stacked

LEFT SIDEBAR (20%):
- Filters heading (18px, bold, uppercase)
- Brand filter (checkboxes)
- Accord filter (checkboxes)
- Price range slider
- Apply/Clear buttons

MAIN CONTENT (80%):
- Sorting bar (dropdown, results count)
- Product grid (4 columns)
- Pagination (centered, clean numbers)
```

**Responsive:**
- Mobile: Filters in slide-out drawer
- Tablet: 3 columns
- Desktop: 4 columns

---

### 3. Checkout Page (UI Only)

**File:** `resources/views/checkout/index.blade.php`

**Layout:**
```
Desktop: 2 columns (60/40 split)

LEFT COLUMN:
1. Contact Information
   - Email input
2. Shipping Address
   - First Name, Last Name
   - Address, Apartment
   - City, Country, Postal Code
   - Phone
3. Shipping Method (radio buttons)
4. Payment Method (COD/Card)

RIGHT COLUMN (Sticky):
- Order Summary heading
- Product list (name, qty, price)
- Subtotal
- Shipping
- Discount field
- Total (large, bold)
- Place Order button (black, full width)
```

**Style:**
- Clean input fields (border-gray-300, rounded-luxury)
- Headings: 18px, bold, uppercase
- Mobile: Order summary at bottom

---

## Tailwind Config Extensions

**File:** `tailwind.config.js`

```javascript
colors: {
  brand: {
    primary: '#108474',  // Teal
    dark: '#232323',     // Black
    gray: '#969696',     // Gray
    light: '#F8F8F8',    // Light BG
    sale: '#e95144',     // Sale Red
  }
},
maxWidth: {
  '8xl': '1600px',       // adperfumes.ae container
},
borderRadius: {
  'luxury': '6px',       // Standard radius
},
letterSpacing: {
  'luxury': '0.05em',    // Uppercase spacing
},
```

---

## Component Usage Examples

### Product Grid (Home/Collection Pages)

```blade
<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
    @foreach($products as $product)
        <x-product-card :product="$product" />
    @endforeach
</div>
```

### Brand Grid

```blade
<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
    @foreach($brands as $brand)
        <x-brand-card :brand="$brand" />
    @endforeach
</div>
```

### Fragrance Notes Display

```blade
<div class="space-y-6">
    <!-- Top Notes -->
    <div>
        <h4 class="text-[14px] font-bold uppercase tracking-luxury mb-3">Top Notes</h4>
        <div class="flex flex-wrap gap-2">
            @foreach($product->topNotes as $note)
                <x-note-badge :note="$note" />
            @endforeach
        </div>
    </div>

    <!-- Middle Notes -->
    <div>
        <h4 class="text-[14px] font-bold uppercase tracking-luxury mb-3">Middle Notes</h4>
        <div class="flex flex-wrap gap-2">
            @foreach($product->middleNotes as $note)
                <x-note-badge :note="$note" />
            @endforeach
        </div>
    </div>

    <!-- Base Notes -->
    <div>
        <h4 class="text-[14px] font-bold uppercase tracking-luxury mb-3">Base Notes</h4>
        <div class="flex flex-wrap gap-2">
            @foreach($product->baseNotes as $note)
                <x-note-badge :note="$note" />
            @endforeach
        </div>
    </div>
</div>
```

### Main Accords Display

```blade
<div>
    <h4 class="text-[14px] font-bold uppercase tracking-luxury mb-3">Main Accords</h4>
    <div class="flex flex-wrap gap-2">
        @foreach($product->accords as $accord)
            <x-accord-badge :accord="$accord" />
        @endforeach
    </div>
</div>
```

---

## Design Patterns

### Section Structure

```blade
<section class="bg-white py-20 border-t border-gray-100">
    <div class="max-w-8xl mx-auto px-6 lg:px-10">
        <!-- Section heading -->
        <div class="text-center mb-12">
            <h2 class="text-[32px] lg:text-[40px] font-bold text-brand-dark uppercase tracking-tight mb-3">
                Section Title
            </h2>
            <p class="text-[14px] text-brand-gray">Subtitle goes here</p>
        </div>

        <!-- Section content -->
        <div class="grid ...">
            ...
        </div>
    </div>
</section>
```

### Form Input Pattern

```blade
<div>
    <label class="block text-[12px] font-medium text-brand-dark uppercase tracking-luxury mb-2">
        Label Text
    </label>
    <input type="text"
           class="w-full px-4 py-3 border border-gray-300 rounded-luxury text-[14px] focus:outline-none focus:border-black transition-colors"
           placeholder="Placeholder">
</div>
```

### Button Patterns

```blade
<!-- Primary CTA -->
<button class="bg-black text-white px-8 py-4 rounded-luxury text-[12px] font-medium uppercase tracking-luxury hover:bg-brand-primary transition-colors">
    Button Text
</button>

<!-- Outline Button -->
<button class="bg-white text-brand-dark border border-black px-6 py-3 rounded-luxury text-[12px] font-medium uppercase tracking-luxury hover:bg-black hover:text-white transition-colors">
    Button Text
</button>
```

---

## Responsive Breakpoints

```
sm:  640px   (Tablet)
md:  768px   (Tablet Large)
lg:  1024px  (Desktop)
xl:  1280px  (Large Desktop)
2xl: 1536px  (Extra Large)
```

**Grid Responsive Pattern:**
```blade
grid-cols-2 md:grid-cols-3 lg:grid-cols-4
```

---

## Performance Considerations

✅ **Implemented:**
- Minimal CSS (Tailwind purges unused classes)
- No heavy JavaScript libraries
- Lazy loading images (via native loading="lazy")
- Optimized web fonts (Instrument Sans via Google Fonts)
- Clean, semantic HTML

🔄 **To Implement:**
- Image optimization (WebP format)
- CDN for static assets
- Browser caching headers
- Gzip compression

---

## Next Steps

### Immediate Tasks:
1. Build Product Detail Page
   - Image gallery with zoom
   - Fragrance notes section
   - Related products

2. Build Collection/Products Page
   - Filter sidebar
   - Product grid with pagination
   - Sorting functionality

3. Build Checkout Page UI
   - Contact form
   - Shipping address
   - Order summary sidebar

### Future Enhancements:
- Mobile navigation menu (Alpine.js)
- Search overlay (Alpine.js)
- Quick view modal for products
- Image zoom on product detail
- Cart drawer (Alpine.js)
- Wishlist functionality
- Product reviews section

---

## File Structure

```
resources/
├── views/
│   ├── layouts/
│   │   └── app.blade.php          ✅ Updated with luxury aesthetic
│   ├── components/
│   │   ├── product-card.blade.php  ✅ Created
│   │   ├── brand-card.blade.php    ✅ Created
│   │   ├── note-badge.blade.php    ✅ Created
│   │   ├── accord-badge.blade.php  ✅ Created
│   │   └── button.blade.php        ✅ Created
│   ├── home.blade.php              ✅ Redesigned
│   ├── products/
│   │   ├── index.blade.php         ⏳ To build
│   │   ├── show.blade.php          ⏳ To build
│   │   └── by-brand.blade.php      ⏳ To build
│   ├── cart/
│   │   └── index.blade.php         📝 Exists (needs redesign)
│   └── checkout/
│       └── index.blade.php         📝 Exists (needs redesign)
```

---

## Testing Checklist

### Visual Testing:
- [ ] Check all breakpoints (mobile, tablet, desktop)
- [ ] Verify font sizes match spec
- [ ] Test hover states on all interactive elements
- [ ] Verify color contrast (WCAG AA)
- [ ] Check spacing consistency

### Functional Testing:
- [ ] Navigation links work
- [ ] Product cards link correctly
- [ ] Brand cards link correctly
- [ ] Add to cart functionality
- [ ] Form inputs accept data
- [ ] Buttons submit forms

### Cross-Browser Testing:
- [ ] Chrome
- [ ] Firefox
- [ ] Safari
- [ ] Edge
- [ ] Mobile Safari
- [ ] Mobile Chrome

---

## Build Commands

```bash
# Development
npm run dev

# Production build
npm run build

# Watch mode (auto-rebuild on changes)
npm run dev
```

---

## Questions?

For design questions or component requests, refer to this document.

**Design Reference:** adperfumes.ae aesthetic patterns
**Components:** `resources/views/components/`
**Tailwind Config:** `tailwind.config.js`
