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
