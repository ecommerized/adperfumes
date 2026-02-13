<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class CatalogFeedController extends Controller
{
    /**
     * Google Merchant Center XML feed.
     */
    public function google(): Response
    {
        $products = Product::where('status', true)->with('brand', 'categories')->get();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">';
        $xml .= '<channel>';
        $xml .= '<title>AD Perfumes Product Feed</title>';
        $xml .= '<link>' . url('/') . '</link>';
        $xml .= '<description>Luxury fragrances from AD Perfumes</description>';

        foreach ($products as $product) {
            $xml .= '<item>';
            $xml .= '<g:id>' . $product->id . '</g:id>';
            $xml .= '<g:title>' . htmlspecialchars($product->name) . '</g:title>';
            $xml .= '<g:description>' . htmlspecialchars(strip_tags($product->description ?? $product->name)) . '</g:description>';
            $xml .= '<g:link>' . route('products.show', $product->slug) . '</g:link>';

            if ($product->image) {
                $xml .= '<g:image_link>' . url(Storage::url($product->image)) . '</g:image_link>';
            }

            $xml .= '<g:availability>' . ($product->stock > 0 ? 'in_stock' : 'out_of_stock') . '</g:availability>';
            $xml .= '<g:price>' . number_format($product->price, 2, '.', '') . ' AED</g:price>';

            if ($product->on_sale && $product->original_price) {
                $xml .= '<g:sale_price>' . number_format($product->price, 2, '.', '') . ' AED</g:sale_price>';
            }

            $xml .= '<g:brand>' . htmlspecialchars($product->brand->name ?? 'AD Perfumes') . '</g:brand>';
            $xml .= '<g:condition>new</g:condition>';

            if ($product->gtin) {
                $xml .= '<g:gtin>' . htmlspecialchars($product->gtin) . '</g:gtin>';
            } else {
                $xml .= '<g:identifier_exists>false</g:identifier_exists>';
            }

            if ($product->categories->isNotEmpty()) {
                $xml .= '<g:product_type>' . htmlspecialchars($product->categories->first()->name) . '</g:product_type>';
            }

            $xml .= '<g:google_product_category>Health &amp; Beauty &gt; Personal Care &gt; Cosmetics &gt; Perfume &amp; Cologne</g:google_product_category>';

            $xml .= '</item>';
        }

        $xml .= '</channel>';
        $xml .= '</rss>';

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }

    /**
     * Meta (Facebook/Instagram) catalog CSV feed.
     */
    public function meta(): Response
    {
        $products = Product::where('status', true)->with('brand', 'categories')->get();

        $csv = "id,title,description,availability,condition,price,link,image_link,brand,sale_price,product_type,gtin\n";

        foreach ($products as $product) {
            $fields = [
                $product->id,
                $this->csvEscape($product->name),
                $this->csvEscape(strip_tags($product->description ?? $product->name)),
                $product->stock > 0 ? 'in stock' : 'out of stock',
                'new',
                number_format($product->price, 2, '.', '') . ' AED',
                route('products.show', $product->slug),
                $product->image ? url(Storage::url($product->image)) : '',
                $this->csvEscape($product->brand->name ?? 'AD Perfumes'),
                ($product->on_sale && $product->original_price) ? number_format($product->price, 2, '.', '') . ' AED' : '',
                $product->categories->isNotEmpty() ? $this->csvEscape($product->categories->first()->name) : 'Perfume',
                $product->gtin ?? '',
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
        $products = Product::where('status', true)->with('brand', 'categories')->get();

        $csv = "sku_id,title,description,availability,condition,price,link,image_link,brand,sale_price,product_type,gtin\n";

        foreach ($products as $product) {
            $fields = [
                'ADP-' . $product->id,
                $this->csvEscape($product->name),
                $this->csvEscape(strip_tags($product->description ?? $product->name)),
                $product->stock > 0 ? 'IN_STOCK' : 'OUT_OF_STOCK',
                'NEW',
                number_format($product->price, 2, '.', '') . ' AED',
                route('products.show', $product->slug),
                $product->image ? url(Storage::url($product->image)) : '',
                $this->csvEscape($product->brand->name ?? 'AD Perfumes'),
                ($product->on_sale && $product->original_price) ? number_format($product->price, 2, '.', '') . ' AED' : '',
                $product->categories->isNotEmpty() ? $this->csvEscape($product->categories->first()->name) : 'Perfume',
                $product->gtin ?? '',
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
        $products = Product::where('status', true)->with('brand', 'categories')->get();

        $csv = "id,title,description,availability,condition,price,link,image_link,brand,sale_price,product_type,gtin\n";

        foreach ($products as $product) {
            $fields = [
                $product->id,
                $this->csvEscape($product->name),
                $this->csvEscape(strip_tags($product->description ?? $product->name)),
                $product->stock > 0 ? 'in stock' : 'out of stock',
                'new',
                number_format($product->price, 2, '.', '') . ' AED',
                route('products.show', $product->slug),
                $product->image ? url(Storage::url($product->image)) : '',
                $this->csvEscape($product->brand->name ?? 'AD Perfumes'),
                ($product->on_sale && $product->original_price) ? number_format($product->price, 2, '.', '') . ' AED' : '',
                $product->categories->isNotEmpty() ? $this->csvEscape($product->categories->first()->name) : 'Perfume',
                $product->gtin ?? '',
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
