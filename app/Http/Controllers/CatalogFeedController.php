<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class CatalogFeedController extends Controller
{
    /**
     * Google Merchant Center XML feed.
     * Includes ALL products with proper availability & inventory data.
     */
    public function google(): Response
    {
        $products = Product::where('status', true)
            ->with('brand', 'categories')
            ->get();

        $siteUrl = url('/');

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">' . "\n";
        $xml .= '<channel>' . "\n";
        $xml .= '<title>AD Perfumes Product Feed</title>' . "\n";
        $xml .= '<link>' . $siteUrl . '</link>' . "\n";
        $xml .= '<description>Luxury fragrances from AD Perfumes - UAE</description>' . "\n";

        foreach ($products as $product) {
            // Skip products without images (Google requires image_link)
            if (empty($product->image)) {
                continue;
            }

            $imageUrl = $this->getImageUrl($product->image);

            $xml .= '<item>' . "\n";

            // Required: Product identity
            $xml .= '<g:id>ADP-' . $product->id . '</g:id>' . "\n";
            $xml .= '<g:title>' . htmlspecialchars($product->name) . '</g:title>' . "\n";

            // Description: clean HTML, min 70 chars for Google
            $description = strip_tags($product->description ?? '');
            if (mb_strlen($description) < 70) {
                $description = $product->name . ' - Authentic luxury fragrance available at AD Perfumes UAE. Shop online for the best perfumes in Dubai, Abu Dhabi & Sharjah.';
            }
            $xml .= '<g:description>' . htmlspecialchars(mb_substr($description, 0, 5000)) . '</g:description>' . "\n";

            // Required: Product URL
            $xml .= '<g:link>' . route('products.show', $product->slug) . '</g:link>' . "\n";

            // Required: Image - use direct URL (Google supports JPEG, PNG, GIF, WebP, BMP, TIFF)
            $xml .= '<g:image_link>' . $imageUrl . '</g:image_link>' . "\n";

            // Additional images from gallery
            if (!empty($product->gallery_images)) {
                $additionalCount = 0;
                foreach ($product->gallery_images as $galleryImage) {
                    if ($additionalCount >= 10) break;
                    if (!empty($galleryImage)) {
                        $xml .= '<g:additional_image_link>' . $this->getImageUrl($galleryImage) . '</g:additional_image_link>' . "\n";
                        $additionalCount++;
                    }
                }
            }

            // Required: Availability based on actual stock
            $xml .= '<g:availability>' . ($product->stock > 0 ? 'in_stock' : 'out_of_stock') . '</g:availability>' . "\n";

            // Required: Price
            if ($product->on_sale && $product->original_price && $product->original_price > $product->price) {
                // When on sale: price = original, sale_price = current
                $xml .= '<g:price>' . number_format($product->original_price, 2, '.', '') . ' AED</g:price>' . "\n";
                $xml .= '<g:sale_price>' . number_format($product->price, 2, '.', '') . ' AED</g:sale_price>' . "\n";
            } else {
                $xml .= '<g:price>' . number_format($product->price, 2, '.', '') . ' AED</g:price>' . "\n";
            }

            // Required: Brand
            $xml .= '<g:brand>' . htmlspecialchars($product->brand->name ?? 'AD Perfumes') . '</g:brand>' . "\n";
            $xml .= '<g:condition>new</g:condition>' . "\n";

            // Product identifiers (GTIN or MPN)
            if ($product->gtin) {
                $xml .= '<g:gtin>' . htmlspecialchars($product->gtin) . '</g:gtin>' . "\n";
            } else {
                $xml .= '<g:mpn>ADP-' . $product->id . '</g:mpn>' . "\n";
                $xml .= '<g:identifier_exists>false</g:identifier_exists>' . "\n";
            }

            // Category / Product type
            if ($product->categories->isNotEmpty()) {
                $xml .= '<g:product_type>' . htmlspecialchars($product->categories->first()->name) . '</g:product_type>' . "\n";
            }
            $xml .= '<g:google_product_category>Health &amp; Beauty &gt; Personal Care &gt; Cosmetics &gt; Perfume &amp; Cologne</g:google_product_category>' . "\n";

            // Gender targeting
            if ($product->gender) {
                $genderMap = ['men' => 'male', 'women' => 'female', 'unisex' => 'unisex'];
                $xml .= '<g:gender>' . ($genderMap[$product->gender] ?? 'unisex') . '</g:gender>' . "\n";
            }

            // Age group (perfumes are adult products)
            $xml .= '<g:age_group>adult</g:age_group>' . "\n";

            // Shipping: UAE targeting
            $xml .= '<g:shipping>' . "\n";
            $xml .= '  <g:country>AE</g:country>' . "\n";
            $xml .= '  <g:service>Standard</g:service>' . "\n";
            $xml .= '  <g:price>0.00 AED</g:price>' . "\n";
            $xml .= '</g:shipping>' . "\n";

            $xml .= '</item>' . "\n";
        }

        $xml .= '</channel>' . "\n";
        $xml .= '</rss>';

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }

    /**
     * Get the public URL for an image path.
     * Handles both storage paths and full URLs.
     */
    private function getImageUrl(string $imagePath): string
    {
        // If it's already a full URL, return as-is
        if (str_starts_with($imagePath, 'http://') || str_starts_with($imagePath, 'https://')) {
            return $imagePath;
        }

        return url(Storage::url($imagePath));
    }

    /**
     * Meta (Facebook/Instagram) catalog CSV feed.
     */
    public function meta(): Response
    {
        $products = Product::where('status', true)
            ->with('brand', 'categories')
            ->get();

        $csv = "id,title,description,availability,condition,price,link,image_link,brand,sale_price,product_type,gtin,quantity_to_sell_on_facebook,inventory\n";

        foreach ($products as $product) {
            $fields = [
                $product->id,
                $this->csvEscape($product->name),
                $this->csvEscape(strip_tags($product->description ?? $product->name)),
                $product->stock > 0 ? 'in stock' : 'out of stock',
                'new',
                number_format($product->price, 2, '.', '') . ' AED',
                route('products.show', $product->slug),
                $product->image ? $this->getImageUrl($product->image) : '',
                $this->csvEscape($product->brand->name ?? 'AD Perfumes'),
                ($product->on_sale && $product->original_price && $product->original_price > $product->price) ? number_format($product->price, 2, '.', '') . ' AED' : '',
                $product->categories->isNotEmpty() ? $this->csvEscape($product->categories->first()->name) : 'Perfume',
                $product->gtin ?? '',
                (int) $product->stock,
                (int) $product->stock,
            ];

            $csv .= implode(',', $fields) . "\n";
        }

        return response($csv, 200)->header('Content-Type', 'text/csv');
    }

    /**
     * TikTok catalog CSV feed.
     */
    public function tiktok(): Response
    {
        $products = Product::where('status', true)
            ->with('brand', 'categories')
            ->get();

        $csv = "sku_id,title,description,availability,condition,price,link,image_link,brand,sale_price,product_type,gtin,quantity\n";

        foreach ($products as $product) {
            $fields = [
                'ADP-' . $product->id,
                $this->csvEscape($product->name),
                $this->csvEscape(strip_tags($product->description ?? $product->name)),
                $product->stock > 0 ? 'IN_STOCK' : 'OUT_OF_STOCK',
                'NEW',
                number_format($product->price, 2, '.', '') . ' AED',
                route('products.show', $product->slug),
                $product->image ? $this->getImageUrl($product->image) : '',
                $this->csvEscape($product->brand->name ?? 'AD Perfumes'),
                ($product->on_sale && $product->original_price && $product->original_price > $product->price) ? number_format($product->price, 2, '.', '') . ' AED' : '',
                $product->categories->isNotEmpty() ? $this->csvEscape($product->categories->first()->name) : 'Perfume',
                $product->gtin ?? '',
                (int) $product->stock,
            ];

            $csv .= implode(',', $fields) . "\n";
        }

        return response($csv, 200)->header('Content-Type', 'text/csv');
    }

    /**
     * Snapchat catalog CSV feed.
     */
    public function snapchat(): Response
    {
        $products = Product::where('status', true)
            ->with('brand', 'categories')
            ->get();

        $csv = "id,title,description,availability,condition,price,link,image_link,brand,sale_price,product_type,gtin,inventory\n";

        foreach ($products as $product) {
            $fields = [
                $product->id,
                $this->csvEscape($product->name),
                $this->csvEscape(strip_tags($product->description ?? $product->name)),
                $product->stock > 0 ? 'in stock' : 'out of stock',
                'new',
                number_format($product->price, 2, '.', '') . ' AED',
                route('products.show', $product->slug),
                $product->image ? $this->getImageUrl($product->image) : '',
                $this->csvEscape($product->brand->name ?? 'AD Perfumes'),
                ($product->on_sale && $product->original_price && $product->original_price > $product->price) ? number_format($product->price, 2, '.', '') . ' AED' : '',
                $product->categories->isNotEmpty() ? $this->csvEscape($product->categories->first()->name) : 'Perfume',
                $product->gtin ?? '',
                (int) $product->stock,
            ];

            $csv .= implode(',', $fields) . "\n";
        }

        return response($csv, 200)->header('Content-Type', 'text/csv');
    }

    private function csvEscape(string $value): string
    {
        $value = str_replace('"', '""', $value);
        return '"' . $value . '"';
    }
}
