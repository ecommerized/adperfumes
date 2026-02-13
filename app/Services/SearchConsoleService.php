<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class SearchConsoleService
{
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

    protected function getAccessToken(): string
    {
        return Cache::remember('gsc_access_token', 3500, function () {
            $jsonPath = $this->settings->get('gsc_service_account_json');
            $credentials = json_decode(file_get_contents(storage_path("app/{$jsonPath}")), true);

            $now = time();
            $payload = [
                'iss' => $credentials['client_email'],
                'scope' => 'https://www.googleapis.com/auth/webmasters.readonly',
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ];

            $jwt = JWT::encode($payload, $credentials['private_key'], 'RS256');

            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            return $response->json('access_token');
        });
    }

    public function testConnection(): array
    {
        try {
            $token = $this->getAccessToken();

            $response = Http::withToken($token)
                ->get('https://www.googleapis.com/webmasters/v3/sites');

            if ($response->failed()) {
                return ['success' => false, 'error' => $response->json('error.message', 'Unknown error')];
            }

            $siteList = [];
            foreach ($response->json('siteEntry', []) as $site) {
                $siteList[] = $site['siteUrl'];
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
            try {
                $token = $this->getAccessToken();
                $siteUrl = $this->getSiteUrl();

                $response = Http::withToken($token)
                    ->post("https://www.googleapis.com/webmasters/v3/sites/" . urlencode($siteUrl) . "/searchAnalytics/query", [
                        'startDate' => $startDate,
                        'endDate' => $endDate,
                    ]);

                if ($response->failed()) {
                    return ['error' => $response->json('error.message', 'API request failed')];
                }

                $rows = $response->json('rows', []);

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
                    'clicks' => $row['clicks'] ?? 0,
                    'impressions' => $row['impressions'] ?? 0,
                    'ctr' => round(($row['ctr'] ?? 0) * 100, 2),
                    'position' => round($row['position'] ?? 0, 1),
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
            try {
                $token = $this->getAccessToken();
                $siteUrl = $this->getSiteUrl();

                $response = Http::withToken($token)
                    ->post("https://www.googleapis.com/webmasters/v3/sites/" . urlencode($siteUrl) . "/searchAnalytics/query", [
                        'startDate' => $startDate,
                        'endDate' => $endDate,
                        'dimensions' => [$dimension],
                        'rowLimit' => $rowLimit,
                    ]);

                if ($response->failed()) {
                    return ['error' => $response->json('error.message', 'API request failed')];
                }

                $rows = [];
                foreach ($response->json('rows', []) as $row) {
                    $rows[] = [
                        'keys' => $row['keys'] ?? [],
                        'clicks' => $row['clicks'] ?? 0,
                        'impressions' => $row['impressions'] ?? 0,
                        'ctr' => round(($row['ctr'] ?? 0) * 100, 2),
                        'position' => round($row['position'] ?? 0, 1),
                    ];
                }

                return $rows;
            } catch (\Exception $e) {
                return ['error' => $e->getMessage()];
            }
        });
    }
}
