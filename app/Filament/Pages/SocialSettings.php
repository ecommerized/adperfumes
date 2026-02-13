<?php

namespace App\Filament\Pages;

use App\Services\SettingsService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Notifications\Notification;

class SocialSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-share';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.social-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = app(SettingsService::class);

        $this->form->fill([
            'social_instagram' => $settings->get('social.instagram'),
            'social_tiktok' => $settings->get('social.tiktok'),
            'social_facebook' => $settings->get('social.facebook'),
            'social_snapchat' => $settings->get('social.snapchat'),
            'social_youtube' => $settings->get('social.youtube'),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Social Media Links')
                    ->description('Add your social media profile URLs')
                    ->schema([
                        Forms\Components\TextInput::make('social_instagram')
                            ->label('Instagram URL')
                            ->url()
                            ->prefixIcon('heroicon-o-at-symbol')
                            ->placeholder('https://instagram.com/yourprofile')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('social_tiktok')
                            ->label('TikTok URL')
                            ->url()
                            ->prefixIcon('heroicon-o-video-camera')
                            ->placeholder('https://tiktok.com/@yourprofile')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('social_facebook')
                            ->label('Facebook URL')
                            ->url()
                            ->prefixIcon('heroicon-o-user-group')
                            ->placeholder('https://facebook.com/yourpage')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('social_snapchat')
                            ->label('Snapchat URL')
                            ->url()
                            ->prefixIcon('heroicon-o-camera')
                            ->placeholder('https://snapchat.com/add/yourprofile')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('social_youtube')
                            ->label('YouTube URL')
                            ->url()
                            ->prefixIcon('heroicon-o-play')
                            ->placeholder('https://youtube.com/@yourchannel')
                            ->maxLength(255),
                    ])->columns(1),
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
            ->title('Social media settings saved successfully')
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
