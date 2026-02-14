<?php

namespace App\Services;

use App\Models\BlogPost;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AutoBlogService
{
    protected SeoAeoService $seoService;
    protected string $apiKey;
    protected string $model;
    protected string $apiUrl;
    protected int $maxTokens;
    protected array $siteConfig;

    public function __construct(SeoAeoService $seoService)
    {
        $this->seoService = $seoService;

        $settings = app(SettingsService::class);
        $this->apiKey = $settings->get('anthropic_api_key') ?: config('seo.anthropic.api_key', '');
        $this->model = config('seo.anthropic.model');
        $this->apiUrl = config('seo.anthropic.api_url');
        $this->maxTokens = 8192; // Larger for blog content
        $this->siteConfig = config('seo.site');
    }

    public function generateTopics(int $count = null): array
    {
        $count = $count ?? config('seo.blog.topics_per_week', 5);

        $existingTitles = BlogPost::pluck('title')->toArray();

        $systemPrompt = $this->buildTopicSystemPrompt();
        $userPrompt = $this->buildTopicUserPrompt($count, $existingTitles);

        $response = $this->callAnthropic($systemPrompt, $userPrompt);

        if (!$response || empty($response['topics'])) {
            Log::warning('AutoBlogService: Failed to generate topics');
            return [];
        }

        $topics = [];
        foreach ($response['topics'] as $topic) {
            $slug = Str::slug($topic['title']);

            // Skip if slug already exists
            if (BlogPost::where('slug', $slug)->exists()) {
                continue;
            }

            $post = BlogPost::create([
                'title' => $topic['title'],
                'slug' => $slug,
                'excerpt' => $topic['excerpt'] ?? null,
                'status' => 'draft',
                'topic_source' => 'ai',
                'meta_data' => [
                    'target_keyword' => $topic['target_keyword'] ?? '',
                    'secondary_keywords' => $topic['secondary_keywords'] ?? [],
                    'search_intent' => $topic['search_intent'] ?? '',
                    'outline' => $topic['outline'] ?? [],
                    'generation_date' => now()->toISOString(),
                ],
            ]);
            $topics[] = $post;
        }

        Log::info("AutoBlogService: Generated " . count($topics) . " blog topics");
        return $topics;
    }

    public function writePost(BlogPost $post): BlogPost
    {
        $systemPrompt = $this->buildWritingSystemPrompt();
        $userPrompt = $this->buildWritingUserPrompt($post);

        $response = $this->callAnthropic($systemPrompt, $userPrompt);

        if (!$response || empty($response['content'])) {
            Log::warning("AutoBlogService: Failed to write post #{$post->id}");
            return $post;
        }

        $post->update([
            'content' => $response['content'],
            'excerpt' => $response['excerpt'] ?? $post->excerpt,
            'meta_data' => array_merge($post->meta_data ?? [], [
                'word_count' => str_word_count(strip_tags($response['content'])),
                'generation_model' => $this->model,
                'written_at' => now()->toISOString(),
            ]),
        ]);

        // Generate SEO data for the post
        $this->seoService->generate($post->fresh());

        // Evaluate for auto-publishing
        $this->evaluateForPublishing($post->fresh());

        Log::info("AutoBlogService: Wrote blog post #{$post->id}: {$post->title}");
        return $post->fresh();
    }

    public function writeNextDraft(): ?BlogPost
    {
        $draft = BlogPost::where('status', 'draft')
            ->whereNull('content')
            ->oldest()
            ->first();

        if (!$draft) {
            Log::info('AutoBlogService: No unwritten drafts found');
            return null;
        }

        return $this->writePost($draft);
    }

    public function evaluateForPublishing(BlogPost $post): void
    {
        $settings = app(SettingsService::class);
        $autoPublish = $settings->get('seo_blog_auto_publish', config('seo.blog.auto_publish', true));

        if (!$autoPublish) {
            return;
        }

        $threshold = $settings->get('seo_auto_publish_threshold')
            ?: config('seo.scoring.auto_publish_threshold', 70);

        $score = $post->seoMeta?->scoring['overall_score'] ?? 0;

        if ($score >= $threshold && $post->status !== 'published' && !empty($post->content)) {
            $post->update([
                'status' => 'published',
                'published_at' => now(),
                'seo_score' => $score,
            ]);
            Log::info("AutoBlogService: Auto-published '{$post->title}' (score: {$score})");
        } else {
            $post->update([
                'status' => 'pending_review',
                'seo_score' => $score,
            ]);
        }
    }

    protected function buildTopicSystemPrompt(): string
    {
        $siteName = $this->siteConfig['name'] ?? 'AD Perfumes';
        $country = $this->siteConfig['target_country'] ?? 'UAE';
        $industry = $this->siteConfig['industry'] ?? 'Luxury Perfumes & Fragrances';

        return <<<PROMPT
You are an expert content strategist for {$siteName}, a luxury fragrance e-commerce store in the {$country}.
Your job is to generate engaging blog topic ideas that will drive organic traffic from search engines.

FOCUS AREAS:
- Perfume guides, reviews, and comparisons
- Fragrance tips, layering, seasonal recommendations
- Perfume industry news and trends
- Gift guides for different occasions
- How-to guides (applying perfume, storing, choosing)
- Ingredient spotlights (oud, rose, musk, amber, etc.)
- UAE-specific fragrance culture and preferences
- Luxury lifestyle content related to fragrances

Respond with ONLY valid JSON. No markdown, no explanation. Structure:
{
  "topics": [
    {
      "title": "Blog post title (compelling, keyword-optimized)",
      "excerpt": "2-3 sentence summary",
      "target_keyword": "main keyword to target",
      "secondary_keywords": ["3-5 supporting keywords"],
      "search_intent": "informational|commercial|navigational",
      "outline": ["H2 heading 1", "H2 heading 2", "H2 heading 3"]
    }
  ]
}
PROMPT;
    }

    protected function buildTopicUserPrompt(int $count, array $existingTitles): string
    {
        $existing = !empty($existingTitles)
            ? "EXISTING TOPICS (avoid duplicates):\n" . implode("\n", array_slice($existingTitles, -20))
            : "No existing topics yet.";

        return "Generate {$count} new blog topic ideas for our perfume store.\n\n{$existing}";
    }

    protected function buildWritingSystemPrompt(): string
    {
        $siteName = $this->siteConfig['name'] ?? 'AD Perfumes';
        $country = $this->siteConfig['target_country'] ?? 'UAE';
        $brandVoice = $this->siteConfig['brand_voice'] ?? '';

        return <<<PROMPT
You are an expert content writer for {$siteName}, a luxury fragrance e-commerce store in the {$country}.

BRAND VOICE: {$brandVoice}

WRITING RULES:
- Write 1200-2000 words
- Use proper H2 and H3 subheadings with keywords
- Include an FAQ section at the bottom (3-5 questions)
- Include a "Key Takeaways" section near the top
- Use short paragraphs (2-3 sentences max)
- Include the primary keyword in the first 100 words
- End with a conclusion and call to action
- Write for a UAE audience (reference local culture, climate, occasions)
- Use engaging, expert tone
- Include image placeholders as [IMAGE: description of ideal image]

Respond with ONLY valid JSON:
{
  "content": "Full HTML blog post content with proper heading tags",
  "excerpt": "Compelling 2-3 sentence excerpt for listing pages"
}
PROMPT;
    }

    protected function buildWritingUserPrompt(BlogPost $post): string
    {
        $data = [
            'title' => $post->title,
            'target_keyword' => $post->meta_data['target_keyword'] ?? '',
            'secondary_keywords' => $post->meta_data['secondary_keywords'] ?? [],
            'outline' => $post->meta_data['outline'] ?? [],
            'excerpt' => $post->excerpt ?? '',
        ];

        return "Write a complete blog post based on this topic:\n\n" . json_encode($data, JSON_PRETTY_PRINT);
    }

    protected function callAnthropic(string $systemPrompt, string $userPrompt): ?array
    {
        if (empty($this->apiKey)) {
            Log::error('AutoBlogService: Anthropic API key not configured');
            return null;
        }

        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])
            ->timeout(180)
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

            Log::error('AutoBlogService API error', [
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 1000),
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('AutoBlogService exception', ['message' => $e->getMessage()]);
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

        Log::warning('AutoBlogService: Failed to parse JSON', [
            'content_preview' => mb_substr($content, 0, 500),
        ]);

        return null;
    }
}
