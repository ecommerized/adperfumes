<?php

namespace App\Services;

use Google\Client;
use Google\Service\SearchConsole;
use Google\Service\SearchConsole\SearchAnalyticsQueryRequest;
use Illuminate\Support\Facades\Cache;

class SearchConsoleService
{
    protected ?Client $client = null;
    protected ?SearchConsole $service = null;
    protected SettingsService $settings;

    public function __construct(SettingsService $settings)
    {
        $this->settings = $settings;
    }

    public function isConfigured(): bool
    {
        $jsonPath = $this->settings->get('gsc_service_account_json');

        return $jsonPath && file_exists(storage_path("app/{$jsonPath}"));
    }

    public function getSiteUrl(): ?string
    {
        return $this->settings->get('gsc_site_url');
    }

    protected function getService(): SearchConsole
    {
        if ($this->service) {
            return $this->service;
        }

        $jsonPath = $this->settings->get('gsc_service_account_json');
        $fullPath = storage_path("app/{$jsonPath}");

        $this->client = new Client();
        $this->client->setAuthConfig($fullPath);
        $this->client->addScope(SearchConsole::WEBMASTERS_READONLY);

        $this->service = new SearchConsole($this->client);

        return $this->service;
    }

    public function testConnection(): array
    {
        try {
            $service = $this->getService();
            $sites = $service->sites->listSites();
            $siteList = [];

            foreach ($sites->getSiteEntry() as $site) {
                $siteList[] = $site->getSiteUrl();
            }

            return ['success' => true, 'sites' => $siteList];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getSummary(string $startDate, string $endDate): array
    {
        $cacheKey = "gsc_summary_{$startDate}_{$endDate}";

        return Cache::remember($cacheKey, 1800, function () use ($startDate, $endDate) {
            $service = $this->getService();
            $siteUrl = $this->getSiteUrl();

            $request = new SearchAnalyticsQueryRequest();
            $request->setStartDate($startDate);
            $request->setEndDate($endDate);

            try {
                $response = $service->searchanalytics->query($siteUrl, $request);
                $rows = $response->getRows();

                if (empty($rows)) {
                    return [
                        'clicks' => 0,
                        'impressions' => 0,
                        'ctr' => 0,
                        'position' => 0,
                    ];
                }

                $row = $rows[0];

                return [
                    'clicks' => $row->getClicks(),
                    'impressions' => $row->getImpressions(),
                    'ctr' => round($row->getCtr() * 100, 2),
                    'position' => round($row->getPosition(), 1),
                ];
            } catch (\Exception $e) {
                return ['error' => $e->getMessage()];
            }
        });
    }

    public function getSearchAnalytics(
        string $startDate,
        string $endDate,
        string $dimension = 'date',
        int $rowLimit = 25
    ): array {
        $cacheKey = "gsc_analytics_{$dimension}_{$startDate}_{$endDate}_{$rowLimit}";

        return Cache::remember($cacheKey, 1800, function () use ($startDate, $endDate, $dimension, $rowLimit) {
            $service = $this->getService();
            $siteUrl = $this->getSiteUrl();

            $request = new SearchAnalyticsQueryRequest();
            $request->setStartDate($startDate);
            $request->setEndDate($endDate);
            $request->setDimensions([$dimension]);
            $request->setRowLimit($rowLimit);

            try {
                $response = $service->searchanalytics->query($siteUrl, $request);
                $rows = [];

                foreach ($response->getRows() as $row) {
                    $rows[] = [
                        'keys' => $row->getKeys(),
                        'clicks' => $row->getClicks(),
                        'impressions' => $row->getImpressions(),
                        'ctr' => round($row->getCtr() * 100, 2),
                        'position' => round($row->getPosition(), 1),
                    ];
                }

                return $rows;
            } catch (\Exception $e) {
                return ['error' => $e->getMessage()];
            }
        });
    }
}
