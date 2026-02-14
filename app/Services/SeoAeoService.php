<?php

namespace App\Services;

use App\Models\SeoMeta;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SeoAeoService
{
    protected string $apiKey;
    protected string $model;
    protected string $apiUrl;
    protected int $maxTokens;
    protected array $siteConfig;

    public function __construct()
    {
        $settings = app(SettingsService::class);

        $this->apiKey = $settings->get('anthropic_api_key') ?: config('seo.anthropic.api_key', '');
        $this->model = config('seo.anthropic.model');
        $this->maxTokens = config('seo.anthropic.max_tokens');
        $this->apiUrl = config('seo.anthropic.api_url');
        $this->siteConfig = config('seo.site');
    }

    public function generate(Model $model, bool $force = false): ?SeoMeta
    {
        $existing = $model->seoMeta;
        if ($existing && $existing->is_manually_edited && !$force) {
            Log::info("SEO generation skipped for {$model->getMorphClass()} #{$model->id} (manually edited)");
            return $existing;
        }

        $prompt = $this->buildPrompt($model);
        $response = $this->callAnthropic($prompt);

        if (!$response) {
            return null;
        }

        return $this->saveResults($model, $response);
    }

    protected function buildPrompt(Model $model): array
    {
        return [
            'system' => $this->buildSystemPrompt(),
            'user' => $this->buildUserPrompt($model),
        ];
    }

    protected function buildSystemPrompt(): string
    {
        $siteName = $this->siteConfig['name'] ?? 'AD Perfumes';
        $siteUrl = $this->siteConfig['url'] ?? 'https://adperfumes.ae';
        $country = $this->siteConfig['target_country'] ?? 'UAE';
        $currency = $this->siteConfig['currency'] ?? 'AED';
        $industry = $this->siteConfig['industry'] ?? 'Luxury Perfumes & Fragrances';
        $brandVoice = $this->siteConfig['brand_voice'] ?? '';

        return <<<PROMPT
You are an expert SEO and AEO (Answer Engine Optimization) specialist for {$siteName}, a luxury fragrance e-commerce store based in the {$country}.

TARGET MARKET: {$country} (Dubai, Abu Dhabi, Sharjah, etc.)
CURRENCY: {$currency}
LANGUAGE: English (primary), with awareness of Arabic search patterns
INDUSTRY: {$industry}
SITE URL: {$siteUrl}
BRAND VOICE: {$brandVoice}

You MUST respond with ONLY a valid JSON object. No markdown fences, no explanation, no text before or after. Return this exact structure:

{
  "meta_title": "string (max 60 chars, primary keyword near start, brand name at end)",
  "meta_description": "string (max 155 chars, compelling with CTA, include keyword)",
  "canonical_url": "string (full URL)",
  "robots": "index, follow",
  "og_title": "string (max 95 chars)",
  "og_description": "string (max 200 chars, engaging for social sharing)",
  "og_type": "product|article|website",
  "og_image": null,
  "twitter_card": "summary_large_image",
  "twitter_title": "string (max 70 chars)",
  "twitter_description": "string (max 200 chars)",
  "keywords": {
    "primary": ["2-3 main keywords"],
    "secondary": ["3-5 supporting keywords"],
    "long_tail": ["5-8 long-tail phrases relevant to {$country} market"],
    "lsi": ["5-8 LSI/related terms"],
    "questions": ["5-8 questions people search for"]
  },
  "aeo_data": {
    "featured_snippet": "Direct answer optimized for Google featured snippet (40-60 words)",
    "people_also_ask": [
      {"question": "string", "answer": "2-3 sentence concise answer"}
    ],
    "voice_search": "Natural conversational answer for voice assistants (30-40 words)",
    "ai_overview": "Authoritative summary for Google AI Overview (100-150 words with facts)",
    "key_facts": ["5-8 standalone factual statements"],
    "entity_map": {"entity_name": "entity_type"}
  },
  "schema_markup": {
    "primary": {valid JSON-LD schema object},
    "breadcrumb": {BreadcrumbList JSON-LD},
    "faq": {FAQPage JSON-LD with real questions},
    "speakable": {SpeakableSpecification JSON-LD}
  },
  "social_media": {
    "twitter_post": "string (under 280 chars with hashtags)",
    "linkedin_post": "string (150-300 words, professional tone)",
    "facebook_post": "string (100-200 words, engaging tone)",
    "pinterest_description": "string (200-500 chars, keyword-rich)",
    "hashtags": ["relevant", "hashtags"]
  },
  "scoring": {
    "seo_score": 0-100,
    "aeo_score": 0-100,
    "content_quality": 0-100,
    "technical_seo": 0-100,
    "overall_score": 0-100,
    "issues": ["list of specific issues found"],
    "improvements": ["list of prioritized improvement suggestions"]
  },
  "content_optimization": {
    "internal_links": [{"anchor": "text", "url": "/path", "context": "why this link"}],
    "external_link_suggestions": [{"anchor": "text", "topic": "what to link to", "type": "reference|authority"}],
    "missing_topics": ["topics the content should cover"],
    "content_gaps": ["areas where content could be stronger"]
  }
}

CONTENT TYPE RULES:
- For PRODUCT: Generate Product schema (JSON-LD) with name, description, brand, offers (price in {$currency}), image, availability. Include perfume notes context.
- For BRAND: Generate Organization/Brand schema. Focus on brand authority keywords.
- For CATEGORY: Generate CollectionPage schema. Focus on browsing/discovery intent.
- For BLOG_POST: Generate Article schema with author, datePublished, publisher. If content is empty, generate a full 1200-2000 word blog post in the content_optimization.content_gaps field.

All scores must be honest and reflect actual content quality. All schemas must be valid JSON-LD that passes Google's Rich Results Test.
PROMPT;
    }

    protected function buildUserPrompt(Model $model): string
    {
        $data = [
            'site_name' => $this->siteConfig['name'] ?? 'AD Perfumes',
            'site_url' => $this->siteConfig['url'] ?? 'https://adperfumes.ae',
            'content_type' => $model->seoContentType(),
            'title' => $model->seoTitle(),
            'content' => mb_substr($model->seoContent(), 0, 5000),
            'url' => $model->seoUrl(),
            'category' => $model->seoCategory(),
            'images' => $model->seoImages(),
        ];

        // Product-specific context
        if ($model instanceof \App\Models\Product) {
            $model->loadMissing(['brand', 'topNotes', 'middleNotes', 'baseNotes', 'accords', 'categories']);
            $data['brand'] = $model->brand?->name;
            $data['price'] = $model->price;
            $data['original_price'] = $model->original_price;
            $data['currency'] = $this->siteConfig['currency'] ?? 'AED';
            $data['is_on_sale'] = $model->on_sale;
            $data['gtin'] = $model->gtin;
            $data['notes'] = [
                'top' => $model->topNotes->pluck('name')->toArray(),
                'middle' => $model->middleNotes->pluck('name')->toArray(),
                'base' => $model->baseNotes->pluck('name')->toArray(),
            ];
            $data['accords'] = $model->accords->pluck('name')->toArray();
            $data['categories'] = $model->categories->pluck('name')->toArray();
        }

        // Brand-specific context
        if ($model instanceof \App\Models\Brand) {
            $data['product_count'] = $model->products()->where('status', true)->count();
        }

        // Category-specific context
        if ($model instanceof \App\Models\Category) {
            $data['parent_category'] = $model->parent?->name;
            $data['product_count'] = $model->products()->where('status', true)->count();
        }

        // BlogPost-specific context
        if ($model instanceof \App\Models\BlogPost) {
            $data['author'] = $model->author;
            $data['published_at'] = $model->published_at?->toISOString();
            $data['target_keyword'] = $model->meta_data['target_keyword'] ?? '';
        }

        // Existing pages for internal linking context
        $data['existing_pages'] = $this->getExistingPages();

        return "Generate comprehensive SEO and AEO data for the following {$data['content_type']}:\n\n"
            . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    protected function getExistingPages(): array
    {
        $pages = [];

        $pages[] = ['url' => '/', 'title' => 'Home'];
        $pages[] = ['url' => '/products', 'title' => 'All Products'];
        $pages[] = ['url' => '/brands', 'title' => 'All Brands'];
        $pages[] = ['url' => '/blog', 'title' => 'Blog'];

        // Top products
        $products = \App\Models\Product::where('status', true)
            ->select('name', 'slug')
            ->limit(20)
            ->get();

        foreach ($products as $p) {
            $pages[] = ['url' => "/products/{$p->slug}", 'title' => $p->name];
        }

        // Brands
        $brands = \App\Models\Brand::where('status', true)
            ->select('name', 'slug')
            ->get();

        foreach ($brands as $b) {
            $pages[] = ['url' => "/brands/{$b->slug}", 'title' => $b->name];
        }

        return $pages;
    }

    protected function callAnthropic(array $prompt): ?array
    {
        if (empty($this->apiKey)) {
            Log::error('SeoAeoService: Anthropic API key not configured');
            return null;
        }

        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])
            ->timeout(120)
            ->retry(3, 5000, function ($exception) {
                if ($exception instanceof \Illuminate\Http\Client\RequestException) {
                    $status = $exception->response?->status();
                    return in_array($status, [429, 500, 502, 503]);
                }
                return false;
            })
            ->post($this->apiUrl, [
                'model' => $this->model,
                'max_tokens' => $this->maxTokens,
                'system' => $prompt['system'],
                'messages' => [
                    ['role' => 'user', 'content' => $prompt['user']],
                ],
            ]);

            if ($response->successful()) {
                $content = $response->json('content.0.text');
                return $this->extractJson($content);
            }

            Log::error('SeoAeoService API error', [
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 1000),
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('SeoAeoService exception', [
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    protected function extractJson(string $content): ?array
    {
        // Try direct JSON parse
        $decoded = json_decode($content, true);
        if ($decoded !== null) {
            return $decoded;
        }

        // Try extracting from markdown code blocks
        if (preg_match('/```(?:json)?\s*(\{[\s\S]*\})\s*```/', $content, $matches)) {
            $decoded = json_decode($matches[1], true);
            if ($decoded !== null) {
                return $decoded;
            }
        }

        // Try finding JSON object in the content
        if (preg_match('/(\{[\s\S]*\})/', $content, $matches)) {
            $decoded = json_decode($matches[1], true);
            if ($decoded !== null) {
                return $decoded;
            }
        }

        Log::warning('SeoAeoService: Failed to parse JSON from AI response', [
            'content_preview' => mb_substr($content, 0, 500),
        ]);

        return null;
    }

    protected function saveResults(Model $model, array $data): SeoMeta
    {
        // Extract nested SEO fields from the AI response
        $seoData = $data['seo'] ?? $data;

        return SeoMeta::updateOrCreate(
            [
                'seoable_type' => $model->getMorphClass(),
                'seoable_id' => $model->id,
            ],
            [
                'meta_title' => $seoData['meta_title'] ?? null,
                'meta_description' => $seoData['meta_description'] ?? null,
                'canonical_url' => $seoData['canonical_url'] ?? $model->seoUrl(),
                'robots' => $seoData['robots'] ?? 'index, follow',
                'og_title' => $seoData['og_title'] ?? $seoData['meta_title'] ?? null,
                'og_description' => $seoData['og_description'] ?? $seoData['meta_description'] ?? null,
                'og_type' => $seoData['og_type'] ?? 'website',
                'og_image' => $seoData['og_image'] ?? null,
                'twitter_card' => $seoData['twitter_card'] ?? 'summary_large_image',
                'twitter_title' => $seoData['twitter_title'] ?? $seoData['meta_title'] ?? null,
                'twitter_description' => $seoData['twitter_description'] ?? $seoData['meta_description'] ?? null,
                'keywords' => $seoData['keywords'] ?? null,
                'aeo_data' => $seoData['aeo_data'] ?? $data['aeo'] ?? null,
                'schema_markup' => $seoData['schema_markup'] ?? $data['schema'] ?? null,
                'social_media' => $seoData['social_media'] ?? $data['social_media'] ?? null,
                'scoring' => $seoData['scoring'] ?? $data['scoring'] ?? null,
                'content_optimization' => $seoData['content_optimization'] ?? null,
                'last_generated_at' => now(),
            ]
        );
    }
}
