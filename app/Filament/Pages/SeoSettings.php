<?php

namespace App\Filament\Pages;

use App\Services\SettingsService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Notifications\Notification;

class SeoSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'SEO Settings';

    protected static string $view = 'filament.pages.seo-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = app(SettingsService::class);

        $this->form->fill([
            'seo_default_title' => $settings->get('seo.default_title'),
            'seo_default_description' => $settings->get('seo.default_description'),
            'seo_og_image' => $settings->get('seo.og_image'),
            'google_site_verification' => $settings->get('google_site_verification'),
            'anthropic_api_key' => $settings->get('anthropic_api_key'),
            'seo_auto_publish_threshold' => $settings->get('seo_auto_publish_threshold', 70),
            'seo_reoptimize_below' => $settings->get('seo_reoptimize_below', 50),
            'seo_blog_auto_publish' => $settings->get('seo_blog_auto_publish', '1'),
            'seo_blog_topics_per_week' => $settings->get('seo_blog_topics_per_week', 5),
            'seo_blog_posts_per_day' => $settings->get('seo_blog_posts_per_day', 1),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Default SEO Meta Tags')
                    ->description('These meta tags will be used as defaults across your website')
                    ->schema([
                        Forms\Components\TextInput::make('seo_default_title')
                            ->label('Default Page Title')
                            ->required()
                            ->maxLength(60)
                            ->helperText('Optimal length: 50-60 characters. This appears in browser tabs and search results.'),

                        Forms\Components\Textarea::make('seo_default_description')
                            ->label('Default Meta Description')
                            ->required()
                            ->maxLength(160)
                            ->rows(3)
                            ->helperText('Optimal length: 150-160 characters. This appears in search engine results.'),

                        Forms\Components\FileUpload::make('seo_og_image')
                            ->label('Default Open Graph Image')
                            ->image()
                            ->directory('settings/seo')
                            ->maxSize(2048)
                            ->helperText('Recommended size: 1200x630px. Used when sharing on social media (max 2MB).'),
                    ])->columns(1),

                Forms\Components\Section::make('Google Search Console Verification')
                    ->description('Add your Google Search Console site verification meta tag content.')
                    ->schema([
                        Forms\Components\TextInput::make('google_site_verification')
                            ->label('Google Site Verification Code')
                            ->placeholder('e.g. AbCdEf123456...')
                            ->helperText('Go to Google Search Console → Settings → Ownership verification → HTML tag. Copy only the content value (not the full meta tag).')
                            ->maxLength(255),
                    ])->columns(1),

                Forms\Components\Section::make('AI-Powered SEO Configuration')
                    ->description('Configure the Anthropic Claude API for automatic SEO/AEO generation')
                    ->schema([
                        Forms\Components\TextInput::make('anthropic_api_key')
                            ->label('Anthropic API Key')
                            ->password()
                            ->revealable()
                            ->placeholder('sk-ant-...')
                            ->helperText('Your Anthropic API key for AI-powered SEO generation. Get one at console.anthropic.com'),
                    ])->columns(1),

                Forms\Components\Section::make('Auto-Blog Settings')
                    ->description('Configure automated blog content generation')
                    ->schema([
                        Forms\Components\Toggle::make('seo_blog_auto_publish')
                            ->label('Auto-Publish High-Score Posts')
                            ->helperText('Automatically publish blog posts that meet the score threshold'),

                        Forms\Components\TextInput::make('seo_blog_topics_per_week')
                            ->label('Topics Per Week')
                            ->numeric()
                            ->default(5)
                            ->minValue(1)
                            ->maxValue(20),

                        Forms\Components\TextInput::make('seo_blog_posts_per_day')
                            ->label('Posts Per Day')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->maxValue(5),
                    ])->columns(3),

                Forms\Components\Section::make('Scoring Thresholds')
                    ->description('Control when content is auto-published or re-optimized')
                    ->schema([
                        Forms\Components\TextInput::make('seo_auto_publish_threshold')
                            ->label('Auto-Publish Threshold')
                            ->numeric()
                            ->default(70)
                            ->minValue(0)
                            ->maxValue(100)
                            ->helperText('Minimum overall score (0-100) for auto-publishing blog posts'),

                        Forms\Components\TextInput::make('seo_reoptimize_below')
                            ->label('Re-optimize Below')
                            ->numeric()
                            ->default(50)
                            ->minValue(0)
                            ->maxValue(100)
                            ->helperText('Content scoring below this will be re-optimized weekly'),
                    ])->columns(2),

                Forms\Components\Section::make('Tips')
                    ->schema([
                        Forms\Components\Placeholder::make('seo_tips')
                            ->label('')
                            ->content('
                                **SEO Best Practices:**
                                - Keep titles under 60 characters
                                - Keep descriptions under 160 characters
                                - Use relevant keywords naturally
                                - Make descriptions compelling to increase click-through rate
                                - Open Graph images should be 1200x630px for best display on social media
                            '),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $settings = app(SettingsService::class);

        foreach ($data as $key => $value) {
            $settings->set($key, $value);
        }

        Notification::make()
            ->title('SEO settings saved successfully')
            ->success()
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            Forms\Components\Actions\Action::make('save')
                ->label('Save Settings')
                ->submit('save'),
        ];
    }
}
