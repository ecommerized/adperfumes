<?php

namespace App\Services;

class BnplWidgetService
{
    protected SettingsService $settings;

    public function __construct(SettingsService $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Get widget display data for a given product price.
     *
     * @return array{tabby: ?array, tamara: ?array}
     */
    public function getWidgetsForPrice(float $price): array
    {
        return [
            'tabby' => $this->getTabbyWidget($price),
            'tamara' => $this->getTamaraWidget($price),
        ];
    }

    protected function getTabbyWidget(float $price): ?array
    {
        if (!(bool) $this->settings->get('bnpl_tabby_widget_enabled', false)) {
            return null;
        }

        if (!(bool) $this->settings->get('payment_tabby_enabled', false)) {
            return null;
        }

        $min = (float) $this->settings->get('bnpl_tabby_min_amount', 200);
        $max = (float) $this->settings->get('bnpl_tabby_max_amount', 10000);
        $installments = (int) $this->settings->get('bnpl_tabby_installment_count', 4);

        if ($price < $min || $price > $max) {
            return null;
        }

        return [
            'installment_count' => $installments,
            'installment_amount' => round($price / $installments, 2),
            'min_amount' => $min,
            'max_amount' => $max,
        ];
    }

    protected function getTamaraWidget(float $price): ?array
    {
        if (!(bool) $this->settings->get('bnpl_tamara_widget_enabled', false)) {
            return null;
        }

        if (!(bool) $this->settings->get('payment_tamara_enabled', false)) {
            return null;
        }

        $min = (float) $this->settings->get('bnpl_tamara_min_amount', 100);
        $max = (float) $this->settings->get('bnpl_tamara_max_amount', 20000);
        $installments = (int) $this->settings->get('bnpl_tamara_installment_count', 3);

        if ($price < $min || $price > $max) {
            return null;
        }

        return [
            'installment_count' => $installments,
            'installment_amount' => round($price / $installments, 2),
            'min_amount' => $min,
            'max_amount' => $max,
        ];
    }
}
