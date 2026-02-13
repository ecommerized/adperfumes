<?php

namespace App\Services\Shipping;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * AramexService
 *
 * Complete Aramex shipping integration for UAE operations
 */
class AramexService
{
    protected array $credentials;
    protected string $baseUrl;
    protected bool $isLive;

    public function __construct()
    {
        $this->credentials = [
            'UserName' => config('services.aramex.username'),
            'Password' => config('services.aramex.password'),
            'Version' => 'v1.0',
            'AccountNumber' => config('services.aramex.account_number'),
            'AccountPin' => config('services.aramex.account_pin'),
            'AccountEntity' => config('services.aramex.account_entity'),
            'AccountCountryCode' => config('services.aramex.account_country_code'),
        ];

        $this->baseUrl = config('services.aramex.base_url');
        $this->isLive = config('services.aramex.is_live', false);
    }

    /**
     * Calculate shipping rate using Aramex Rate Calculator API
     *
     * @param array $address Shipping address details
     * @param float $weight Package weight in KG
     * @param array $items Order items for dimensional weight
     * @return array
     */
    public function calculateShippingRate(array $address, float $weight = 1.0, array $items = []): array
    {
        try {
            // If no Aramex credentials, return fixed rate
            if (empty($this->credentials['UserName']) || empty($this->credentials['Password'])) {
                return $this->getFixedRate();
            }

            $payload = [
                'ClientInfo' => $this->credentials,
                'Transaction' => [
                    'Reference1' => 'Rate-' . time(),
                ],
                'OriginAddress' => [
                    'Line1' => 'AD Perfumes Warehouse',
                    'City' => 'Dubai',
                    'CountryCode' => 'AE',
                ],
                'DestinationAddress' => [
                    'Line1' => $address['address'] ?? '',
                    'City' => $address['city'] ?? 'Dubai',
                    'CountryCode' => $address['country'] ?? 'AE',
                    'PostCode' => $address['postal_code'] ?? '',
                ],
                'ShipmentDetails' => [
                    'Dimensions' => [
                        'Length' => 30,
                        'Width' => 20,
                        'Height' => 15,
                        'Unit' => 'CM',
                    ],
                    'ActualWeight' => [
                        'Value' => max($weight, 0.5),
                        'Unit' => 'KG',
                    ],
                    'ChargeableWeight' => [
                        'Value' => max($weight, 0.5),
                        'Unit' => 'KG',
                    ],
                    'NumberOfPieces' => 1,
                ],
                'PreferredCurrencyCode' => 'AED',
            ];

            $response = Http::timeout(30)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($this->baseUrl . 'Service_1_0.svc/JSON/CalculateRate', $payload);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['TotalAmount']) && $data['TotalAmount']['Value'] > 0) {
                    return [
                        'success' => true,
                        'rate' => (float) $data['TotalAmount']['Value'],
                        'currency' => $data['TotalAmount']['CurrencyCode'] ?? 'AED',
                        'delivery_time' => '2-3 business days',
                        'service_type' => 'Express',
                    ];
                }
            }

            // Fallback to fixed rate if API fails
            Log::warning('Aramex Rate Calculation Failed', [
                'response' => $response->json(),
                'status' => $response->status(),
            ]);

            return $this->getFixedRate();

        } catch (Exception $e) {
            Log::error('Aramex Rate Calculation Exception', [
                'message' => $e->getMessage(),
            ]);

            return $this->getFixedRate();
        }
    }

    /**
     * Create shipment with Aramex
     *
     * @param array $orderData Order details
     * @return array
     */
    public function createShipment(array $orderData): array
    {
        try {
            // If no Aramex credentials, return test mode response
            if (empty($this->credentials['UserName']) || empty($this->credentials['Password'])) {
                return [
                    'success' => true,
                    'tracking_number' => 'TEST-' . strtoupper(substr(md5(time()), 0, 10)),
                    'label_url' => null,
                    'message' => 'Test mode - Configure Aramex credentials for live shipments',
                ];
            }

            $payload = [
                'ClientInfo' => $this->credentials,
                'Transaction' => [
                    'Reference1' => $orderData['order_number'] ?? 'ORD-' . time(),
                    'Reference2' => $orderData['email'] ?? '',
                ],
                'Shipments' => [
                    [
                        'Shipper' => [
                            'Reference1' => $orderData['order_number'] ?? '',
                            'AccountNumber' => $this->credentials['AccountNumber'],
                            'PartyAddress' => [
                                'Line1' => 'AD Perfumes',
                                'Line2' => 'Warehouse Address',
                                'City' => 'Dubai',
                                'CountryCode' => 'AE',
                            ],
                            'Contact' => [
                                'Department' => 'Shipping',
                                'PersonName' => 'AD Perfumes',
                                'PhoneNumber1' => '+971 4 1234567',
                                'EmailAddress' => 'shipping@adperfumes.com',
                            ],
                        ],
                        'Consignee' => [
                            'Reference1' => $orderData['order_number'] ?? '',
                            'PartyAddress' => [
                                'Line1' => $orderData['address'] ?? '',
                                'City' => $orderData['city'] ?? '',
                                'CountryCode' => $orderData['country'] ?? 'AE',
                                'PostCode' => $orderData['postal_code'] ?? '',
                            ],
                            'Contact' => [
                                'PersonName' => $orderData['full_name'] ?? '',
                                'PhoneNumber1' => $orderData['phone'] ?? '',
                                'EmailAddress' => $orderData['email'] ?? '',
                            ],
                        ],
                        'Details' => [
                            'Dimensions' => [
                                'Length' => 30,
                                'Width' => 20,
                                'Height' => 15,
                                'Unit' => 'CM',
                            ],
                            'ActualWeight' => [
                                'Value' => 1.0,
                                'Unit' => 'KG',
                            ],
                            'NumberOfPieces' => 1,
                            'DescriptionOfGoods' => 'Perfumes',
                            'GoodsOriginCountry' => 'AE',
                        ],
                    ],
                ],
                'LabelInfo' => [
                    'ReportID' => 9201,
                    'ReportType' => 'URL',
                ],
            ];

            $response = Http::timeout(30)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($this->baseUrl . 'Service_1_0.svc/JSON/CreateShipments', $payload);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['Shipments'][0])) {
                    $shipment = $data['Shipments'][0];

                    if (isset($shipment['ID'])) {
                        return [
                            'success' => true,
                            'tracking_number' => $shipment['ID'],
                            'label_url' => $shipment['ShipmentLabel']['LabelURL'] ?? null,
                            'aramex_shipment_id' => $shipment['ID'],
                            'message' => 'Shipment created successfully',
                        ];
                    }
                }

                // Check for errors
                if (isset($data['HasErrors']) && $data['HasErrors']) {
                    $errorMessage = $data['Notifications'][0]['Message'] ?? 'Unknown error';

                    return [
                        'success' => false,
                        'message' => 'Aramex Error: ' . $errorMessage,
                    ];
                }
            }

            return [
                'success' => false,
                'message' => 'Failed to create shipment with Aramex',
            ];

        } catch (Exception $e) {
            Log::error('Aramex Shipment Creation Exception', [
                'message' => $e->getMessage(),
                'order' => $orderData['order_number'] ?? 'Unknown',
            ]);

            return [
                'success' => false,
                'message' => 'Shipment creation error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Track shipment
     *
     * @param string $trackingNumber
     * @return array
     */
    public function trackShipment(string $trackingNumber): array
    {
        try {
            if (empty($this->credentials['UserName']) || empty($this->credentials['Password'])) {
                return [
                    'success' => false,
                    'message' => 'Aramex credentials not configured',
                ];
            }

            $payload = [
                'ClientInfo' => $this->credentials,
                'Transaction' => [
                    'Reference1' => 'Track-' . time(),
                ],
                'Shipments' => [$trackingNumber],
            ];

            $response = Http::timeout(30)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($this->baseUrl . 'Service_1_0.svc/JSON/TrackShipments', $payload);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['Results'][0])) {
                    $trackingData = $data['Results'][0];

                    return [
                        'success' => true,
                        'status' => $trackingData['UpdateDescription'] ?? 'Unknown',
                        'location' => $trackingData['UpdateLocation'] ?? '',
                        'last_update' => $trackingData['UpdateDateTime'] ?? '',
                        'events' => $trackingData['TrackingEvents'] ?? [],
                    ];
                }
            }

            return [
                'success' => false,
                'message' => 'Tracking information not available',
            ];

        } catch (Exception $e) {
            Log::error('Aramex Tracking Exception', [
                'tracking_number' => $trackingNumber,
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Tracking error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get fixed shipping rate (fallback)
     *
     * @return array
     */
    protected function getFixedRate(): array
    {
        return [
            'success' => true,
            'rate' => 25.00,
            'currency' => 'AED',
            'delivery_time' => '2-3 business days',
            'service_type' => 'Express (Fixed Rate)',
        ];
    }

    /**
     * Validate address with Aramex
     *
     * @param array $address
     * @return array
     */
    public function validateAddress(array $address): array
    {
        // Aramex address validation API
        // This is a placeholder - implement if needed
        return [
            'valid' => true,
            'message' => 'Address validation not implemented',
        ];
    }

    /**
     * Schedule pickup
     *
     * @param array $pickupData
     * @return array
     */
    public function schedulePickup(array $pickupData): array
    {
        try {
            if (empty($this->credentials['UserName']) || empty($this->credentials['Password'])) {
                return [
                    'success' => false,
                    'message' => 'Aramex credentials not configured',
                ];
            }

            // Implement Aramex Pickup API
            // This is a placeholder for future implementation

            return [
                'success' => false,
                'message' => 'Pickup scheduling not yet implemented',
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Pickup scheduling error: ' . $e->getMessage(),
            ];
        }
    }
}
