<?php

namespace App\Services;

class ShippingSettingsService
{
    protected SettingsService $settings;

    public function __construct(SettingsService $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Check if shipping is enabled
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->settings->get('shipping.enabled', true);
    }

    /**
     * Get the active shipping provider
     *
     * @return string
     */
    public function getProvider(): string
    {
        return $this->settings->get('shipping.provider', 'flat_rate');
    }

    /**
     * Get flat rate shipping amount
     *
     * @return float|null
     */
    public function getFlatRateAmount(): ?float
    {
        return $this->settings->get('shipping.flat_rate_amount');
    }

    /**
     * Get minimum order value for free shipping
     *
     * @return float|null
     */
    public function getFreeShippingMinimum(): ?float
    {
        return $this->settings->get('shipping.free_shipping_min');
    }

    /**
     * Check if order qualifies for free shipping
     *
     * @param float $orderTotal
     * @return bool
     */
    public function qualifiesForFreeShipping(float $orderTotal): bool
    {
        $minimum = $this->getFreeShippingMinimum();

        if ($minimum === null) {
            return false;
        }

        return $orderTotal >= $minimum;
    }

    /**
     * Calculate shipping cost for an order
     *
     * @param float $orderTotal
     * @return float
     */
    public function calculateShippingCost(float $orderTotal): float
    {
        if (!$this->isEnabled()) {
            return 0;
        }

        if ($this->qualifiesForFreeShipping($orderTotal)) {
            return 0;
        }

        if ($this->getProvider() === 'flat_rate') {
            return $this->getFlatRateAmount() ?? 0;
        }

        // For Aramex or other dynamic providers, implement calculation logic here
        return 0;
    }

    /**
     * Get Aramex configuration (combines DB settings with .env credentials)
     *
     * @return array
     */
    public function getAramexConfig(): array
    {
        return [
            // Secrets from .env (NEVER store in database)
            'username' => config('services.aramex.username'),
            'password' => config('services.aramex.password'),
            'account_number' => config('services.aramex.account_number'),

            // Non-sensitive data from database
            'country_code' => $this->settings->get('aramex.country_code', 'AE'),
            'sender_city' => $this->settings->get('aramex.sender_city'),
            'sender_postal_code' => $this->settings->get('aramex.sender_postal_code'),
            'sender_address' => $this->settings->get('aramex.sender_address'),
        ];
    }

    /**
     * Check if Aramex is properly configured
     *
     * @return bool
     */
    public function isAramexConfigured(): bool
    {
        $config = $this->getAramexConfig();

        return !empty($config['username'])
            && !empty($config['password'])
            && !empty($config['account_number'])
            && !empty($config['sender_city'])
            && !empty($config['sender_address']);
    }
}
