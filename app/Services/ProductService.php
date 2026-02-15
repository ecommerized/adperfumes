<?php

namespace App\Services;

use App\Models\Product;

class ProductService
{
    /**
     * Calculate tax breakdown from a tax-inclusive price.
     *
     * @return array{price_excluding_tax: float, tax_amount: float, tax_rate: float}
     */
    public function calculateTaxBreakdown(float $priceInclTax, float $taxRate = 5.00): array
    {
        if ($priceInclTax <= 0) {
            return [
                'price_excluding_tax' => 0,
                'tax_amount' => 0,
                'tax_rate' => $taxRate,
            ];
        }

        $priceExclTax = round($priceInclTax / (1 + ($taxRate / 100)), 2);
        $taxAmount = round($priceInclTax - $priceExclTax, 2);

        return [
            'price_excluding_tax' => $priceExclTax,
            'tax_amount' => $taxAmount,
            'tax_rate' => $taxRate,
        ];
    }

    /**
     * Sync tax fields on a product (auto-fills derived tax fields).
     */
    public function syncTaxFields(Product $product): void
    {
        $taxRate = $product->tax_rate ?? 5.00;
        $priceInclTax = (float) $product->price;

        if ($priceInclTax > 0) {
            $breakdown = $this->calculateTaxBreakdown($priceInclTax, $taxRate);
            $product->price_excluding_tax = $breakdown['price_excluding_tax'];
            $product->tax_amount = $breakdown['tax_amount'];
            $product->tax_rate = $breakdown['tax_rate'];
        }
    }
}
