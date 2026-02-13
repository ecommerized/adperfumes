<?php

namespace App\Payments;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class TapPayment
{
    protected string $apiKey;
    protected string $baseUrl;
    protected bool $isLive;

    public function __construct()
    {
        $this->apiKey = config('services.tap.secret_key');
        $this->isLive = config('services.tap.is_live', false);
        $this->baseUrl = 'https://api.tap.company/v2';
    }

    /**
     * Create a payment charge
     *
     * @param array $orderData Order information
     * @return array Response from Tap API
     * @throws Exception
     */
    public function createCharge(array $orderData): array
    {
        try {
            $payload = [
                'amount' => $orderData['amount'],
                'currency' => $orderData['currency'] ?? 'AED',
                'threeDSecure' => true,
                'save_card' => false,
                'description' => $orderData['description'] ?? 'Order Payment',
                'statement_descriptor' => 'AD Perfumes',
                'metadata' => [
                    'udf1' => $orderData['order_number'] ?? '',
                    'udf2' => $orderData['email'] ?? '',
                ],
                'reference' => [
                    'transaction' => $orderData['order_number'] ?? '',
                    'order' => $orderData['order_number'] ?? '',
                ],
                'receipt' => [
                    'email' => true,
                    'sms' => false,
                ],
                'customer' => [
                    'first_name' => $orderData['first_name'] ?? '',
                    'last_name' => $orderData['last_name'] ?? '',
                    'email' => $orderData['email'] ?? '',
                    'phone' => [
                        'country_code' => '971',
                        'number' => $orderData['phone'] ?? '',
                    ],
                ],
                'source' => [
                    'id' => 'src_all',
                ],
                'post' => [
                    'url' => route('payment.callback.tap'),
                ],
                'redirect' => [
                    'url' => route('payment.return.tap'),
                ],
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/charges', $payload);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                    'charge_id' => $response->json()['id'] ?? null,
                    'redirect_url' => $response->json()['transaction']['url'] ?? null,
                ];
            }

            Log::error('Tap Payment Charge Creation Failed', [
                'response' => $response->json(),
                'status' => $response->status(),
            ]);

            return [
                'success' => false,
                'error' => $response->json()['errors'][0]['description'] ?? 'Payment failed',
                'data' => $response->json(),
            ];

        } catch (Exception $e) {
            Log::error('Tap Payment Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new Exception('Payment gateway error: ' . $e->getMessage());
        }
    }

    /**
     * Retrieve a charge by ID
     *
     * @param string $chargeId
     * @return array
     */
    public function retrieveCharge(string $chargeId): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->get($this->baseUrl . '/charges/' . $chargeId);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to retrieve charge',
                'data' => $response->json(),
            ];

        } catch (Exception $e) {
            Log::error('Tap Retrieve Charge Exception', [
                'charge_id' => $chargeId,
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verify payment callback from Tap
     *
     * @param string $chargeId
     * @return array
     */
    public function verifyPayment(string $chargeId): array
    {
        $chargeData = $this->retrieveCharge($chargeId);

        if (!$chargeData['success']) {
            return [
                'success' => false,
                'status' => 'failed',
                'message' => 'Failed to verify payment',
            ];
        }

        $charge = $chargeData['data'];
        $status = $charge['status'] ?? '';

        return [
            'success' => true,
            'status' => $status, // CAPTURED, FAILED, INITIATED, etc.
            'is_paid' => $status === 'CAPTURED',
            'amount' => $charge['amount'] ?? 0,
            'currency' => $charge['currency'] ?? 'AED',
            'order_number' => $charge['reference']['order'] ?? null,
            'transaction_id' => $charge['id'] ?? null,
            'payment_method' => $charge['source']['payment_method'] ?? 'card',
            'data' => $charge,
        ];
    }

    /**
     * Create a refund
     *
     * @param string $chargeId
     * @param float $amount
     * @param string $reason
     * @return array
     */
    public function createRefund(string $chargeId, float $amount, string $reason = 'requested_by_customer'): array
    {
        try {
            $payload = [
                'charge_id' => $chargeId,
                'amount' => $amount,
                'currency' => 'AED',
                'reason' => $reason,
                'reference' => [
                    'merchant' => 'Refund-' . time(),
                ],
                'metadata' => [
                    'udf1' => 'Refund processed',
                ],
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/refunds', $payload);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                    'refund_id' => $response->json()['id'] ?? null,
                ];
            }

            return [
                'success' => false,
                'error' => $response->json()['errors'][0]['description'] ?? 'Refund failed',
                'data' => $response->json(),
            ];

        } catch (Exception $e) {
            Log::error('Tap Refund Exception', [
                'charge_id' => $chargeId,
                'amount' => $amount,
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check if payment is successful
     *
     * @param array $tapResponse
     * @return bool
     */
    public function isPaymentSuccessful(array $tapResponse): bool
    {
        return isset($tapResponse['status']) && $tapResponse['status'] === 'CAPTURED';
    }

    /**
     * Get payment method name from Tap response
     *
     * @param array $tapResponse
     * @return string
     */
    public function getPaymentMethod(array $tapResponse): string
    {
        return $tapResponse['source']['payment_method'] ?? 'card';
    }

    /**
     * Format amount for Tap (Tap uses smallest currency unit)
     * For AED: 1 AED = 1.00 (no conversion needed)
     *
     * @param float $amount
     * @return float
     */
    public function formatAmount(float $amount): float
    {
        return round($amount, 2);
    }
}
