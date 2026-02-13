<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShopifyService
{
    protected $storeUrl;
    protected $accessToken;
    protected $apiVersion;

    public function __construct()
    {
        $this->storeUrl = env('SHOPIFY_STORE_URL', 'adperfumes.myshopify.com');
        $this->accessToken = env('SHOPIFY_ACCESS_TOKEN', '');
        $this->apiVersion = env('SHOPIFY_API_VERSION', '2026-01');
    }

    /**
     * Make a request to Shopify API
     */
    protected function request($endpoint, $method = 'GET', $data = [])
    {
        $url = "https://{$this->storeUrl}/admin/api/{$this->apiVersion}/{$endpoint}";

        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $this->accessToken,
            'Content-Type' => 'application/json',
        ])->$method($url, $data);

        if ($response->failed()) {
            Log::error('Shopify API Error', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception("Shopify API request failed: {$response->body()}");
        }

        return [
            'data' => $response->json(),
            'headers' => $response->headers(),
        ];
    }

    /**
     * Get all products (paginated)
     */
    public function getProducts($limit = 250, $pageInfo = null)
    {
        // When using page_info, don't send limit (it's embedded in page_info)
        if ($pageInfo) {
            $endpoint = "products.json?page_info={$pageInfo}";
        } else {
            $endpoint = "products.json?limit={$limit}";
        }

        $response = $this->request($endpoint);
        $data = $response['data'];
        $headers = $response['headers'];

        return [
            'products' => $data['products'] ?? [],
            'link' => $headers['Link'][0] ?? $headers['link'][0] ?? null,
        ];
    }

    /**
     * Get all products (all pages)
     */
    public function getAllProducts()
    {
        $allProducts = [];
        $pageInfo = null;

        do {
            $data = $this->getProducts(250, $pageInfo);
            $products = $data['products'];

            if (!empty($products)) {
                $allProducts = array_merge($allProducts, $products);
            }

            // Extract next page info from Link header
            $pageInfo = $this->extractNextPageInfo($data['link'] ?? null);

            // Sleep to respect rate limits (2 requests per second)
            usleep(500000); // 0.5 seconds

        } while ($pageInfo !== null && !empty($products));

        return $allProducts;
    }

    /**
     * Get all customers (paginated)
     */
    public function getCustomers($limit = 250, $pageInfo = null)
    {
        if ($pageInfo) {
            $endpoint = "customers.json?page_info={$pageInfo}";
        } else {
            $endpoint = "customers.json?limit={$limit}";
        }

        $response = $this->request($endpoint);
        $data = $response['data'];
        $headers = $response['headers'];

        return [
            'customers' => $data['customers'] ?? [],
            'link' => $headers['Link'][0] ?? null,
        ];
    }

    /**
     * Get all customers (all pages)
     */
    public function getAllCustomers()
    {
        $allCustomers = [];
        $pageInfo = null;

        do {
            $data = $this->getCustomers(250, $pageInfo);
            $customers = $data['customers'];

            if (!empty($customers)) {
                $allCustomers = array_merge($allCustomers, $customers);
            }

            $pageInfo = $this->extractNextPageInfo($data['link'] ?? null);
            usleep(500000);

        } while ($pageInfo !== null && !empty($customers));

        return $allCustomers;
    }

    /**
     * Get all orders (paginated)
     */
    public function getOrders($limit = 250, $pageInfo = null, $status = 'any')
    {
        if ($pageInfo) {
            $endpoint = "orders.json?page_info={$pageInfo}";
        } else {
            $endpoint = "orders.json?limit={$limit}&status={$status}";
        }

        $response = $this->request($endpoint);
        $data = $response['data'];
        $headers = $response['headers'];

        return [
            'orders' => $data['orders'] ?? [],
            'link' => $headers['Link'][0] ?? null,
        ];
    }

    /**
     * Get all orders (all pages)
     */
    public function getAllOrders($status = 'any')
    {
        $allOrders = [];
        $pageInfo = null;

        do {
            $data = $this->getOrders(250, $pageInfo, $status);
            $orders = $data['orders'];

            if (!empty($orders)) {
                $allOrders = array_merge($allOrders, $orders);
            }

            $pageInfo = $this->extractNextPageInfo($data['link'] ?? null);
            usleep(500000);

        } while ($pageInfo !== null && !empty($orders));

        return $allOrders;
    }

    /**
     * Get all collections (paginated)
     */
    public function getCollections($limit = 250, $pageInfo = null)
    {
        if ($pageInfo) {
            $endpoint = "custom_collections.json?page_info={$pageInfo}";
        } else {
            $endpoint = "custom_collections.json?limit={$limit}";
        }

        $response = $this->request($endpoint);
        $data = $response['data'];
        $headers = $response['headers'];

        return [
            'collections' => $data['custom_collections'] ?? [],
            'link' => $headers['Link'][0] ?? null,
        ];
    }

    /**
     * Get all collections (all pages)
     */
    public function getAllCollections()
    {
        $allCollections = [];
        $pageInfo = null;

        do {
            $data = $this->getCollections(250, $pageInfo);
            $collections = $data['collections'];

            if (!empty($collections)) {
                $allCollections = array_merge($allCollections, $collections);
            }

            $pageInfo = $this->extractNextPageInfo($data['link'] ?? null);
            usleep(500000);

        } while ($pageInfo !== null && !empty($collections));

        return $allCollections;
    }

    /**
     * Extract next page info from Link header
     */
    public function extractNextPageInfo($linkHeader)
    {
        if (!$linkHeader) {
            return null;
        }

        // Parse Link header to extract page_info for next page
        // Example: <https://store.myshopify.com/admin/api/2026-01/products.json?page_info=abc123>; rel="next"
        if (preg_match('/<[^>]+page_info=([^>&]+)>;\s*rel="next"/', $linkHeader, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Download image from URL
     */
    public function downloadImage($imageUrl)
    {
        try {
            $response = Http::timeout(30)->get($imageUrl);

            if ($response->successful()) {
                return $response->body();
            }
        } catch (\Exception $e) {
            Log::error('Failed to download image', [
                'url' => $imageUrl,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }
}
