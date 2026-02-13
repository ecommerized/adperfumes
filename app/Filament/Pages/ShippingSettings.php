<?php

namespace App\Filament\Pages;

use App\Services\SettingsService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Notifications\Notification;

class ShippingSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 4;

    protected static string $view = 'filament.pages.shipping-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = app(SettingsService::class);

        $this->form->fill([
            'shipping_enabled' => $settings->get('shipping.enabled', true),
            'shipping_provider' => $settings->get('shipping.provider', 'flat_rate'),
            'shipping_flat_rate_amount' => $settings->get('shipping.flat_rate_amount'),
            'shipping_free_shipping_min' => $settings->get('shipping.free_shipping_min'),
            'shipping_default_weight_unit' => $settings->get('shipping.default_weight_unit', 'kg'),
            'aramex_country_code' => $settings->get('aramex.country_code', 'AE'),
            'aramex_sender_city' => $settings->get('aramex.sender_city'),
            'aramex_sender_postal_code' => $settings->get('aramex.sender_postal_code'),
            'aramex_sender_address' => $settings->get('aramex.sender_address'),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Shipping Configuration')
                    ->schema([
                        Forms\Components\Toggle::make('shipping_enabled')
                            ->label('Enable Shipping')
                            ->default(true)
                            ->helperText('Turn shipping on/off globally'),

                        Forms\Components\Select::make('shipping_provider')
                            ->label('Shipping Provider')
                            ->options([
                                'flat_rate' => 'Flat Rate',
                                'aramex' => 'Aramex',
                            ])
                            ->required()
                            ->live()
                            ->helperText('Select your preferred shipping method'),

                        Forms\Components\TextInput::make('shipping_default_weight_unit')
                            ->label('Default Weight Unit')
                            ->default('kg')
                            ->required()
                            ->helperText('Unit for product weights (kg, g, lb)'),
                    ])->columns(3),

                Forms\Components\Section::make('Flat Rate Shipping')
                    ->schema([
                        Forms\Components\TextInput::make('shipping_flat_rate_amount')
                            ->label('Flat Rate Amount')
                            ->numeric()
                            ->prefix('AED')
                            ->helperText('Fixed shipping cost for all orders'),

                        Forms\Components\TextInput::make('shipping_free_shipping_min')
                            ->label('Free Shipping Minimum')
                            ->numeric()
                            ->prefix('AED')
                            ->helperText('Minimum order value for free shipping (leave empty to disable)'),
                    ])
                    ->columns(2)
                    ->visible(fn (Forms\Get $get) => $get('shipping_provider') === 'flat_rate'),

                Forms\Components\Section::make('Aramex Configuration')
                    ->description('**Important:** Aramex credentials (username, password, account number) must be configured in your .env file for security.')
                    ->schema([
                        Forms\Components\Placeholder::make('aramex_env_notice')
                            ->label('Required .env Variables')
                            ->content('
                                Add these to your .env file:
                                - ARAMEX_USERNAME
                                - ARAMEX_PASSWORD
                                - ARAMEX_ACCOUNT_NUMBER
                            '),

                        Forms\Components\Select::make('aramex_country_code')
                            ->label('Sender Country Code')
                            ->options([
                                'AE' => 'United Arab Emirates (AE)',
                                'SA' => 'Saudi Arabia (SA)',
                                'KW' => 'Kuwait (KW)',
                                'BH' => 'Bahrain (BH)',
                                'QA' => 'Qatar (QA)',
                                'OM' => 'Oman (OM)',
                            ])
                            ->default('AE')
                            ->required(),

                        Forms\Components\TextInput::make('aramex_sender_city')
                            ->label('Sender City')
                            ->maxLength(255)
                            ->required(),

                        Forms\Components\TextInput::make('aramex_sender_postal_code')
                            ->label('Sender Postal Code')
                            ->maxLength(255)
                            ->helperText('Optional for UAE addresses'),

                        Forms\Components\Textarea::make('aramex_sender_address')
                            ->label('Sender Address')
                            ->rows(3)
                            ->required()
                            ->columnSpanFull(),
                    ])
                    ->columns(3)
                    ->visible(fn (Forms\Get $get) => $get('shipping_provider') === 'aramex'),
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
            ->title('Shipping settings saved successfully')
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
