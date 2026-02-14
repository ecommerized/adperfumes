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
    protected ?string $openaiApiKey;

    public function __construct()
    {
        $settings = app(SettingsService::class);
        $this->apiKey = $settings->get('anthropic_api_key') ?: config('seo.anthropic.api_key', '');
        $this->model = config('seo.anthropic.model');
        $this->apiUrl = config('seo.anthropic.api_url');
        $this->maxTokens = 2048;
        $this->siteConfig = config('seo.site');
        $this->openaiApiKey = $settings->get('openai_api_key') ?: config('services.openai.api_key');
    }

    /**
     * Generate a caption + hashtags + image using AI.
     */
    public function generateCaption(string $type, array $context = [], bool $generateImage = false): ?array
    {
        $systemPrompt = $this->buildCaptionSystemPrompt();
        $userPrompt = $this->buildCaptionUserPrompt($type, $context);

        $response = $this->callAnthropic($systemPrompt, $userPrompt);

        if (!$response || empty($response['caption'])) {
            Log::warning('SocialMediaService: Failed to generate caption', ['type' => $type]);
            return null;
        }

        $result = [
            'caption' => $response['caption'],
            'hashtags' => $response['hashtags'] ?? '',
            'suggested_cta' => $response['suggested_cta'] ?? '',
        ];

        // Generate image if requested
        if ($generateImage) {
            $imagePath = $this->generateImage($type, $context);
            if ($imagePath) {
                $result['image_path'] = $imagePath;
            }
        }

        return $result;
    }

    /**
     * Generate a promotional image using product image or DALL-E 3.
     */
    public function generateImage(string $type, array $context = []): ?string
    {
        // If we have a product image, create a branded composite design
        if (!empty($context['product_image_path'])) {
            Log::info('SocialMediaService: Using actual product image for design');
            return $this->createProductImageDesign($type, $context);
        }

        // Otherwise, use DALL-E 3 to generate from scratch
        if (empty($this->openaiApiKey)) {
            Log::error('SocialMediaService: OpenAI API key not configured');
            return null;
        }

        $prompt = $this->buildImagePrompt($type, $context);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->openaiApiKey,
                'Content-Type' => 'application/json',
            ])
            ->timeout(120)
            ->post('https://api.openai.com/v1/images/generations', [
                'model' => 'dall-e-3',
                'prompt' => $prompt,
                'n' => 1,
                'size' => '1024x1024',
                'quality' => 'standard',
            ]);

            if ($response->successful()) {
                $imageUrl = $response->json('data.0.url');

                if ($imageUrl) {
                    // Download and save the image
                    $imagePath = $this->downloadAndSaveImage($imageUrl);
                    Log::info('SocialMediaService: Image generated successfully', ['path' => $imagePath]);
                    return $imagePath;
                }
            }

            Log::error('SocialMediaService: DALL-E API error', [
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 500),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('SocialMediaService: Image generation exception', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Create a branded social media image using actual product image.
     */
    protected function createProductImageDesign(string $type, array $context): ?string
    {
        try {
            $productImagePath = $context['product_image_path'];

            // Get full path to product image
            $fullPath = \Storage::disk('public')->path($productImagePath);

            if (!file_exists($fullPath)) {
                Log::warning('SocialMediaService: Product image not found', ['path' => $productImagePath]);
                return null;
            }

            // Create 1024x1024 canvas with branded gradient background
            $canvas = imagecreatetruecolor(1024, 1024);

            // Brand colors: #C9A96E (gold), #0A0A0A (black), #FAFAF8 (ivory)
            $gold = imagecolorallocate($canvas, 201, 169, 110);
            $black = imagecolorallocate($canvas, 10, 10, 10);
            $ivory = imagecolorallocate($canvas, 250, 250, 248);
            $darkGold = imagecolorallocate($canvas, 160, 130, 70);

            // Create elegant gradient background (black to dark gold)
            for ($y = 0; $y < 1024; $y++) {
                $ratio = $y / 1024;
                $r = (int)(10 + (160 - 10) * $ratio);
                $g = (int)(10 + (130 - 10) * $ratio);
                $b = (int)(10 + (70 - 10) * $ratio);
                $color = imagecolorallocate($canvas, $r, $g, $b);
                imagefilledrectangle($canvas, 0, $y, 1024, $y + 1, $color);
            }

            // Add subtle texture/noise for luxury feel
            for ($i = 0; $i < 500; $i++) {
                $x = rand(0, 1024);
                $y = rand(0, 1024);
                $alpha = rand(20, 50);
                $noise = imagecolorallocatealpha($canvas, 255, 255, 255, $alpha);
                imagesetpixel($canvas, $x, $y, $noise);
            }

            // Load product image
            $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
            $productImg = match($ext) {
                'png' => imagecreatefrompng($fullPath),
                'jpg', 'jpeg' => imagecreatefromjpeg($fullPath),
                'gif' => imagecreatefromgif($fullPath),
                'webp' => imagecreatefromwebp($fullPath),
                default => null,
            };

            if (!$productImg) {
                Log::warning('SocialMediaService: Could not load product image');
                imagedestroy($canvas);
                return null;
            }

            // Get product image dimensions
            $prodWidth = imagesx($productImg);
            $prodHeight = imagesy($productImg);

            // Calculate size to fit product (60% of canvas)
            $maxSize = (int)(1024 * 0.6);
            $scale = min($maxSize / $prodWidth, $maxSize / $prodHeight);
            $newProdWidth = (int)($prodWidth * $scale);
            $newProdHeight = (int)($prodHeight * $scale);

            // Center the product image
            $prodX = (int)((1024 - $newProdWidth) / 2);
            $prodY = (int)((1024 - $newProdHeight) / 2);

            // Add subtle shadow behind product
            $shadowOffset = 15;
            $shadow = imagecolorallocatealpha($canvas, 0, 0, 0, 50);
            imagefilledellipse($canvas, $prodX + ($newProdWidth / 2) + $shadowOffset, $prodY + ($newProdHeight / 2) + $shadowOffset, $newProdWidth - 50, $newProdHeight - 50, $shadow);

            // Place product image on canvas
            imagecopyresampled(
                $canvas, $productImg,
                $prodX, $prodY, 0, 0,
                $newProdWidth, $newProdHeight,
                $prodWidth, $prodHeight
            );

            imagedestroy($productImg);

            // Add decorative elements based on post type
            if ($type === 'offer' && !empty($context['discount_value'])) {
                // Add discount badge (top-right corner)
                $badgeSize = 150;
                $badgeX = 1024 - $badgeSize - 50;
                $badgeY = 50;

                // Draw golden circle badge
                imagefilledellipse($canvas, $badgeX + ($badgeSize / 2), $badgeY + ($badgeSize / 2), $badgeSize, $badgeSize, $gold);
                imageellipse($canvas, $badgeX + ($badgeSize / 2), $badgeY + ($badgeSize / 2), $badgeSize, $badgeSize, $ivory);

                // Add discount text (simplified - would need GD font)
                $discountText = $context['discount_value'];
                imagestring($canvas, 5, $badgeX + 30, $badgeY + 60, strtoupper(substr($discountText, 0, 10)), $black);
                imagestring($canvas, 5, $badgeX + 45, $badgeY + 80, 'OFF', $black);
            }

            // Add golden accent lines at top and bottom
            imagefilledrectangle($canvas, 0, 30, 1024, 35, $gold);
            imagefilledrectangle($canvas, 0, 1024 - 35, 1024, 1024 - 30, $gold);

            // Save to temp file
            $filename = 'social-product-' . time() . '-' . uniqid() . '.png';
            $tempPath = sys_get_temp_dir() . '/' . $filename;
            imagepng($canvas, $tempPath, 9);
            imagedestroy($canvas);

            // Overlay brand logo
            $finalPath = $this->overlayBrandLogo($tempPath);

            if ($finalPath) {
                // Move to storage
                $storagePath = 'social-posts/' . $filename;
                \Storage::disk('public')->put($storagePath, file_get_contents($finalPath));
                @unlink($tempPath);
                @unlink($finalPath);

                Log::info('SocialMediaService: Product image design created', ['path' => $storagePath]);
                return $storagePath;
            }

            // Fallback: save without logo
            $storagePath = 'social-posts/' . $filename;
            \Storage::disk('public')->put($storagePath, file_get_contents($tempPath));
            @unlink($tempPath);

            return $storagePath;
        } catch (\Exception $e) {
            Log::error('SocialMediaService: Failed to create product image design', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Download image from URL, overlay brand logo, and save to storage.
     */
    protected function downloadAndSaveImage(string $url): ?string
    {
        try {
            $imageContent = file_get_contents($url);

            if (!$imageContent) {
                return null;
            }

            $filename = 'social-ai-' . time() . '-' . uniqid() . '.png';
            $path = 'social-posts/' . $filename;

            // Save the base image first
            $tempPath = sys_get_temp_dir() . '/' . $filename;
            file_put_contents($tempPath, $imageContent);

            // Overlay brand logo
            $finalPath = $this->overlayBrandLogo($tempPath);

            if ($finalPath) {
                // Move to storage
                \Storage::disk('public')->put($path, file_get_contents($finalPath));
                @unlink($tempPath);
                @unlink($finalPath);
                return $path;
            }

            // Fallback: save without logo if overlay fails
            \Storage::disk('public')->put($path, $imageContent);
            @unlink($tempPath);

            return $path;
        } catch (\Exception $e) {
            Log::error('SocialMediaService: Failed to download image', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Overlay brand logo on the generated image.
     */
    protected function overlayBrandLogo(string $imagePath): ?string
    {
        try {
            $settings = app(SettingsService::class);
            $logoPath = $settings->get('store_logo');

            if (empty($logoPath)) {
                return $imagePath; // No logo to overlay
            }

            $logoFullPath = \Storage::disk('public')->path($logoPath);

            if (!file_exists($logoFullPath)) {
                return $imagePath; // Logo file not found
            }

            // Load the base image
            $image = imagecreatefrompng($imagePath);
            if (!$image) {
                return $imagePath;
            }

            // Load the logo
            $logoExt = pathinfo($logoFullPath, PATHINFO_EXTENSION);
            $logo = match(strtolower($logoExt)) {
                'png' => imagecreatefrompng($logoFullPath),
                'jpg', 'jpeg' => imagecreatefromjpeg($logoFullPath),
                'gif' => imagecreatefromgif($logoFullPath),
                default => null,
            };

            if (!$logo) {
                return $imagePath;
            }

            // Get dimensions
            $imageWidth = imagesx($image);
            $imageHeight = imagesy($image);
            $logoWidth = imagesx($logo);
            $logoHeight = imagesy($logo);

            // Resize logo to 15% of image width
            $newLogoWidth = (int)($imageWidth * 0.15);
            $newLogoHeight = (int)($logoHeight * ($newLogoWidth / $logoWidth));

            // Position logo at bottom-right with 30px padding
            $x = $imageWidth - $newLogoWidth - 30;
            $y = $imageHeight - $newLogoHeight - 30;

            // Resize and merge
            imagecopyresampled(
                $image, $logo,
                $x, $y, 0, 0,
                $newLogoWidth, $newLogoHeight,
                $logoWidth, $logoHeight
            );

            // Save
            $outputPath = sys_get_temp_dir() . '/branded-' . basename($imagePath);
            imagepng($image, $outputPath, 9);

            // Cleanup
            imagedestroy($image);
            imagedestroy($logo);

            return $outputPath;
        } catch (\Exception $e) {
            Log::error('SocialMediaService: Failed to overlay logo', ['message' => $e->getMessage()]);
            return $imagePath; // Return original if overlay fails
        }
    }

    /**
     * Build DALL-E 3 image generation prompt.
     */
    protected function buildImagePrompt(string $type, array $context): string
    {
        $basePrompt = "Create a luxury perfume social media post for Instagram/Facebook. ";
        $basePrompt .= "IMPORTANT: Leave bottom-right corner clear (no text or objects) for logo placement. ";
        $basePrompt .= "Style: Ultra-premium, sophisticated, magazine-quality. ";
        $basePrompt .= "Colors: Dominant luxury gold (#C9A96E), obsidian black (#0A0A0A), ivory (#FAFAF8). ";
        $basePrompt .= "Background: Elegant gradient or texture using these exact colors. ";
        $basePrompt .= "Lighting: Professional studio lighting with warm golden highlights. ";
        $basePrompt .= "Quality: Photorealistic, high-end commercial photography. ";

        switch ($type) {
            case 'product_promo':
                $productName = $context['product_name'] ?? 'Luxury Perfume';
                $brandName = $context['brand_name'] ?? '';
                $price = $context['price'] ?? null;

                $prompt = $basePrompt;
                $prompt .= "Subject: Elegant perfume bottle centered or slightly left. ";
                $prompt .= "Product: {$productName}" . ($brandName ? " by {$brandName}" : "") . ". ";
                $prompt .= "Details: Show bottle with premium packaging, golden reflections on glass. ";
                $prompt .= "Composition: Product name in elegant gold serif font (Cormorant Garamond style). ";
                if ($price) {
                    $prompt .= "Price display: 'AED {$price}' in subtle gold text. ";
                }
                $prompt .= "Background: Luxury marble or silk texture in gold and black tones. ";
                $prompt .= "Props: Optional: Scattered gold leaves, precious stones, or silk fabric. ";
                return $prompt;

            case 'offer':
                $discountValue = $context['formatted_value'] ?? $context['discount_value'] ?? '20%';
                $discountCode = $context['discount_code'] ?? '';

                $prompt = $basePrompt;
                $prompt .= "Subject: Special offer announcement with perfume bottles. ";
                $prompt .= "Highlight: Large golden badge displaying '{$discountValue} OFF' in bold elegant typography. ";
                if ($discountCode) {
                    $prompt .= "Code: '{$discountCode}' in prominent golden frame. ";
                }
                $prompt .= "Composition: 1-2 perfume bottles with luxury packaging. ";
                $prompt .= "Background: Dramatic black and gold gradient with bokeh lights. ";
                $prompt .= "Urgency: Subtle 'Limited Time' text in gold. ";
                return $prompt;

            case 'brand_story':
                $brandName = $context['brand_name'] ?? 'Luxury Perfumes';

                $prompt = $basePrompt;
                $prompt .= "Subject: Elegant brand showcase for {$brandName}. ";
                $prompt .= "Composition: 3-4 perfume bottles artfully arranged. ";
                $prompt .= "Style: Timeless, heritage luxury brand aesthetic. ";
                $prompt .= "Background: Premium textured surface (marble, leather, or silk) in gold and black. ";
                $prompt .= "Mood: Aspirational, exclusive, sophisticated. ";
                $prompt .= "Typography: Brand name '{$brandName}' in elegant serif font, gold foil effect. ";
                return $prompt;

            default:
                return $basePrompt . "Create an elegant luxury perfume promotional image with gold and black color scheme.";
        }
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
            'image_path' => $product->image, // For saving to post record
            'product_image_path' => $product->image, // For image generation
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
            'image_path' => $topProduct?->image ?? $brand->logo, // For saving to post record
            'product_image_path' => $topProduct?->image, // For image generation (if available)
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
