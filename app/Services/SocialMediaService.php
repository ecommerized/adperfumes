<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Discount;
use App\Models\Product;
use App\Models\SocialMediaPost;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SocialMediaService
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
        $this->apiUrl = config('seo.anthropic.api_url');
        $this->maxTokens = 2048;
        $this->siteConfig = config('seo.site');
    }

    /**
     * Generate a caption + hashtags using Claude AI.
     */
    public function generateCaption(string $type, array $context = []): ?array
    {
        $systemPrompt = $this->buildCaptionSystemPrompt();
        $userPrompt = $this->buildCaptionUserPrompt($type, $context);

        $response = $this->callAnthropic($systemPrompt, $userPrompt);

        if (!$response || empty($response['caption'])) {
            Log::warning('SocialMediaService: Failed to generate caption', ['type' => $type]);
            return null;
        }

        return [
            'caption' => $response['caption'],
            'hashtags' => $response['hashtags'] ?? '',
            'suggested_cta' => $response['suggested_cta'] ?? '',
        ];
    }

    /**
     * Auto-pilot: pick content and generate a complete scheduled post.
     */
    public function generateAutoPost(): ?SocialMediaPost
    {
        $settings = app(SettingsService::class);

        $preferredTypes = json_decode(
            $settings->get('social_auto_post_types', '["product_promo","offer","brand_story"]'),
            true
        ) ?: ['product_promo', 'offer', 'brand_story'];

        $type = $preferredTypes[array_rand($preferredTypes)];

        $context = $this->buildAutoContext($type);

        if (empty($context)) {
            Log::info('SocialMediaService: No suitable content for auto-post type', ['type' => $type]);
            return null;
        }

        $result = $this->generateCaption($type, $context);

        if (!$result) {
            return null;
        }

        $scheduledAt = $this->determineAutoPostTime();

        $post = SocialMediaPost::create([
            'type' => $type,
            'caption' => $result['caption'],
            'hashtags' => $result['hashtags'],
            'image_path' => $context['image_path'] ?? null,
            'product_id' => $context['product_id'] ?? null,
            'discount_id' => $context['discount_id'] ?? null,
            'status' => 'scheduled',
            'scheduled_at' => $scheduledAt,
            'source' => 'auto_pilot',
            'created_by' => 'auto_pilot',
            'meta_data' => [
                'generation_model' => $this->model,
                'generated_at' => now()->toISOString(),
                'suggested_cta' => $result['suggested_cta'] ?? '',
            ],
        ]);

        Log::info("SocialMediaService: Auto-post created #{$post->id}", [
            'type' => $type,
            'scheduled_at' => $scheduledAt->toISOString(),
        ]);

        return $post;
    }

    // ── Auto Context Builders ────────────────────

    protected function buildAutoContext(string $type): array
    {
        return match ($type) {
            'product_promo' => $this->buildProductPromoContext(),
            'offer' => $this->buildOfferContext(),
            'brand_story' => $this->buildBrandStoryContext(),
            default => [],
        };
    }

    protected function buildProductPromoContext(): array
    {
        // Avoid products posted about in last 14 days
        $recentProductIds = SocialMediaPost::where('type', 'product_promo')
            ->whereNotNull('product_id')
            ->where('created_at', '>=', now()->subDays(14))
            ->pluck('product_id')
            ->toArray();

        $product = Product::where('status', true)
            ->whereNotIn('id', $recentProductIds)
            ->with(['brand', 'categories', 'topNotes', 'middleNotes', 'baseNotes'])
            ->inRandomOrder()
            ->first();

        if (!$product) {
            $product = Product::where('status', true)
                ->with(['brand', 'categories', 'topNotes', 'middleNotes', 'baseNotes'])
                ->inRandomOrder()
                ->first();
        }

        if (!$product) {
            return [];
        }

        return [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_description' => mb_substr($product->description ?? '', 0, 500),
            'brand_name' => $product->brand?->name,
            'price' => $product->price,
            'original_price' => $product->original_price,
            'on_sale' => $product->on_sale,
            'categories' => $product->categories->pluck('name')->toArray(),
            'notes' => [
                'top' => $product->topNotes->pluck('name')->toArray(),
                'middle' => $product->middleNotes->pluck('name')->toArray(),
                'base' => $product->baseNotes->pluck('name')->toArray(),
            ],
            'product_url' => url("/products/{$product->slug}"),
            'image_path' => $product->image,
        ];
    }

    protected function buildOfferContext(): array
    {
        $discount = Discount::active()->available()->inRandomOrder()->first();

        if (!$discount) {
            return $this->buildProductPromoContext();
        }

        return [
            'discount_id' => $discount->id,
            'discount_code' => $discount->code,
            'discount_type' => $discount->type,
            'discount_value' => $discount->value,
            'formatted_value' => $discount->formatted_value,
            'description' => $discount->description,
            'min_purchase' => $discount->min_purchase_amount,
            'expires_at' => $discount->expires_at?->format('M d, Y'),
            'image_path' => null,
        ];
    }

    protected function buildBrandStoryContext(): array
    {
        $brand = Brand::where('status', true)
            ->whereNotNull('description')
            ->with('products')
            ->inRandomOrder()
            ->first();

        if (!$brand) {
            return [];
        }

        $topProduct = $brand->products()
            ->where('status', true)
            ->inRandomOrder()
            ->first();

        return [
            'brand_name' => $brand->name,
            'brand_description' => mb_substr($brand->description ?? '', 0, 500),
            'product_count' => $brand->products()->where('status', true)->count(),
            'featured_product' => $topProduct?->name,
            'brand_url' => url("/brands/{$brand->slug}"),
            'image_path' => $topProduct?->image ?? $brand->logo,
        ];
    }

    // ── Scheduling ───────────────────────────────

    protected function determineAutoPostTime(): \Carbon\Carbon
    {
        $settings = app(SettingsService::class);

        $preferredHours = json_decode(
            $settings->get('social_auto_post_hours', '[10, 14, 18]'),
            true
        ) ?: [10, 14, 18];

        $now = now()->timezone('Asia/Dubai');

        foreach (range(0, 6) as $dayOffset) {
            foreach ($preferredHours as $hour) {
                $candidate = $now->copy()
                    ->addDays($dayOffset)
                    ->setHour($hour)
                    ->setMinute(0)
                    ->setSecond(0);

                if ($candidate->isFuture()) {
                    $conflict = SocialMediaPost::where('status', 'scheduled')
                        ->whereBetween('scheduled_at', [
                            $candidate->copy()->subMinutes(30),
                            $candidate->copy()->addMinutes(30),
                        ])
                        ->exists();

                    if (!$conflict) {
                        return $candidate->utc();
                    }
                }
            }
        }

        return now()->addHours(2);
    }

    // ── AI Prompt Builders ───────────────────────

    protected function buildCaptionSystemPrompt(): string
    {
        $siteName = $this->siteConfig['name'] ?? 'AD Perfumes';
        $country = $this->siteConfig['target_country'] ?? 'UAE';
        $brandVoice = $this->siteConfig['brand_voice'] ?? '';
        $siteUrl = $this->siteConfig['url'] ?? 'https://adperfumes.ae';

        return <<<PROMPT
You are a social media marketing expert for {$siteName}, a luxury fragrance e-commerce store in the {$country}.

BRAND VOICE: {$brandVoice}
WEBSITE: {$siteUrl}
TARGET AUDIENCE: Fragrance enthusiasts in the UAE (Dubai, Abu Dhabi, Sharjah). Mix of Arabic and international customers.
CURRENCY: AED

GUIDELINES:
- Write engaging, scroll-stopping Facebook captions
- Use emojis strategically (2-4 per post, not excessive)
- Include a clear call-to-action (shop now, link in bio, DM us, etc.)
- For product promos: highlight unique selling points, fragrance notes, occasion
- For offers: create urgency, emphasize value, include discount code if available
- For brand stories: be aspirational, share interesting brand heritage/facts
- Captions should be 100-250 words (concise but engaging)
- Generate 8-12 relevant hashtags mixing broad (#perfume, #fragrance) with niche (#UAEperfumes, #luxuryscent)
- Always include #ADPerfumes in hashtags

Respond with ONLY valid JSON:
{
  "caption": "The full Facebook post caption text",
  "hashtags": "#hashtag1 #hashtag2 #hashtag3 ...",
  "suggested_cta": "A short call-to-action suggestion"
}
PROMPT;
    }

    protected function buildCaptionUserPrompt(string $type, array $context): string
    {
        $typeLabel = match ($type) {
            'product_promo' => 'Product Promotion',
            'offer' => 'Offer / Discount Announcement',
            'brand_story' => 'Brand Storytelling',
            'custom' => 'Custom Post',
            default => 'Social Media Post',
        };

        $contextJson = json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return "Generate a Facebook {$typeLabel} post using this context:\n\n{$contextJson}";
    }

    // ── Anthropic API (same pattern as AutoBlogService) ──

    protected function callAnthropic(string $systemPrompt, string $userPrompt): ?array
    {
        if (empty($this->apiKey)) {
            Log::error('SocialMediaService: Anthropic API key not configured');
            return null;
        }

        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])
            ->timeout(120)
            ->retry(2, 10000, function ($exception) {
                if ($exception instanceof \Illuminate\Http\Client\RequestException) {
                    $status = $exception->response?->status();
                    return in_array($status, [429, 500, 502, 503]);
                }
                return false;
            })
            ->post($this->apiUrl, [
                'model' => $this->model,
                'max_tokens' => $this->maxTokens,
                'system' => $systemPrompt,
                'messages' => [
                    ['role' => 'user', 'content' => $userPrompt],
                ],
            ]);

            if ($response->successful()) {
                $content = $response->json('content.0.text');
                return $this->extractJson($content);
            }

            Log::error('SocialMediaService API error', [
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 1000),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('SocialMediaService exception', ['message' => $e->getMessage()]);
            return null;
        }
    }

    protected function extractJson(string $content): ?array
    {
        $decoded = json_decode($content, true);
        if ($decoded !== null) {
            return $decoded;
        }

        if (preg_match('/```(?:json)?\s*(\{[\s\S]*\})\s*```/', $content, $matches)) {
            $decoded = json_decode($matches[1], true);
            if ($decoded !== null) {
                return $decoded;
            }
        }

        if (preg_match('/(\{[\s\S]*\})/', $content, $matches)) {
            $decoded = json_decode($matches[1], true);
            if ($decoded !== null) {
                return $decoded;
            }
        }

        Log::warning('SocialMediaService: Failed to parse JSON', [
            'content_preview' => mb_substr($content, 0, 500),
        ]);

        return null;
    }
}
