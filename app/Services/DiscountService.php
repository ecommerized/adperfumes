<?php

namespace App\Services;

use App\Models\Discount;
use Illuminate\Support\Facades\Log;

/**
 * DiscountService
 *
 * Handles discount code validation and calculation
 */
class DiscountService
{
    /**
     * Validate and calculate discount
     *
     * @param string $code Discount code
     * @param float $subtotal Cart subtotal
     * @param string|null $userEmail User email (for usage tracking)
     * @return array
     */
    public function applyDiscount(string $code, float $subtotal, ?string $userEmail = null): array
    {
        try {
            // Normalize code (uppercase, trim)
            $code = strtoupper(trim($code));

            // Find discount by code
            $discount = Discount::where('code', $code)->first();

            if (!$discount) {
                return [
                    'valid' => false,
                    'discount_amount' => 0,
                    'discount_id' => null,
                    'message' => 'Invalid discount code',
                ];
            }

            // Validate discount
            $validation = $discount->isValid($subtotal, $userEmail);

            if (!$validation['valid']) {
                return [
                    'valid' => false,
                    'discount_amount' => 0,
                    'discount_id' => null,
                    'message' => $validation['message'],
                ];
            }

            // Calculate discount amount
            $discountAmount = $discount->calculateDiscount($subtotal);

            return [
                'valid' => true,
                'discount_amount' => round($discountAmount, 2),
                'discount_id' => $discount->id,
                'discount_code' => $discount->code,
                'discount_type' => $discount->type,
                'discount_value' => $discount->value,
                'message' => $validation['message'],
            ];

        } catch (\Exception $e) {
            Log::error('Discount Service Exception', [
                'code' => $code,
                'error' => $e->getMessage(),
            ]);

            return [
                'valid' => false,
                'discount_amount' => 0,
                'discount_id' => null,
                'message' => 'Error processing discount code',
            ];
        }
    }

    /**
     * Increment discount usage after successful order
     *
     * @param string $code
     * @return void
     */
    public function incrementUsage(string $code): void
    {
        try {
            $discount = Discount::where('code', strtoupper(trim($code)))->first();

            if ($discount) {
                $discount->incrementUsage();

                Log::info('Discount Usage Incremented', [
                    'code' => $code,
                    'current_uses' => $discount->current_uses,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Discount Increment Exception', [
                'code' => $code,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get all active discounts
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActiveDiscounts()
    {
        return Discount::active()->available()->get();
    }

    /**
     * Validate discount code (without applying)
     *
     * @param string $code
     * @param float $subtotal
     * @return array
     */
    public function validateCode(string $code, float $subtotal): array
    {
        return $this->applyDiscount($code, $subtotal);
    }
}
