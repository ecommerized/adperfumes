<?php

namespace App\Filament\Pages;

use App\Services\SettingsService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Notifications\Notification;

class StoreSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.store-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = app(SettingsService::class);

        $this->form->fill([
            'store_name' => $settings->get('store_name', 'AD Perfumes'),
            'store_logo' => $settings->get('store_logo'),
            'store_favicon' => $settings->get('store_favicon'),
            'store_support_email' => $settings->get('store_support_email'),
            'store_support_phone' => $settings->get('store_support_phone'),
            'store_whatsapp' => $settings->get('store_whatsapp'),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Store Information')
                    ->schema([
                        Forms\Components\TextInput::make('store_name')
                            ->label('Store Name')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Your store name displayed across the website'),

                        Forms\Components\FileUpload::make('store_logo')
                            ->label('Store Logo')
                            ->image()
                            ->disk('public')
                            ->directory('settings')
                            ->maxSize(2048)
                            ->visibility('public')
                            ->helperText('Logo displayed in header (max 2MB)'),

                        Forms\Components\FileUpload::make('store_favicon')
                            ->label('Favicon')
                            ->image()
                            ->disk('public')
                            ->directory('settings')
                            ->maxSize(512)
                            ->visibility('public')
                            ->helperText('Small icon for browser tab (max 512KB)'),
                    ])->columns(1),

                Forms\Components\Section::make('Contact Information')
                    ->schema([
                        Forms\Components\TextInput::make('store_support_email')
                            ->label('Support Email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->helperText('Customer support email address'),

                        Forms\Components\TextInput::make('store_support_phone')
                            ->label('Support Phone')
                            ->tel()
                            ->maxLength(255)
                            ->helperText('Customer support phone number'),

                        Forms\Components\TextInput::make('store_whatsapp')
                            ->label('WhatsApp Number')
                            ->tel()
                            ->maxLength(255)
                            ->helperText('WhatsApp number for customer support (include country code)'),
                    ])->columns(3),
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
            ->title('Store settings saved successfully')
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
