<?php

namespace App\Payments;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Tabby Payment Service
 *
 * Buy Now Pay Later (BNPL) payment gateway integration
 * Allows customers to split payments into 4 interest-free installments
 */
class TabbyPayment
{
    protected string $apiUrl;
    protected string $publicKey;
    protected string $secretKey;
    protected string $merchantCode;
    protected bool $isLive;

    public function __construct()
    {
        $this->publicKey = config('services.tabby.public_key');
        $this->secretKey = config('services.tabby.secret_key');
        $this->merchantCode = config('services.tabby.merchant_code');
        $this->isLive = config('services.tabby.is_live', false);

        $this->apiUrl = $this->isLive
            ? 'https://api.tabby.ai/api/v2'
            : 'https://api.tabby.dev/api/v2';
    }

    /**
     * Create Tabby payment session
     *
     * @param array $orderData Order details
     * @return array
     */
    public function createPayment(array $orderData): array
    {
        try {
            // If no credentials configured, return test mode response
            if (empty($this->publicKey) || empty($this->secretKey)) {
                return [
                    'success' => false,
                    'error' => 'Tabby credentials not configured. Please set up your Tabby account.',
                    'test_mode' => true,
                ];
            }

            $payload = [
                'payment' => [
                    'amount' => $this->formatAmount($orderData['amount']),
                    'currency' => $orderData['currency'],
                    'description' => $orderData['description'],
                    'buyer' => [
                        'phone' => $orderData['phone'],
                        'email' => $orderData['email'],
                        'name' => $orderData['name'],
                    ],
                    'shipping_address' => [
                        'city' => $orderData['city'],
                        'address' => $orderData['address'],
                        'zip' => $orderData['postal_code'] ?? '',
                    ],
                    'order' => [
                        'tax_amount' => '0.00',
                        'shipping_amount' => $this->formatAmount($orderData['shipping'] ?? 0),
                        'discount_amount' => $this->formatAmount($orderData['discount'] ?? 0),
                        'reference_id' => $orderData['order_number'],
                    ],
                    'order_history' => [
                        [
                            'purchased_at' => now()->toIso8601String(),
                            'amount' => $this->formatAmount($orderData['amount']),
                            'status' => 'new',
                        ],
                    ],
                ],
                'lang' => 'en',
                'merchant_code' => $this->merchantCode,
                'merchant_urls' => [
                    'success' => $orderData['success_url'],
                    'cancel' => $orderData['cancel_url'],
                    'failure' => $orderData['failure_url'],
                ],
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->publicKey,
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl . '/checkout', $payload);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['configuration']['available_products']['installments'])) {
                    return [
                        'success' => true,
                        'payment_id' => $data['payment']['id'] ?? null,
                        'redirect_url' => $data['configuration']['available_products']['installments'][0]['web_url'] ?? null,
                        'installments' => $data['configuration']['available_products']['installments'] ?? [],
                    ];
                }
            }

            Log::error('Tabby Payment Failed', [
                'response' => $response->json(),
                'status' => $response->status(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to create Tabby payment session',
            ];

        } catch (Exception $e) {
            Log::error('Tabby Payment Exception', [
                'message' => $e->getMessage(),
                'order' => $orderData['order_number'] ?? 'Unknown',
            ]);

            return [
                'success' => false,
                'error' => 'Payment gateway error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Retrieve payment details
     *
     * @param string $paymentId Tabby payment ID
     * @return array
     */
    public function getPayment(string $paymentId): array
    {
        try {
            if (empty($this->secretKey)) {
                return [
                    'success' => false,
                    'error' => 'Tabby credentials not configured',
                ];
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
            ])->get($this->apiUrl . '/payments/' . $paymentId);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'payment_id' => $data['id'] ?? null,
                    'status' => $data['status'] ?? 'unknown',
                    'amount' => $data['amount'] ?? 0,
                    'currency' => $data['currency'] ?? 'AED',
                    'order_id' => $data['order']['reference_id'] ?? null,
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to retrieve payment details',
            ];

        } catch (Exception $e) {
            Log::error('Tabby Get Payment Exception', [
                'payment_id' => $paymentId,
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Error retrieving payment: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Capture payment (after customer completes installment setup)
     *
     * @param string $paymentId Tabby payment ID
     * @param float $amount Amount to capture
     * @return array
     */
    public function capturePayment(string $paymentId, float $amount): array
    {
        try {
            if (empty($this->secretKey)) {
                return [
                    'success' => false,
                    'error' => 'Tabby credentials not configured',
                ];
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->secretKey,
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl . '/payments/' . $paymentId . '/captures', [
                'amount' => $this->formatAmount($amount),
            ]);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'capture_id' => $data['id'] ?? null,
                    'amount' => $data['amount'] ?? 0,
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to capture payment',
            ];

        } catch (Exception $e) {
            Log::error('Tabby Capture Payment Exception', [
                'payment_id' => $paymentId,
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Capture error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Format amount to string with 2 decimals
     *
     * @param float $amount
     * @return string
     */
    protected function formatAmount(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }

    /**
     * Check if Tabby is available for this amount
     *
     * @param float $amount Order amount
     * @param string $currency Currency code
     * @return bool
     */
    public function isAvailable(float $amount, string $currency = 'AED'): bool
    {
        if ($currency === 'AED') {
            $settings = app(\App\Services\SettingsService::class);
            $min = (float) $settings->get('bnpl_tabby_min_amount', 200);
            $max = (float) $settings->get('bnpl_tabby_max_amount', 10000);

            return $amount >= $min && $amount <= $max;
        }

        return false;
    }
}
