<?php

namespace App\Filament\Pages;

use App\Services\SettingsService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Notifications\Notification;

class TrackingPixels extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-code-bracket';

    protected static ?string $navigationGroup = 'Marketing';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Tracking Pixels';

    protected static string $view = 'filament.pages.tracking-pixels';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = app(SettingsService::class);

        $this->form->fill([
            'pixel_meta' => $settings->get('pixel_meta'),
            'pixel_tiktok' => $settings->get('pixel_tiktok'),
            'pixel_x' => $settings->get('pixel_x'),
            'pixel_snapchat' => $settings->get('pixel_snapchat'),
            'pixel_pinterest' => $settings->get('pixel_pinterest'),
            'pixel_linkedin' => $settings->get('pixel_linkedin'),
            'pixel_google_analytics' => $settings->get('pixel_google_analytics'),
            'pixel_gtm' => $settings->get('pixel_gtm'),
            'pixel_clarity' => $settings->get('pixel_clarity'),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Social Media Pixels')
                    ->description('Enter the pixel/tag IDs from each platform. These will be injected into the frontend for conversion tracking.')
                    ->schema([
                        Forms\Components\TextInput::make('pixel_meta')
                            ->label('Meta (Facebook) Pixel ID')
                            ->placeholder('e.g. 1234567890123456')
                            ->helperText('Found in Meta Events Manager > Data Sources > Your Pixel')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('pixel_tiktok')
                            ->label('TikTok Pixel ID')
                            ->placeholder('e.g. CXXXXXXXXXXXXXXXXX')
                            ->helperText('Found in TikTok Ads Manager > Assets > Events')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('pixel_x')
                            ->label('X (Twitter) Pixel ID')
                            ->placeholder('e.g. oXXXX')
                            ->helperText('Found in X Ads Manager > Tools > Conversion Tracking')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('pixel_snapchat')
                            ->label('Snapchat Pixel ID')
                            ->placeholder('e.g. xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx')
                            ->helperText('Found in Snapchat Ads Manager > Events Manager')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('pixel_pinterest')
                            ->label('Pinterest Tag ID')
                            ->placeholder('e.g. 1234567890123')
                            ->helperText('Found in Pinterest Ads Manager > Conversions > Tag')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('pixel_linkedin')
                            ->label('LinkedIn Insight Tag ID')
                            ->placeholder('e.g. 1234567')
                            ->helperText('Found in LinkedIn Campaign Manager > Account Assets > Insight Tag')
                            ->maxLength(255),
                    ])->columns(2),

                Forms\Components\Section::make('Analytics & Tag Management')
                    ->description('Google Analytics, Tag Manager, and Microsoft Clarity integration.')
                    ->schema([
                        Forms\Components\TextInput::make('pixel_google_analytics')
                            ->label('Google Analytics Measurement ID')
                            ->placeholder('e.g. G-XXXXXXXXXX')
                            ->helperText('Found in Google Analytics > Admin > Data Streams > Your Stream')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('pixel_gtm')
                            ->label('Google Tag Manager Container ID')
                            ->placeholder('e.g. GTM-XXXXXXX')
                            ->helperText('Found in Google Tag Manager > Container Settings')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('pixel_clarity')
                            ->label('Microsoft Clarity Project ID')
                            ->placeholder('e.g. abcdefghij')
                            ->helperText('Found in Clarity Dashboard > Settings > Setup')
                            ->maxLength(255),
                    ])->columns(2),
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
            ->title('Tracking pixels saved successfully')
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
