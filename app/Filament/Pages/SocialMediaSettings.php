<?php

namespace App\Filament\Pages;

use App\Services\FacebookService;
use App\Services\SettingsService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Notifications\Notification;

class SocialMediaSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Marketing';

    protected static ?int $navigationSort = 7;

    protected static ?string $title = 'Social Media Settings';

    protected static ?string $navigationLabel = 'Social Media Settings';

    protected static string $view = 'filament.pages.social-media-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = app(SettingsService::class);

        $this->form->fill([
            'facebook_page_id' => $settings->get('facebook_page_id'),
            'facebook_page_access_token' => $settings->get('facebook_page_access_token'),
            'openai_api_key' => $settings->get('openai_api_key'),
            'social_auto_pilot_enabled' => (bool) $settings->get('social_auto_pilot_enabled', false),
            'social_auto_post_frequency' => $settings->get('social_auto_post_frequency', 'daily'),
            'social_auto_post_types' => json_decode($settings->get('social_auto_post_types', '["product_promo","offer","brand_story"]'), true),
            'social_auto_post_hours' => $settings->get('social_auto_post_hours', '[10, 14, 18]'),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Facebook Page Configuration')
                    ->description('Connect your Facebook Page to enable auto-posting. You need a long-lived Page Access Token with pages_manage_posts and pages_read_engagement permissions.')
                    ->schema([
                        Forms\Components\TextInput::make('facebook_page_id')
                            ->label('Facebook Page ID')
                            ->placeholder('e.g. 123456789012345')
                            ->maxLength(50)
                            ->helperText('Find this in your Facebook Page Settings > Page Transparency > Page ID.'),

                        Forms\Components\TextInput::make('facebook_page_access_token')
                            ->label('Page Access Token (Long-Lived)')
                            ->password()
                            ->revealable()
                            ->maxLength(1000)
                            ->helperText('Generate from the Meta Developer portal (Graph API Explorer). Requires pages_manage_posts permission.'),
                    ])->columns(2),

                Forms\Components\Section::make('AI Image Generation')
                    ->description('Enable AI-powered image generation for social media posts using DALL-E 3.')
                    ->schema([
                        Forms\Components\TextInput::make('openai_api_key')
                            ->label('OpenAI API Key')
                            ->password()
                            ->revealable()
                            ->maxLength(500)
                            ->helperText('Get your API key from https://platform.openai.com/api-keys. Required for AI image generation ($0.04 per image).'),
                    ]),

                Forms\Components\Section::make('Auto-Pilot Settings')
                    ->description('Configure automatic social media post generation and scheduling.')
                    ->schema([
                        Forms\Components\Toggle::make('social_auto_pilot_enabled')
                            ->label('Enable Auto-Pilot')
                            ->helperText('When enabled, the system will automatically generate and schedule Facebook posts.'),

                        Forms\Components\Select::make('social_auto_post_frequency')
                            ->label('Posting Frequency')
                            ->options([
                                'daily' => 'Once per day',
                                'twice_daily' => 'Twice per day',
                                'every_other_day' => 'Every other day',
                                'weekly_3' => '3 times per week',
                                'weekly' => 'Once per week',
                            ])
                            ->default('daily'),

                        Forms\Components\CheckboxList::make('social_auto_post_types')
                            ->label('Preferred Post Types')
                            ->options([
                                'product_promo' => 'Product Promotions',
                                'offer' => 'Offers / Discounts',
                                'brand_story' => 'Brand Stories',
                            ])
                            ->default(['product_promo', 'offer', 'brand_story'])
                            ->columns(3),

                        Forms\Components\TextInput::make('social_auto_post_hours')
                            ->label('Preferred Posting Hours (UAE Time)')
                            ->placeholder('[10, 14, 18]')
                            ->helperText('JSON array of hours in 24h format (UAE timezone). Default: [10, 14, 18]')
                            ->maxLength(100),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $settings = app(SettingsService::class);

        if (isset($data['social_auto_post_types']) && is_array($data['social_auto_post_types'])) {
            $data['social_auto_post_types'] = json_encode($data['social_auto_post_types']);
        }

        foreach ($data as $key => $value) {
            $settings->set($key, $value);
        }

        Notification::make()
            ->title('Social media settings saved successfully')
            ->success()
            ->send();
    }

    public function testConnection(): void
    {
        $this->save();

        $facebookService = app(FacebookService::class);
        $pageInfo = $facebookService->getPageInfo();

        if ($pageInfo) {
            Notification::make()
                ->title('Connected to Facebook Page!')
                ->body('Page: ' . ($pageInfo['name'] ?? 'Unknown') . ' (ID: ' . ($pageInfo['id'] ?? 'N/A') . ')')
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Connection Failed')
                ->body('Could not connect to Facebook. Please verify your Page ID and Access Token.')
                ->danger()
                ->send();
        }
    }

    protected function getFormActions(): array
    {
        return [
            Forms\Components\Actions\Action::make('save')
                ->label('Save Settings')
                ->submit('save'),

            Forms\Components\Actions\Action::make('test_connection')
                ->label('Test Facebook Connection')
                ->icon('heroicon-o-signal')
                ->color('info')
                ->action('testConnection'),
        ];
    }
}
