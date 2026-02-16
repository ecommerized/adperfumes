<?php

namespace App\Services;

use App\Models\Order;

/**
 * PaymentFeeService
 *
 * Calculates payment gateway fees and platform fees based on Tap Payments agreement.
 *
 * Fee Structure (from Tap Agreement):
 * - Platform Fee: 0.25% per transaction
 * - Local Visa/Mastercard: 2.25% + 1 AED
 * - Regional Visa/Mastercard: 2.55% + 1 AED
 * - International Visa/Mastercard: 2.55% + 1 AED
 * - Amex: 3.2% + 1 AED
 * - Tabby (BNPL): 6.5% + 1 AED
 * - Tamara (BNPL): Estimated 3.5% + 0 AED
 */
class PaymentFeeService
{
    /**
     * Tap platform fee percentage (applies to all transactions)
     */
    const PLATFORM_FEE_PERCENTAGE = 0.25;

    /**
     * Payment gateway fee structures by method and card type
     */
    const PAYMENT_FEES = [
        'tap' => [
            'local_visa' => ['percentage' => 2.25, 'fixed' => 1.00],
            'local_mastercard' => ['percentage' => 2.25, 'fixed' => 1.00],
            'regional_visa' => ['percentage' => 2.55, 'fixed' => 1.00],
            'regional_mastercard' => ['percentage' => 2.55, 'fixed' => 1.00],
            'international_visa' => ['percentage' => 2.55, 'fixed' => 1.00],
            'international_mastercard' => ['percentage' => 2.55, 'fixed' => 1.00],
            'amex' => ['percentage' => 3.2, 'fixed' => 1.00],
            'default' => ['percentage' => 2.25, 'fixed' => 1.00], // fallback to local rate
        ],
        'tabby' => [
            'bnpl' => ['percentage' => 6.5, 'fixed' => 1.00],
        ],
        'tamara' => [
            'bnpl' => ['percentage' => 3.5, 'fixed' => 0.00],
        ],
        'cod' => [
            'cash' => ['percentage' => 0.00, 'fixed' => 0.00], // no payment gateway fees for COD
        ],
    ];

    /**
     * Calculate payment gateway fee for a transaction
     *
     * @param float $amount Transaction amount (tax-inclusive)
     * @param string $paymentMethod 'tap', 'tabby', 'tamara', 'cod'
     * @param string|null $cardType 'local_visa', 'regional_mastercard', 'amex', etc.
     * @return array ['percentage_fee' => float, 'fixed_fee' => float, 'total_fee' => float, 'rate_applied' => float]
     */
    public function calculatePaymentGatewayFee(
        float $amount,
        string $paymentMethod = 'tap',
        ?string $cardType = null
    ): array {
        // Get fee structure for this payment method
        $methodFees = self::PAYMENT_FEES[$paymentMethod] ?? self::PAYMENT_FEES['tap'];

        // Determine card type (default to 'default' or first available)
        $feeStructure = null;

        if ($cardType && isset($methodFees[$cardType])) {
            $feeStructure = $methodFees[$cardType];
        } elseif (isset($methodFees['default'])) {
            $feeStructure = $methodFees['default'];
        } elseif (isset($methodFees['bnpl'])) {
            $feeStructure = $methodFees['bnpl'];
        } else {
            // Fallback: use first available fee structure
            $feeStructure = reset($methodFees);
        }

        // Calculate fees
        $percentageFee = round($amount * $feeStructure['percentage'] / 100, 2);
        $fixedFee = $feeStructure['fixed'];
        $totalFee = round($percentageFee + $fixedFee, 2);

        return [
            'percentage_fee' => $percentageFee,
            'fixed_fee' => $fixedFee,
            'total_fee' => $totalFee,
            'rate_applied' => $feeStructure['percentage'],
        ];
    }

    /**
     * Calculate platform fee (Tap's 0.25% charge)
     *
     * @param float $amount Transaction amount
     * @return float Platform fee amount
     */
    public function calculatePlatformFee(float $amount): float
    {
        return round($amount * self::PLATFORM_FEE_PERCENTAGE / 100, 2);
    }

