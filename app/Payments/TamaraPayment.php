<?php

namespace App\Payments;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Tamara Payment Service
 *
 * Buy Now Pay Later (BNPL) payment gateway integration
 * Allows customers to pay in 3 installments with 0% interest
 */
class TamaraPayment
{
    protected string $apiUrl;
    protected string $apiToken;
    protected string $notificationKey;
    protected bool $isLive;

    public function __construct()
    {
        $this->apiToken = config('services.tamara.api_token');
        $this->notificationKey = config('services.tamara.notification_key');
        $this->isLive = config('services.tamara.is_live', false);

        $this->apiUrl = $this->isLive
            ? 'https://api.tamara.co'
            : 'https://api-sandbox.tamara.co';
    }

    /**
     * Create Tamara checkout session
     *
     * @param array $orderData Order details
     * @return array
     */
    public function createCheckout(array $orderData): array
    {
        try {
            // If no credentials configured, return test mode response
            if (empty($this->apiToken)) {
                return [
                    'success' => false,
                    'error' => 'Tamara credentials not configured. Please set up your Tamara account.',
                    'test_mode' => true,
                ];
            }

            $payload = [
                'order_reference_id' => $orderData['order_number'],
                'total_amount' => [
                    'amount' => $this->formatAmount($orderData['amount']),
                    'currency' => $orderData['currency'],
                ],
                'description' => $orderData['description'],
                'country_code' => 'AE',
                'payment_type' => 'PAY_BY_INSTALMENTS', // or 'PAY_BY_LATER'
                'instalments' => 3, // 3 monthly installments
                'locale' => 'en_US',
                'items' => $orderData['items'] ?? [],
                'consumer' => [
                    'first_name' => $orderData['first_name'],
                    'last_name' => $orderData['last_name'],
                    'phone_number' => $orderData['phone'],
                    'email' => $orderData['email'],
                ],
                'shipping_address' => [
                    'first_name' => $orderData['first_name'],
                    'last_name' => $orderData['last_name'],
                    'line1' => $orderData['address'],
                    'city' => $orderData['city'],
                    'country_code' => $orderData['country'] ?? 'AE',
                    'phone_number' => $orderData['phone'],
                ],
                'billing_address' => [
                    'first_name' => $orderData['first_name'],
                    'last_name' => $orderData['last_name'],
                    'line1' => $orderData['address'],
                    'city' => $orderData['city'],
                    'country_code' => $orderData['country'] ?? 'AE',
                    'phone_number' => $orderData['phone'],
                ],
                'discount' => [
                    'name' => 'Discount',
                    'amount' => [
                        'amount' => $this->formatAmount($orderData['discount'] ?? 0),
                        'currency' => $orderData['currency'],
                    ],
                ],
                'shipping_amount' => [
                    'amount' => $this->formatAmount($orderData['shipping'] ?? 0),
                    'currency' => $orderData['currency'],
                ],
                'tax_amount' => [
                    'amount' => '0.00',
                    'currency' => $orderData['currency'],
                ],
                'merchant_url' => [
                    'success' => $orderData['success_url'],
                    'failure' => $orderData['failure_url'],
                    'cancel' => $orderData['cancel_url'],
                    'notification' => $orderData['notification_url'],
                ],
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiToken,
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl . '/checkout', $payload);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'checkout_id' => $data['checkout_id'] ?? null,
                    'order_id' => $data['order_id'] ?? null,
                    'redirect_url' => $data['checkout_url'] ?? null,
                ];
            }

            Log::error('Tamara Checkout Failed', [
                'response' => $response->json(),
                'status' => $response->status(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to create Tamara checkout session',
            ];

        } catch (Exception $e) {
            Log::error('Tamara Checkout Exception', [
                'message' => $e->getMessage(),
                'order' => $orderData['order_number'] ?? 'Unknown',
            ]);

            return [
                'success' => false,
                'error' => 'Checkout error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get order details from Tamara
     *
     * @param string $orderId Tamara order ID
     * @return array
     */
    public function getOrder(string $orderId): array
    {
        try {
            if (empty($this->apiToken)) {
                return [
                    'success' => false,
                    'error' => 'Tamara credentials not configured',
                ];
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiToken,
            ])->get($this->apiUrl . '/orders/' . $orderId);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'order_id' => $data['order_id'] ?? null,
                    'status' => $data['status'] ?? 'unknown',
                    'order_reference_id' => $data['order_reference_id'] ?? null,
                    'total_amount' => $data['total_amount']['amount'] ?? 0,
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to retrieve order details',
            ];

        } catch (Exception $e) {
            Log::error('Tamara Get Order Exception', [
                'order_id' => $orderId,
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Error retrieving order: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Authorize order (confirm payment)
     *
     * @param string $orderId Tamara order ID
     * @return array
     */
    public function authorizeOrder(string $orderId): array
    {
        try {
            if (empty($this->apiToken)) {
                return [
                    'success' => false,
                    'error' => 'Tamara credentials not configured',
                ];
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiToken,
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl . '/orders/' . $orderId . '/authorise', [
                'order_id' => $orderId,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'order_id' => $data['order_id'] ?? null,
                    'status' => $data['order_status'] ?? 'authorised',
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to authorize order',
            ];

        } catch (Exception $e) {
            Log::error('Tamara Authorize Order Exception', [
                'order_id' => $orderId,
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Authorization error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Cancel order
     *
     * @param string $orderId Tamara order ID
     * @return array
     */
    public function cancelOrder(string $orderId): array
    {
        try {
            if (empty($this->apiToken)) {
                return [
                    'success' => false,
                    'error' => 'Tamara credentials not configured',
                ];
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiToken,
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl . '/orders/' . $orderId . '/cancel', [
                'order_id' => $orderId,
                'total_amount' => [
                    'amount' => '0.00',
                    'currency' => 'AED',
                ],
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Order cancelled successfully',
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to cancel order',
            ];

        } catch (Exception $e) {
            Log::error('Tamara Cancel Order Exception', [
                'order_id' => $orderId,
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Cancellation error: ' . $e->getMessage(),
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
     * Check if Tamara is available for this amount
     *
     * @param float $amount Order amount
     * @param string $currency Currency code
     * @return bool
     */
    public function isAvailable(float $amount, string $currency = 'AED'): bool
    {
        if ($currency === 'AED') {
            return $amount >= 100 && $amount <= 20000;
        }

        return false;
    }
}
