<?php

namespace App\Services;

/**
 * CheckoutCalculator Service
 *
 * Handles all checkout calculations:
 * - Subtotal
 * - Shipping amount
 * - Discount amount
 * - Grand total
 *
 * IMPORTANT: All totals MUST be calculated server-side only.
 * Frontend/mobile apps should never calculate totals.
 */
class CheckoutCalculator
{
    protected $discountService;

    public function __construct(DiscountService $discountService)
    {
        $this->discountService = $discountService;
    }
    /**
     * Calculate checkout totals
     *
     * @param array $cartItems Array of cart items with ['product_id', 'quantity', 'price']
     * @param float|null $shippingAmount Shipping cost (from Aramex API)
     * @param string|null $discountCode Discount code if applied
     * @return array ['subtotal', 'shipping', 'discount', 'grand_total', 'discount_info']
     */
    public function calculateTotals(array $cartItems, ?float $shippingAmount = null, ?string $discountCode = null): array
    {
        // Calculate subtotal
        $subtotal = $this->calculateSubtotal($cartItems);

        // Shipping
        $shipping = $shippingAmount ?? 0;

        // Apply discount if code provided
        $discount = 0;
        $discountInfo = null;

        if ($discountCode) {
            $discountResult = $this->discountService->applyDiscount($discountCode, $subtotal);

            if ($discountResult['valid']) {
                $discount = $discountResult['discount_amount'];
                $discountInfo = $discountResult;
            }
        }

        // Grand total (subtotal + shipping - discount)
        $grandTotal = $subtotal + $shipping - $discount;

        return [
            'subtotal' => round($subtotal, 2),
            'shipping' => round($shipping, 2),
            'discount' => round($discount, 2),
            'grand_total' => round($grandTotal, 2),
            'discount_info' => $discountInfo,
        ];
    }

    /**
     * Calculate cart subtotal
     */
    private function calculateSubtotal(array $cartItems): float
    {
        $subtotal = 0;

        foreach ($cartItems as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }

        return $subtotal;
    }
}
