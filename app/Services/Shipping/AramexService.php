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

            // Normalize country code to ISO 3166-1 alpha-2 format
            $destCountry = $this->normalizeCountryCode($address['country'] ?? 'AE');
            $isInternational = $destCountry !== 'AE';

            $payload = [
                'ClientInfo' => $this->credentials,
                'Transaction' => [
                    'Reference1' => 'Rate-' . time(),
                    'Reference2' => '',
                    'Reference3' => '',
                    'Reference4' => '',
                    'Reference5' => '',
                ],
                'OriginAddress' => $this->getShipperAddress(),
                'DestinationAddress' => [
                    'Line1' => $address['address'] ?? '',
                    'Line2' => '',
                    'Line3' => '',
                    'City' => $address['city'] ?? 'Dubai',
                    'StateOrProvinceCode' => '',
                    'PostCode' => $address['postal_code'] ?? '',
                    'CountryCode' => $destCountry,
                    'Longitude' => 0,
                    'Latitude' => 0,
                    'BuildingNumber' => null,
                    'BuildingName' => null,
                    'Floor' => null,
                    'Apartment' => null,
                    'POBox' => null,
                    'Description' => null,
                ],
                'ShipmentDetails' => [
                    'PaymentType' => 'P',
                    'ProductGroup' => $isInternational ? 'EXP' : 'DOM',
                    'ProductType' => $isInternational ? 'PPX' : 'ONP',
                    'ActualWeight' => [
                        'Unit' => 'KG',
                        'Value' => max($weight, 0.5),
                    ],
                    'ChargeableWeight' => [
                        'Unit' => 'KG',
                        'Value' => max($weight, 0.5),
                    ],
                    'NumberOfPieces' => 1,
                    'Dimensions' => [
                        'Length' => 30,
                        'Width' => 20,
                        'Height' => 15,
                        'Unit' => 'CM',
                    ],
                    'DescriptionOfGoods' => 'Perfumes',
                    'GoodsOriginCountry' => 'AE',
                    'PaymentOptions' => '',
                ],
                'PreferredCurrencyCode' => 'AED',
            ];

            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($this->baseUrl . 'RateCalculator/Service_1_0.svc/json/CalculateRate', $payload);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['TotalAmount']) && $data['TotalAmount']['Value'] > 0) {
                    return [
                        'success' => true,
                        'rate' => (float) $data['TotalAmount']['Value'],
                        'currency' => $data['TotalAmount']['CurrencyCode'] ?? 'AED',
                        'delivery_time' => $isInternational ? '5-7 business days' : '2-3 business days',
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

            // Log incoming order data for debugging
            Log::info('AramexService: createShipment called', [
                'order_number' => $orderData['order_number'] ?? 'N/A',
                'full_name_raw' => $orderData['full_name'] ?? null,
                'phone_raw' => $orderData['phone'] ?? null,
                'address' => $orderData['address'] ?? null,
                'city' => $orderData['city'] ?? null,
            ]);

            // Validate required fields
            $fullName = trim($orderData['full_name'] ?? '');
            if (empty($fullName)) {
                Log::error('AramexService: Full name validation failed', [
                    'full_name_raw' => $orderData['full_name'] ?? null,
                    'full_name_trimmed' => $fullName,
                    'is_empty' => empty($fullName),
                ]);
                return [
                    'success' => false,
                    'message' => 'Customer name is required for shipment creation. Please ensure the order has a first name and last name.',
                ];
            }

            $phone = trim($orderData['phone'] ?? '');
            if (empty($phone)) {
                Log::error('AramexService: Phone validation failed', [
                    'phone_raw' => $orderData['phone'] ?? null,
                    'phone_trimmed' => $phone,
                ]);
                return [
                    'success' => false,
                    'message' => 'Customer phone number is required for shipment creation.',
                ];
            }

            Log::info('AramexService: Validation passed', [
                'full_name' => $fullName,
                'phone' => $phone,
            ]);

            // Normalize country code to ISO 3166-1 alpha-2 format
            $destCountry = $this->normalizeCountryCode($orderData['country'] ?? 'AE');
            $isInternational = $destCountry !== 'AE';

            $payload = [
                'ClientInfo' => $this->credentials,
                'Transaction' => [
                    'Reference1' => $orderData['order_number'] ?? 'ORD-' . time(),
                    'Reference2' => $orderData['email'] ?? '',
                    'Reference3' => '',
                    'Reference4' => '',
                    'Reference5' => '',
                ],
                'Shipments' => [
                    [
                        'Reference1' => $orderData['order_number'] ?? '',
                        'Reference2' => '',
                        'Reference3' => '',
                        'Shipper' => [
                            'Reference1' => $orderData['order_number'] ?? '',
                            'Reference2' => '',
                            'AccountNumber' => $this->credentials['AccountNumber'],
                            'PartyAddress' => $this->getShipperAddress(),
                            'Contact' => $this->getShipperContact(),
                        ],
                        'Consignee' => [
                            'Reference1' => $orderData['order_number'] ?? '',
                            'Reference2' => '',
                            'AccountNumber' => '',
                            'PartyAddress' => [
                                'Line1' => $orderData['address'] ?? '',
                                'Line2' => $orderData['address2'] ?? '',
                                'Line3' => '',
                                'City' => $orderData['city'] ?? '',
                                'StateOrProvinceCode' => '',
                                'PostCode' => $orderData['postal_code'] ?? '',
                                'CountryCode' => $destCountry,
                                'Longitude' => 0,
                                'Latitude' => 0,
                                'BuildingNumber' => null,
                                'BuildingName' => null,
                                'Floor' => null,
                                'Apartment' => null,
                                'POBox' => null,
                                'Description' => null,
                            ],
                            'Contact' => $this->getConsigneeContact($fullName, $phone, $orderData['email'] ?? ''),
                        ],
                        'ThirdParty' => [
                            'Reference1' => '',
                            'Reference2' => '',
                            'AccountNumber' => '',
                            'PartyAddress' => [
                                'Line1' => '',
                                'Line2' => '',
                                'Line3' => '',
                                'City' => '',
                                'StateOrProvinceCode' => '',
                                'PostCode' => '',
                                'CountryCode' => '',
                                'Longitude' => 0,
                                'Latitude' => 0,
                                'BuildingNumber' => null,
                                'BuildingName' => null,
                                'Floor' => null,
                                'Apartment' => null,
                                'POBox' => null,
                                'Description' => null,
                            ],
                            'Contact' => [
                                'Department' => '',
                                'PersonName' => '',
                                'Title' => '',
                                'CompanyName' => '',
                                'PhoneNumber1' => '',
                                'PhoneNumber1Ext' => '',
                                'PhoneNumber2' => '',
                                'PhoneNumber2Ext' => '',
                                'FaxNumber' => '',
                                'CellPhone' => '',
                                'EmailAddress' => '',
                                'Type' => '',
                            ],
                        ],
                        'ShippingDateTime' => '/Date(' . (time() * 1000) . ')/',
                        'DueDate' => '/Date(' . ((time() + 86400 * 3) * 1000) . ')/',
                        'Comments' => '',
                        'PickupLocation' => '',
                        'OperationsInstructions' => '',
                        'AccountingInstrcutions' => '',
                        'Details' => [
                            'Dimensions' => [
                                'Length' => 30,
                                'Width' => 20,
                                'Height' => 15,
                                'Unit' => 'CM',
                            ],
                            'ActualWeight' => [
                                'Unit' => 'KG',
                                'Value' => $orderData['weight'] ?? 1.0,
                            ],
                            'ChargeableWeight' => [
                                'Unit' => 'KG',
                                'Value' => $orderData['weight'] ?? 1.0,
                            ],
                            'DescriptionOfGoods' => 'Perfumes',
                            'GoodsOriginCountry' => 'AE',
                            'NumberOfPieces' => $orderData['pieces'] ?? 1,
                            'ProductGroup' => $isInternational ? 'EXP' : 'DOM',
                            'ProductType' => $isInternational ? 'PPX' : 'ONP',
                            'PaymentType' => 'P',
                            'PaymentOptions' => '',
                            'CustomsValueAmount' => null,
                            'CashOnDeliveryAmount' => null,
                            'InsuranceAmount' => null,
                            'CashAdditionalAmount' => null,
                            'CashAdditionalAmountDescription' => '',
                            'CollectAmount' => null,
                            'Services' => '',
                            'Items' => [],
                        ],
                    ],
                ],
                'LabelInfo' => [
                    'ReportID' => 9201,
                    'ReportType' => 'URL',
                ],
            ];

            // Log the exact payload being sent to Aramex (for debugging)
            Log::info('AramexService: Sending payload to Aramex API', [
                'consignee_name' => $payload['Shipments'][0]['Consignee']['Contact']['PersonName'] ?? 'NOT_SET',
                'consignee_phone' => $payload['Shipments'][0]['Consignee']['Contact']['PhoneNumber1'] ?? 'NOT_SET',
                'consignee_cell' => $payload['Shipments'][0]['Consignee']['Contact']['CellPhone'] ?? 'NOT_SET',
                'order_number' => $payload['Shipments'][0]['Reference1'] ?? 'NOT_SET',
                'full_payload' => $payload,
            ]);

            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($this->baseUrl . 'Shipping/Service_1_0.svc/json/CreateShipments', $payload);

            if ($response->successful()) {
                $data = $response->json();

                // Log the full response for debugging
                Log::info('Aramex API Response', [
                    'response' => $data,
                ]);

                // Check for errors first
                if (isset($data['HasErrors']) && $data['HasErrors']) {
                    $errorMessages = [];

                    // Extract error messages from various possible locations
                    if (!empty($data['Notifications'])) {
                        foreach ($data['Notifications'] as $notification) {
                            if (isset($notification['Message'])) {
                                $errorMessages[] = $notification['Message'];
                            }
                            if (isset($notification['Code'])) {
                                $errorMessages[] = 'Code: ' . $notification['Code'];
                            }
                        }
                    }

                    if (!empty($data['Shipments'][0]['Notifications'])) {
                        foreach ($data['Shipments'][0]['Notifications'] as $notification) {
                            if (isset($notification['Message'])) {
                                $errorMessages[] = $notification['Message'];
                            }
                        }
                    }

                    $errorMessage = !empty($errorMessages)
                        ? implode('. ', $errorMessages)
                        : 'Unknown error - check logs for details';

                    Log::error('Aramex Shipment Creation Error', [
                        'errors' => $errorMessages,
                        'full_response' => $data,
                    ]);

                    return [
                        'success' => false,
                        'message' => 'Aramex Error: ' . $errorMessage,
                    ];
                }

                // Check for successful shipment
                if (isset($data['Shipments'][0])) {
                    $shipment = $data['Shipments'][0];

                    if (isset($shipment['ID'])) {
                        $labelUrl = $shipment['ShipmentLabel']['LabelURL'] ?? null;

                        Log::info('Aramex Shipment Created Successfully', [
                            'tracking_number' => $shipment['ID'],
                            'label_url' => $labelUrl,
                            'has_shipment_label' => isset($shipment['ShipmentLabel']),
                            'shipment_label_structure' => $shipment['ShipmentLabel'] ?? 'NOT_SET',
                        ]);

                        return [
                            'success' => true,
                            'tracking_number' => $shipment['ID'],
                            'label_url' => $labelUrl,
                            'aramex_shipment_id' => $shipment['ID'],
                            'message' => 'Shipment created successfully',
                        ];
                    }
                }
            }

            // Log failed response
            Log::warning('Aramex Create Shipment Failed', [
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            return [
                'success' => false,
                'message' => 'Failed to create shipment with Aramex. Status: ' . $response->status(),
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
                    'Reference2' => '',
                    'Reference3' => '',
                    'Reference4' => '',
                    'Reference5' => '',
                ],
                'Shipments' => [$trackingNumber],
                'GetLastTrackingUpdateOnly' => false,
            ];

            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post($this->baseUrl . 'Tracking/Service_1_0.svc/json/TrackShipments', $payload);

            if ($response->successful()) {
                $data = $response->json();

                if (!empty($data['TrackingResults'])) {
                    $result = $data['TrackingResults'][0];

                    if (isset($result['Value']) && !empty($result['Value'])) {
                        $latestEvent = $result['Value'][0] ?? null;

                        return [
                            'success' => true,
                            'status' => $latestEvent['UpdateDescription'] ?? 'Unknown',
                            'location' => $latestEvent['UpdateLocation'] ?? '',
                            'last_update' => $latestEvent['UpdateDateTime'] ?? '',
                            'events' => $result['Value'],
                        ];
                    }
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

    /**
     * Normalize country code to ISO 3166-1 alpha-2 format
     *
     * @param string $country
     * @return string
     */
    protected function normalizeCountryCode(string $country): string
    {
        // Map common country names/codes to ISO 3166-1 alpha-2
        $countryMap = [
            'UAE' => 'AE',
            'United Arab Emirates' => 'AE',
            'Saudi Arabia' => 'SA',
            'KSA' => 'SA',
            'Qatar' => 'QA',
            'Bahrain' => 'BH',
            'Kuwait' => 'KW',
            'Oman' => 'OM',
            'Egypt' => 'EG',
            'Jordan' => 'JO',
            'Lebanon' => 'LB',
        ];

        $upperCountry = strtoupper(trim($country));

        // If it's already a 2-letter code, return it
        if (strlen($upperCountry) === 2) {
            return $upperCountry;
        }

        // Otherwise, look it up in the map
        return $countryMap[$country] ?? $upperCountry;
    }

    /**
     * Get shipper (pickup) address from database settings (with .env fallback)
     *
     * @return array
     */
    protected function getShipperAddress(): array
    {
        $settings = app(\App\Services\SettingsService::class);

        return [
            'Line1' => $settings->get('aramex.sender_address')
                ?? config('services.aramex.shipper_address', 'AD Perfumes Warehouse'),
            'Line2' => $settings->get('aramex.sender_address_2')
                ?? config('services.aramex.shipper_address_2', ''),
            'Line3' => '',
            'City' => $settings->get('aramex.sender_city')
                ?? config('services.aramex.shipper_city', 'Dubai'),
            'StateOrProvinceCode' => '',
            'PostCode' => $settings->get('aramex.sender_postal_code')
                ?? config('services.aramex.shipper_postal_code', ''),
            'CountryCode' => $settings->get('aramex.country_code', 'AE'),
            'Longitude' => 0,
            'Latitude' => 0,
            'BuildingNumber' => null,
            'BuildingName' => null,
            'Floor' => null,
            'Apartment' => null,
            'POBox' => null,
            'Description' => null,
        ];
    }

    /**
     * Get shipper contact details from database settings (with .env fallback)
     *
     * @return array
     */
    protected function getShipperContact(): array
    {
        $settings = app(\App\Services\SettingsService::class);

        return [
            'Department' => 'Shipping',
            'PersonName' => $settings->get('aramex.sender_name')
                ?? config('services.aramex.shipper_name', 'AD Perfumes'),
            'Title' => '',
            'CompanyName' => $settings->get('aramex.sender_company')
                ?? config('services.aramex.shipper_company', 'AD Perfumes'),
            'PhoneNumber1' => $settings->get('aramex.sender_phone')
                ?? config('services.aramex.shipper_phone', '+971 4 1234567'),
            'PhoneNumber1Ext' => '',
            'PhoneNumber2' => '',
            'PhoneNumber2Ext' => '',
            'FaxNumber' => '',
            'CellPhone' => $settings->get('aramex.sender_mobile')
                ?? config('services.aramex.shipper_mobile', '+971 50 1234567'),
            'EmailAddress' => $settings->get('aramex.sender_email')
                ?? config('services.aramex.shipper_email', 'shipping@adperfumes.com'),
            'Type' => '',
        ];
    }

    /**
     * Get consignee contact details for shipment
     *
     * @param string $fullName Customer full name
     * @param string $phone Customer phone number
     * @param string $email Customer email
     * @return array
     */
    protected function getConsigneeContact(string $fullName, string $phone, string $email = ''): array
    {
        Log::info('AramexService: Building consignee contact', [
            'fullName_param' => $fullName,
            'phone_param' => $phone,
            'email_param' => $email,
            'fullName_trimmed' => trim($fullName),
            'phone_trimmed' => trim($phone),
        ]);

        // Ensure we have valid data
        $trimmedName = trim($fullName);
        $trimmedPhone = trim($phone);

        if (empty($trimmedName)) {
            Log::error('AramexService: Consignee name is empty in getConsigneeContact', [
                'fullName_original' => $fullName,
                'fullName_trimmed' => $trimmedName,
            ]);
            throw new \Exception('Consignee name cannot be empty');
        }

        if (empty($trimmedPhone)) {
            Log::error('AramexService: Consignee phone is empty in getConsigneeContact', [
                'phone_original' => $phone,
                'phone_trimmed' => $trimmedPhone,
            ]);
            throw new \Exception('Consignee phone cannot be empty');
        }

        $contact = [
            'Department' => '',
            'PersonName' => $trimmedName,
            'Title' => '',
            'CompanyName' => $trimmedName,  // CRITICAL: Aramex requires CompanyName field populated
            'PhoneNumber1' => $trimmedPhone,
            'PhoneNumber1Ext' => '',
            'PhoneNumber2' => '',
            'PhoneNumber2Ext' => '',
            'FaxNumber' => '',
            'CellPhone' => $trimmedPhone,
            'EmailAddress' => trim($email),
            'Type' => '',
        ];

        Log::info('AramexService: Consignee contact built successfully', [
            'PersonName' => $contact['PersonName'],
            'CompanyName' => $contact['CompanyName'],
            'PhoneNumber1' => $contact['PhoneNumber1'],
        ]);

        return $contact;
    }
}
