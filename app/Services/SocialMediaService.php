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
            $sizeType = $context['size_type'] ?? 'post';
            $dimensions = ($sizeType === 'story') ? [1080, 1920] : [1080, 1080];

            $imagePath = $this->generateImageSize($type, $context, $dimensions[0], $dimensions[1], $sizeType);
            if ($imagePath) {
                $result['image_path'] = $imagePath;
            }
        }

        return $result;
    }

    /**
     * Generate both post (1080x1080) and story (1080x1920) sizes.
     */
    protected function generateBothSizes(string $type, array $context): ?array
    {
        // Generate post size (square)
        $postImage = $this->generateImageSize($type, $context, 1080, 1080, 'post');

        // Generate story size (vertical)
        $storyImage = $this->generateImageSize($type, $context, 1080, 1920, 'story');

        if (!$postImage || !$storyImage) {
            return null;
        }

        return [
            'post' => $postImage,
            'story' => $storyImage,
        ];
    }

    /**
     * Generate a single promotional image (backward compatibility).
     */
    public function generateImage(string $type, array $context = []): ?string
    {
        return $this->generateImageSize($type, $context, 1080, 1080, 'post');
    }

    /**
     * Generate promotional image with specific dimensions.
     */
    protected function generateImageSize(string $type, array $context, int $width, int $height, string $sizeType = 'post'): ?string
    {
        // If we have a product image, create a branded composite design
        if (!empty($context['product_image_path'])) {
            Log::info('SocialMediaService: Using actual product image for design', ['size' => "{$width}x{$height}"]);
            return $this->createProductImageDesign($type, $context, $width, $height, $sizeType);
        }

        // Otherwise, use DALL-E 3 to generate from scratch (only supports 1024x1024)
        if (empty($this->openaiApiKey)) {
            Log::error('SocialMediaService: OpenAI API key not configured');
            return null;
        }

        // DALL-E 3 only supports 1024x1024, 1024x1792, 1792x1024
        $dalleSize = ($width === $height) ? '1024x1024' : '1024x1792';
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
                'size' => $dalleSize,
                'quality' => 'standard',
            ]);

            if ($response->successful()) {
                $imageUrl = $response->json('data.0.url');

                if ($imageUrl) {
                    $imagePath = $this->downloadAndSaveImage($imageUrl, $sizeType);
                    Log::info('SocialMediaService: Image generated successfully', ['path' => $imagePath, 'size' => $dalleSize]);
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
     * AdCreative.ai-style with text overlays, prices, and CTAs.
     */
    protected function createProductImageDesign(string $type, array $context, int $width = 1080, int $height = 1080, string $sizeType = 'post'): ?string
    {
        try {
            $productImagePath = $context['product_image_path'];

            // Get full path to product image
            $fullPath = \Storage::disk('public')->path($productImagePath);

            // If direct path doesn't exist, try to find it in subdirectories
            if (!file_exists($fullPath)) {
                // Try to find the file in the products directory
                $filename = basename($productImagePath);
                $searchPattern = storage_path('app/public/products/*/' . $filename);
                $foundFiles = glob($searchPattern);

                if (!empty($foundFiles)) {
                    $fullPath = $foundFiles[0];
                    Log::info('SocialMediaService: Found product image in subdirectory', ['path' => $fullPath]);
                } else {
                    Log::warning('SocialMediaService: Product image not found', ['path' => $productImagePath, 'searched' => $searchPattern]);
                    return null;
                }
            }

            // Choose design template based on type
            $template = $this->chooseTemplate($type, $context);

            // Create canvas with specified dimensions
            $canvas = imagecreatetruecolor($width, $height);

            // Brand colors: #C9A96E (gold), #0A0A0A (black), #FAFAF8 (ivory)
            $gold = imagecolorallocate($canvas, 201, 169, 110);
            $black = imagecolorallocate($canvas, 10, 10, 10);
            $ivory = imagecolorallocate($canvas, 250, 250, 248);
            $darkGold = imagecolorallocate($canvas, 160, 130, 70);

            // Create sophisticated radial gradient (dark center to gold edges)
            $centerX = $width / 2;
            $centerY = $height / 2;
            $maxDistance = sqrt($centerX * $centerX + $centerY * $centerY);

            for ($y = 0; $y < $height; $y++) {
                for ($x = 0; $x < $width; $x++) {
                    // Calculate distance from center
                    $dx = $x - $centerX;
                    $dy = $y - $centerY;
                    $distance = sqrt($dx * $dx + $dy * $dy);

                    // Smooth gradient from center to edge
                    $ratio = min($distance / $maxDistance, 1);
                    $ratio = $ratio * $ratio; // Quadratic easing for smoother gradient

                    $r = (int)(10 + (95 - 10) * $ratio); // Darker, richer tone
                    $g = (int)(10 + (80 - 10) * $ratio);
                    $b = (int)(10 + (50 - 10) * $ratio);

                    $color = imagecolorallocate($canvas, $r, $g, $b);
                    imagesetpixel($canvas, $x, $y, $color);
                }
            }

            // Add elegant golden vignette/glow at edges
            $glowCount = (int)(($width + $height) / 6); // Scale with dimensions
            for ($i = 0; $i < $glowCount; $i++) {
                $edge = rand(0, 3); // 0=top, 1=right, 2=bottom, 3=left
                $posMax = ($edge % 2 === 0) ? $width : $height;
                $pos = rand(0, $posMax);
                $depth = rand(0, (int)min($width, $height) * 0.15);
                $alpha = rand(100, 120);
                $glow = imagecolorallocatealpha($canvas, 201, 169, 110, $alpha);

                match($edge) {
                    0 => imagesetpixel($canvas, $pos, $depth, $glow), // top
                    1 => imagesetpixel($canvas, $width - $depth, $pos, $glow), // right
                    2 => imagesetpixel($canvas, $pos, $height - $depth, $glow), // bottom
                    3 => imagesetpixel($canvas, $depth, $pos, $glow), // left
                };
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

            // Calculate size to fit product (adjust for aspect ratio)
            $productRatio = ($width === $height) ? 0.70 : 0.50; // Smaller for vertical stories
            $maxSize = (int)(min($width, $height) * $productRatio);
            $scale = min($maxSize / $prodWidth, $maxSize / $prodHeight);
            $newProdWidth = (int)($prodWidth * $scale);
            $newProdHeight = (int)($prodHeight * $scale);

            // Center the product image
            $prodX = (int)(($width - $newProdWidth) / 2);
            $prodY = (int)(($height - $newProdHeight) / 2);

            // Add luxury multi-layer shadow
            // Outer soft shadow
            $shadowLarge = imagecolorallocatealpha($canvas, 0, 0, 0, 80);
            imagefilledellipse($canvas, $prodX + ($newProdWidth / 2) + 20, $prodY + ($newProdHeight / 2) + 25, $newProdWidth + 40, $newProdHeight + 40, $shadowLarge);

            // Mid shadow
            $shadowMid = imagecolorallocatealpha($canvas, 0, 0, 0, 60);
            imagefilledellipse($canvas, $prodX + ($newProdWidth / 2) + 12, $prodY + ($newProdHeight / 2) + 15, $newProdWidth + 20, $newProdHeight + 20, $shadowMid);

            // Inner sharp shadow
            $shadowInner = imagecolorallocatealpha($canvas, 0, 0, 0, 40);
            imagefilledellipse($canvas, $prodX + ($newProdWidth / 2) + 5, $prodY + ($newProdHeight / 2) + 8, $newProdWidth, $newProdHeight, $shadowInner);

            // Place product image on canvas
            imagecopyresampled(
                $canvas, $productImg,
                $prodX, $prodY, 0, 0,
                $newProdWidth, $newProdHeight,
                $prodWidth, $prodHeight
            );

            imagedestroy($productImg);

            // Add professional text overlays and design elements
            $this->addTextOverlays($canvas, $type, $context, $width, $height, $template);

            // Add elegant golden frame border (all sides)
            $borderThickness = 8;
            // Top border
            imagefilledrectangle($canvas, 0, 0, $width, $borderThickness, $gold);
            // Bottom border
            imagefilledrectangle($canvas, 0, $height - $borderThickness, $width, $height, $gold);
            // Left border
            imagefilledrectangle($canvas, 0, 0, $borderThickness, $height, $gold);
            // Right border
            imagefilledrectangle($canvas, $width - $borderThickness, 0, $width, $height, $gold);

            // Add inner ivory accent lines for luxury double-frame effect
            $innerBorder = 16;
            $ivoryAccent = imagecolorallocatealpha($canvas, 250, 250, 248, 30);
            // Top
            imageline($canvas, $innerBorder, $innerBorder, $width - $innerBorder, $innerBorder, $ivoryAccent);
            // Bottom
            imageline($canvas, $innerBorder, $height - $innerBorder, $width - $innerBorder, $height - $innerBorder, $ivoryAccent);
            // Left
            imageline($canvas, $innerBorder, $innerBorder, $innerBorder, $height - $innerBorder, $ivoryAccent);
            // Right
            imageline($canvas, $width - $innerBorder, $innerBorder, $width - $innerBorder, $height - $innerBorder, $ivoryAccent);

            // Save to temp file with size type in filename
            $filename = 'social-product-' . $sizeType . '-' . time() . '-' . uniqid() . '.png';
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
     * Choose design template based on post type and context.
     */
    protected function chooseTemplate(string $type, array $context): string
    {
        $templates = match($type) {
            'product_promo' => ['premium', 'minimal', 'bold'],
            'offer' => ['badge', 'urgency', 'sale'],
            'brand_story' => ['elegant', 'heritage', 'showcase'],
            default => ['premium'],
        };

        return $templates[array_rand($templates)];
    }

    /**
     * Get TTF font path. Uses Windows system fonts as primary, custom fonts as fallback.
     */
    protected function getFont(string $name = 'serif-bold'): ?string
    {
        // Windows system fonts (elegant choices)
        $systemFonts = [
            'serif-bold' => 'C:/Windows/Fonts/georgiab.ttf',       // Georgia Bold (elegant serif)
            'serif-semibold' => 'C:/Windows/Fonts/georgia.ttf',     // Georgia Regular
            'sans-bold' => 'C:/Windows/Fonts/calibrib.ttf',         // Calibri Bold (clean sans)
            'sans-semibold' => 'C:/Windows/Fonts/calibri.ttf',      // Calibri Regular
        ];

        // Try system font first (most reliable)
        $systemPath = $systemFonts[$name] ?? $systemFonts['serif-bold'];
        if (file_exists($systemPath)) {
            return $systemPath;
        }

        // Custom fonts fallback
        $customFonts = [
            'serif-bold' => 'CormorantGaramond-Bold.ttf',
            'serif-semibold' => 'CormorantGaramond-SemiBold.ttf',
            'sans-bold' => 'Montserrat-Bold.ttf',
            'sans-semibold' => 'Montserrat-SemiBold.ttf',
        ];

        $customPath = storage_path('app/fonts/' . ($customFonts[$name] ?? ''));
        if (file_exists($customPath) && filesize($customPath) > 1000) {
            return $customPath;
        }

        Log::warning('SocialMediaService: No font found', ['name' => $name]);
        return null;
    }

    /**
     * Add professional text overlays (product name, price, CTA, badges).
     */
    protected function addTextOverlays($canvas, string $type, array $context, int $width, int $height, string $template): void
    {
        // Brand colors
        $gold = imagecolorallocate($canvas, 201, 169, 110);
        $black = imagecolorallocate($canvas, 10, 10, 10);
        $ivory = imagecolorallocate($canvas, 250, 250, 248);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        $darkGold = imagecolorallocate($canvas, 160, 130, 70);
        $red = imagecolorallocate($canvas, 200, 40, 40);

        $isStory = $height > $width;

        // Add promotional badge for offers
        if ($type === 'offer' && !empty($context['discount_value'])) {
            $this->addDiscountBadge($canvas, $context['discount_value'], $width, $height, $isStory);
        }

        // Add product name overlay (top section)
        if (!empty($context['product_name'])) {
            $this->addProductNameOverlay($canvas, $context['product_name'], $width, $height, $isStory, $template);
        }

        // Add price display (bottom section)
        if (!empty($context['price'])) {
            $this->addPriceDisplay($canvas, $context, $width, $height, $isStory, $type);
        }

        // Add CTA button
        $cta = $this->getCTA($type, $context);
        if ($cta) {
            $this->addCTAButton($canvas, $cta, $width, $height, $isStory);
        }

        // Add promotional sticker for special cases
        if ($type === 'product_promo' && !empty($context['on_sale'])) {
            $this->addCornerSticker($canvas, 'SALE', $width, $height, $red);
        }

        // Add brand name "AD Perfumes" watermark
        $this->addBrandWatermark($canvas, $width, $height, $isStory);
    }

    /**
     * Add "AD Perfumes" brand watermark text on the image.
     */
    protected function addBrandWatermark($canvas, int $width, int $height, bool $isStory): void
    {
        $gold = imagecolorallocate($canvas, 201, 169, 110);
        $goldShadow = imagecolorallocatealpha($canvas, 0, 0, 0, 60);

        $siteName = $this->siteConfig['name'] ?? 'AD Perfumes';
        $font = $this->getFont('serif-semibold');

        if ($font) {
            $fontSize = $isStory ? 22 : 20;

            $bbox = imagettfbbox($fontSize, 0, $font, $siteName);
            $textWidth = abs($bbox[4] - $bbox[0]);

            // Position: bottom-right, above the CTA button
            $textX = $width - $textWidth - 50;
            $textY = $isStory ? ($height - 130) : ($height - 100);

            imagettftext($canvas, $fontSize, 0, $textX + 2, $textY + 2, $goldShadow, $font, $siteName);
            imagettftext($canvas, $fontSize, 0, $textX, $textY, $gold, $font, $siteName);
        } else {
            $textWidth = strlen($siteName) * 8;
            $textX = $width - $textWidth - 50;
            $textY = $height - 110;
            imagestring($canvas, 4, $textX + 1, $textY + 1, $siteName, $goldShadow);
            imagestring($canvas, 4, $textX, $textY, $siteName, $gold);
        }
    }

    /**
     * Add discount badge (professional circular badge).
     */
    protected function addDiscountBadge($canvas, string $discountValue, int $width, int $height, bool $isStory): void
    {
        $badgeSize = (int)(min($width, $height) * 0.20);
        $badgeX = $width - $badgeSize - 60;
        $badgeY = $isStory ? (int)($height * 0.08) : 70;

        $gold = imagecolorallocate($canvas, 201, 169, 110);
        $black = imagecolorallocate($canvas, 10, 10, 10);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        $darkGold = imagecolorallocate($canvas, 160, 130, 70);
        $red = imagecolorallocate($canvas, 200, 40, 40);

        $centerX = $badgeX + ($badgeSize / 2);
        $centerY = $badgeY + ($badgeSize / 2);

        // Drop shadow (larger and softer)
        $shadow = imagecolorallocatealpha($canvas, 0, 0, 0, 70);
        imagefilledellipse($canvas, $centerX + 10, $centerY + 10, $badgeSize + 20, $badgeSize + 20, $shadow);

        // Outer ring (golden)
        imagefilledellipse($canvas, $centerX, $centerY, $badgeSize, $badgeSize, $gold);

        // Inner circle (red for urgency)
        imagefilledellipse($canvas, $centerX, $centerY, (int)($badgeSize * 0.85), (int)($badgeSize * 0.85), $red);

        // Accent ring
        imageellipse($canvas, $centerX, $centerY, $badgeSize, $badgeSize, $white);
        imagesetthickness($canvas, 2);
        imageellipse($canvas, $centerX, $centerY, (int)($badgeSize * 0.85), (int)($badgeSize * 0.85), $gold);
        imagesetthickness($canvas, 1);

        $font = $this->getFont('sans-bold');

        if ($font) {
            // Main discount value
            $text = str_replace(['%', ' '], '', $discountValue);
            $fontSize = (int)($badgeSize * 0.35);

            $bbox = imagettfbbox($fontSize, 0, $font, $text);
            $textWidth = abs($bbox[4] - $bbox[0]);
            $textHeight = abs($bbox[5] - $bbox[1]);

            $textX = $centerX - ($textWidth / 2);
            $textY = $centerY - 15;

            // Discount value
            imagettftext($canvas, $fontSize, 0, (int)$textX, (int)$textY, $white, $font, $text);

            // "OFF" text
            $offSize = (int)($fontSize * 0.5);
            $bboxOff = imagettfbbox($offSize, 0, $font, 'OFF');
            $offWidth = abs($bboxOff[4] - $bboxOff[0]);
            $offX = $centerX - ($offWidth / 2);

            imagettftext($canvas, $offSize, 0, (int)$offX, (int)($centerY + 30), $white, $font, 'OFF');
        } else {
            // Fallback
            $text = str_replace('%', '', $discountValue);
            $textWidth = strlen($text) * 9;
            $textX = $centerX - ($textWidth / 2);
            imagestring($canvas, 5, (int)$textX, (int)($centerY - 20), $text, $white);
            imagestring($canvas, 5, (int)($centerX - 15), (int)($centerY + 5), 'OFF', $white);
        }
    }

    /**
     * Add product name overlay with background panel.
     */
    protected function addProductNameOverlay($canvas, string $productName, int $width, int $height, bool $isStory, string $template): void
    {
        $gold = imagecolorallocate($canvas, 201, 169, 110);
        $black = imagecolorallocate($canvas, 10, 10, 10);
        $ivory = imagecolorallocate($canvas, 250, 250, 248);
        $blackBg = imagecolorallocatealpha($canvas, 10, 10, 10, 20);

        // Truncate long names
        $displayName = mb_strlen($productName) > 35 ? mb_substr($productName, 0, 32) . '...' : $productName;
        $displayName = strtoupper($displayName);

        // Position: top center for story, top left for post
        $panelHeight = $isStory ? 140 : 110;
        $panelY = $isStory ? 60 : 40;

        // Semi-transparent panel
        imagefilledrectangle($canvas, 40, $panelY, $width - 40, $panelY + $panelHeight, $blackBg);

        // Gold top border
        imagefilledrectangle($canvas, 40, $panelY, $width - 40, $panelY + 6, $gold);

        // Product name text with TTF font
        $font = $this->getFont('serif-bold');
        if ($font) {
            $fontSize = $isStory ? 48 : 42;

            // Calculate text bounding box for centering
            $bbox = imagettfbbox($fontSize, 0, $font, $displayName);
            $textWidth = abs($bbox[4] - $bbox[0]);
            $textHeight = abs($bbox[5] - $bbox[1]);

            $textX = (int)(($width - $textWidth) / 2);
            $textY = $panelY + (int)(($panelHeight + $textHeight) / 2);

            // Text shadow for depth
            imagettftext($canvas, $fontSize, 0, $textX + 3, $textY + 3, $black, $font, $displayName);
            // Main text in ivory
            imagettftext($canvas, $fontSize, 0, $textX, $textY, $ivory, $font, $displayName);
        } else {
            // Fallback to built-in font
            $textX = (int)(($width - (strlen($displayName) * 10)) / 2);
            $textY = $panelY + 50;
            imagestring($canvas, 5, $textX + 2, $textY + 2, $displayName, $black);
            imagestring($canvas, 5, $textX, $textY, $displayName, $ivory);
        }
    }

    /**
     * Add price display with discount info.
     */
    protected function addPriceDisplay($canvas, array $context, int $width, int $height, bool $isStory, string $type): void
    {
        $gold = imagecolorallocate($canvas, 201, 169, 110);
        $black = imagecolorallocate($canvas, 10, 10, 10);
        $ivory = imagecolorallocate($canvas, 250, 250, 248);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        $red = imagecolorallocate($canvas, 200, 40, 40);

        $price = $context['price'] ?? null;
        $originalPrice = $context['original_price'] ?? null;

        if (!$price) return;

        // Position: bottom left
        $priceY = $height - ($isStory ? 220 : 180);
        $priceX = 60;

        // Price panel background
        $panelWidth = 350;
        $panelHeight = 100;
        $panelBg = imagecolorallocatealpha($canvas, 10, 10, 10, 15);
        imagefilledrectangle($canvas, $priceX - 20, $priceY - 15, $priceX + $panelWidth, $priceY + $panelHeight, $panelBg);

        // Gold border
        imagerectangle($canvas, $priceX - 20, $priceY - 15, $priceX + $panelWidth, $priceY + $panelHeight, $gold);
        imagerectangle($canvas, $priceX - 21, $priceY - 16, $priceX + $panelWidth + 1, $priceY + $panelHeight + 1, $gold);

        $font = $this->getFont('sans-bold');

        if ($font) {
            // Current price with TTF
            $priceText = 'AED ' . number_format($price, 0);
            imagettftext($canvas, 36, 0, $priceX, $priceY + 50, $white, $font, $priceText);

            // Original price (if on sale)
            if ($originalPrice && $originalPrice > $price) {
                $originalText = 'AED ' . number_format($originalPrice, 0);
                $fontSmall = $this->getFont('sans-semibold');

                // Position for original price
                $bbox = imagettfbbox(24, 0, $fontSmall, $originalText);
                $textWidth = abs($bbox[4] - $bbox[0]);

                imagettftext($canvas, 24, 0, $priceX, $priceY + 85, $red, $fontSmall, $originalText);

                // Strikethrough line
                imageline($canvas, $priceX, $priceY + 70, $priceX + $textWidth, $priceY + 70, $red);
                imagesetthickness($canvas, 2);
                imageline($canvas, $priceX, $priceY + 70, $priceX + $textWidth, $priceY + 70, $red);
                imagesetthickness($canvas, 1);
            }
        } else {
            // Fallback
            $priceText = 'AED ' . number_format($price, 2);
            imagestring($canvas, 5, $priceX, $priceY + 30, $priceText, $white);

            if ($originalPrice && $originalPrice > $price) {
                $originalText = 'AED ' . number_format($originalPrice, 2);
                imagestring($canvas, 4, $priceX, $priceY + 60, $originalText, $red);
                imageline($canvas, $priceX, $priceY + 67, $priceX + 120, $priceY + 67, $red);
            }
        }
    }

    /**
     * Add CTA button overlay.
     */
    protected function addCTAButton($canvas, string $cta, int $width, int $height, bool $isStory): void
    {
        $gold = imagecolorallocate($canvas, 201, 169, 110);
        $black = imagecolorallocate($canvas, 10, 10, 10);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        $darkGold = imagecolorallocate($canvas, 160, 130, 70);

        // Position: bottom center
        $btnWidth = 340;
        $btnHeight = 75;
        $btnX = (int)(($width - $btnWidth) / 2);
        $btnY = $height - ($isStory ? 100 : 80);

        // Button shadow (larger, softer)
        $shadow = imagecolorallocatealpha($canvas, 0, 0, 0, 70);
        imagefilledrectangle($canvas, $btnX + 6, $btnY + 6, $btnX + $btnWidth + 6, $btnY + $btnHeight + 6, $shadow);

        // Button background (gradient effect with layering)
        imagefilledrectangle($canvas, $btnX, $btnY, $btnX + $btnWidth, $btnY + $btnHeight, $gold);

        // Highlight on top edge
        $highlight = imagecolorallocatealpha($canvas, 255, 255, 255, 100);
        imagefilledrectangle($canvas, $btnX, $btnY, $btnX + $btnWidth, $btnY + 3, $highlight);

        // Double border for luxury effect
        imagerectangle($canvas, $btnX, $btnY, $btnX + $btnWidth, $btnY + $btnHeight, $darkGold);
        imagerectangle($canvas, $btnX - 1, $btnY - 1, $btnX + $btnWidth + 1, $btnY + $btnHeight + 1, $darkGold);

        // CTA text with TTF font
        $ctaText = strtoupper(mb_substr($cta, 0, 20));
        $font = $this->getFont('sans-bold');

        if ($font) {
            $fontSize = 32;

            // Calculate text bounding box for perfect centering
            $bbox = imagettfbbox($fontSize, 0, $font, $ctaText);
            $textWidth = abs($bbox[4] - $bbox[0]);
            $textHeight = abs($bbox[5] - $bbox[1]);

            $textX = $btnX + (int)(($btnWidth - $textWidth) / 2);
            $textY = $btnY + (int)(($btnHeight + $textHeight) / 2);

            // Shadow for depth
            imagettftext($canvas, $fontSize, 0, $textX + 2, $textY + 2, $darkGold, $font, $ctaText);
            // Main text
            imagettftext($canvas, $fontSize, 0, $textX, $textY, $black, $font, $ctaText);
        } else {
            // Fallback
            $textWidth = strlen($ctaText) * 9;
            $textX = $btnX + (int)(($btnWidth - $textWidth) / 2);
            $textY = $btnY + (int)(($btnHeight - 16) / 2);
            imagestring($canvas, 5, $textX, $textY, $ctaText, $black);
        }
    }

    /**
     * Add corner sticker (NEW, SALE, LIMITED, etc.).
     */
    protected function addCornerSticker($canvas, string $text, int $width, int $height, $color): void
    {
        $white = imagecolorallocate($canvas, 255, 255, 255);
        $black = imagecolorallocate($canvas, 10, 10, 10);

        $stickerSize = 100;
        $stickerX = 50;
        $stickerY = 50;

        // Starburst effect (simplified hexagon)
        imagefilledellipse($canvas, $stickerX + 50, $stickerY + 50, $stickerSize, $stickerSize, $color);
        imageellipse($canvas, $stickerX + 50, $stickerY + 50, $stickerSize, $stickerSize, $white);

        // Text
        $textWidth = strlen($text) * 8;
        $textX = $stickerX + 50 - ($textWidth / 2);
        $textY = $stickerY + 45;

        imagestring($canvas, 4, (int)$textX, (int)$textY, strtoupper($text), $white);
    }

    /**
     * Get appropriate CTA based on post type.
     */
    protected function getCTA(string $type, array $context): ?string
    {
        return match($type) {
            'product_promo' => 'SHOP NOW',
            'offer' => 'GRAB THE OFFER',
            'brand_story' => 'EXPLORE COLLECTION',
            default => 'SHOP NOW',
        };
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