    /**
     * Calculate all fees for an order (gateway + platform)
     *
     * @param float $orderTotal Order grand total (tax-inclusive)
     * @param string $paymentMethod Payment method used
     * @param string|null $cardType Card type if known
     * @return array Complete fee breakdown
     */
    public function calculateAllFees(
        float $orderTotal,
        string $paymentMethod = 'tap',
        ?string $cardType = null
    ): array {
        // Calculate payment gateway fee
        $gatewayFee = $this->calculatePaymentGatewayFee($orderTotal, $paymentMethod, $cardType);

        // Calculate platform fee
        $platformFee = $this->calculatePlatformFee($orderTotal);

        // Total fees deducted
        $totalFees = $gatewayFee['total_fee'] + $platformFee;

        // Net amount after fees
        $netAmount = round($orderTotal - $totalFees, 2);

        return [
            'order_total' => $orderTotal,
            'gateway_fee_percentage' => $gatewayFee['rate_applied'],
            'gateway_fee_fixed' => $gatewayFee['fixed_fee'],
            'gateway_fee_total' => $gatewayFee['total_fee'],
            'platform_fee_percentage' => self::PLATFORM_FEE_PERCENTAGE,
            'platform_fee_amount' => $platformFee,
            'total_fees' => $totalFees,
            'net_amount_after_fees' => $netAmount,
        ];
    }

    /**
     * Determine card type from payment response
     * (This would be called from payment gateway webhook handlers)
     *
     * @param array $paymentResponse Payment gateway response
     * @return string Card type identifier
     */
    public function determineCardType(array $paymentResponse): string
    {
        // For Tap Payments
        if (isset($paymentResponse['source']['payment_method'])) {
            $scheme = strtolower($paymentResponse['source']['payment_method'] ?? '');
            $country = strtoupper($paymentResponse['source']['country'] ?? '');

            // Determine if card is local, regional, or international
            $cardRegion = $this->determineCardRegion($country);

            // Map to our card type
            if ($scheme === 'american_express' || $scheme === 'amex') {
                return 'amex';
            }

            if (in_array($scheme, ['visa', 'mastercard', 'mada'])) {
                return $cardRegion . '_' . $scheme;
            }
        }

        // For Tabby/Tamara
        if (isset($paymentResponse['payment_type']) && $paymentResponse['payment_type'] === 'installments') {
            return 'bnpl';
        }

        // Default fallback
        return 'local_visa';
    }

    /**
     * Determine if card is local, regional, or international
     *
     * @param string $countryCode ISO country code
     * @return string 'local', 'regional', or 'international'
     */
    private function determineCardRegion(string $countryCode): string
    {
        // UAE cards
        if ($countryCode === 'AE') {
            return 'local';
        }

        // GCC countries (regional)
        $gccCountries = ['SA', 'KW', 'BH', 'OM', 'QA'];
        if (in_array($countryCode, $gccCountries)) {
            return 'regional';
        }

        // All others are international
        return 'international';
    }

    /**
     * Update order with payment fee details
     *
     * @param Order $order
     * @param array $paymentResponse Payment gateway response (from webhook)
     * @return Order
     */
    public function updateOrderPaymentFees(Order $order, array $paymentResponse = []): Order
    {
        // Determine card type
        $cardType = !empty($paymentResponse)
            ? $this->determineCardType($paymentResponse)
            : 'local_visa'; // default

        // Determine payment method
        $paymentMethod = $order->payment_method ?? 'tap';

        // Calculate fees
        $fees = $this->calculateAllFees($order->grand_total, $paymentMethod, $cardType);

        // Extract card details from payment response
        $cardScheme = null;
        $issuerCountry = null;

        if (isset($paymentResponse['source'])) {
            $cardScheme = $paymentResponse['source']['payment_method'] ?? null;
            $issuerCountry = $paymentResponse['source']['country'] ?? null;
        }

        // Update order
        $order->update([
            'payment_card_type' => $cardType,
            'payment_card_scheme' => $cardScheme,
            'payment_card_issuer_country' => $issuerCountry,
            'payment_gateway_percentage' => $fees['gateway_fee_percentage'],
            'payment_gateway_fixed_fee' => $fees['gateway_fee_fixed'],
            'payment_gateway_fee_total' => $fees['gateway_fee_total'],
            'platform_fee_percentage' => $fees['platform_fee_percentage'],
            'platform_fee_amount' => $fees['platform_fee_amount'],
            'net_amount_after_fees' => $fees['net_amount_after_fees'],
        ]);

        return $order->fresh();
    }

    /**
     * Get fee summary for display
     *
     * @param Order $order
     * @return array
     */
    public function getFeeSummary(Order $order): array
    {
        return [
            'order_total' => number_format($order->grand_total, 2),
            'payment_gateway_fee' => number_format($order->payment_gateway_fee_total ?? 0, 2),
            'platform_fee' => number_format($order->platform_fee_amount ?? 0, 2),
            'total_fees' => number_format(
                ($order->payment_gateway_fee_total ?? 0) + ($order->platform_fee_amount ?? 0),
                2
            ),
            'net_after_fees' => number_format($order->net_amount_after_fees ?? $order->grand_total, 2),
            'fee_rate' => ($order->payment_gateway_percentage ?? 0) . '% + ' . ($order->payment_gateway_fixed_fee ?? 0) . ' AED',
        ];
    }
}
