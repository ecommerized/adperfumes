<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FacebookService
{
    protected const GRAPH_API_VERSION = 'v21.0';
    protected const GRAPH_API_BASE = 'https://graph.facebook.com';

    protected ?string $pageId;
    protected ?string $pageAccessToken;

    public function __construct()
    {
        $settings = app(SettingsService::class);
        $this->pageId = $settings->get('facebook_page_id');
        $this->pageAccessToken = $settings->get('facebook_page_access_token');
    }

    public function isConfigured(): bool
    {
        return !empty($this->pageId) && !empty($this->pageAccessToken);
    }

    /**
     * Get Facebook Page info to verify the connection.
     */
    public function getPageInfo(): ?array
    {
        if (!$this->isConfigured()) {
            return null;
        }

        try {
            $url = self::GRAPH_API_BASE . '/' . self::GRAPH_API_VERSION
                 . '/' . $this->pageId;

            $response = Http::timeout(30)
                ->get($url, [
                    'fields' => 'id,name,category,fan_count,picture',
                    'access_token' => $this->pageAccessToken,
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('FacebookService: getPageInfo failed', [
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 500),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('FacebookService: getPageInfo exception', [
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Post text + image to the Facebook Page.
     *
     * @return array{success: bool, post_id: ?string, error: ?string}
     */
    public function postToPage(string $caption, ?string $imagePath = null): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'post_id' => null,
                'error' => 'Facebook Page ID or Access Token not configured.',
            ];
        }

        try {
            if ($imagePath && Storage::disk('public')->exists($imagePath)) {
                return $this->postWithImage($caption, $imagePath);
            }

            return $this->postTextOnly($caption);
        } catch (\Exception $e) {
            Log::error('FacebookService: postToPage exception', [
                'message' => $e->getMessage(),
            ]);
            return [
                'success' => false,
                'post_id' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    protected function postWithImage(string $caption, string $imagePath): array
    {
        $url = self::GRAPH_API_BASE . '/' . self::GRAPH_API_VERSION
             . '/' . $this->pageId . '/photos';

        $fullPath = Storage::disk('public')->path($imagePath);

        $response = Http::timeout(120)
            ->attach('source', file_get_contents($fullPath), basename($imagePath))
            ->post($url, [
                'message' => $caption,
                'access_token' => $this->pageAccessToken,
            ]);

        if ($response->successful()) {
            $postId = $response->json('post_id') ?? $response->json('id');
            Log::info('FacebookService: Image post published', ['post_id' => $postId]);
            return [
                'success' => true,
                'post_id' => $postId,
                'error' => null,
            ];
        }

        $error = $response->json('error.message') ?? 'Unknown error';
        Log::error('FacebookService: Image post failed', [
            'status' => $response->status(),
            'error' => $error,
        ]);

        return [
            'success' => false,
            'post_id' => null,
            'error' => $error,
        ];
    }

    protected function postTextOnly(string $caption): array
    {
        $url = self::GRAPH_API_BASE . '/' . self::GRAPH_API_VERSION
             . '/' . $this->pageId . '/feed';

        $response = Http::timeout(60)
            ->post($url, [
                'message' => $caption,
                'access_token' => $this->pageAccessToken,
            ]);

        if ($response->successful()) {
            $postId = $response->json('id');
            Log::info('FacebookService: Text post published', ['post_id' => $postId]);
            return [
                'success' => true,
                'post_id' => $postId,
                'error' => null,
            ];
        }

        $error = $response->json('error.message') ?? 'Unknown error';
        Log::error('FacebookService: Text post failed', [
            'status' => $response->status(),
            'error' => $error,
        ]);

        return [
            'success' => false,
            'post_id' => null,
            'error' => $error,
        ];
    }
}
