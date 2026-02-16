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

    protected static ?string $navigationLabel = 'Payment & Delivery';

    protected static ?string $title = 'Payment & Delivery Settings';

    protected static string $view = 'filament.pages.shipping-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = app(SettingsService::class);

        $this->form->fill([
            // Payment method toggles
            'payment_tap_enabled' => (bool) $settings->get('payment_tap_enabled', true),
            'payment_tabby_enabled' => (bool) $settings->get('payment_tabby_enabled', true),
            'payment_tamara_enabled' => (bool) $settings->get('payment_tamara_enabled', true),
            'payment_cod_enabled' => (bool) $settings->get('payment_cod_enabled', false),

            // Shipping settings
            'shipping_enabled' => $settings->get('shipping.enabled', true),
            'shipping_provider' => $settings->get('shipping.provider', 'flat_rate'),
            'shipping_flat_rate_amount' => $settings->get('shipping.flat_rate_amount'),
            'shipping_free_shipping_min' => $settings->get('shipping.free_shipping_min'),
            'shipping_default_weight_unit' => $settings->get('shipping.default_weight_unit', 'kg'),

            // Aramex pickup address
            'aramex_country_code' => $settings->get('aramex.country_code', 'AE'),
            'aramex_sender_company' => $settings->get('aramex.sender_company', 'AD Perfumes'),
            'aramex_sender_name' => $settings->get('aramex.sender_name', 'AD Perfumes'),
            'aramex_sender_address' => $settings->get('aramex.sender_address'),
            'aramex_sender_address_2' => $settings->get('aramex.sender_address_2'),
            'aramex_sender_city' => $settings->get('aramex.sender_city', 'Dubai'),
            'aramex_sender_postal_code' => $settings->get('aramex.sender_postal_code'),
            'aramex_sender_phone' => $settings->get('aramex.sender_phone', '+971 4 1234567'),
            'aramex_sender_mobile' => $settings->get('aramex.sender_mobile', '+971 50 1234567'),
            'aramex_sender_email' => $settings->get('aramex.sender_email', 'shipping@adperfumes.com'),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Payment Methods')
                    ->description('Enable or disable payment methods shown during checkout.')
                    ->schema([
                        Forms\Components\Toggle::make('payment_tap_enabled')
                            ->label('Tap Payments (Credit/Debit Card)')
                            ->default(true)
                            ->helperText('Visa, Mastercard, AMEX via Tap Payments'),

                        Forms\Components\Toggle::make('payment_tabby_enabled')
                            ->label('Tabby (Buy Now Pay Later)')
                            ->default(true)
                            ->helperText('Split into 4 interest-free payments (AED 200 - AED 10,000)'),

                        Forms\Components\Toggle::make('payment_tamara_enabled')
                            ->label('Tamara (Buy Now Pay Later)')
                            ->default(true)
                            ->helperText('Pay in 3 installments, 0% interest (AED 100 - AED 20,000)'),

                        Forms\Components\Toggle::make('payment_cod_enabled')
                            ->label('Cash on Delivery (COD)')
                            ->default(false)
                            ->helperText('Customer pays when receiving the order'),
                    ])->columns(2),

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
                                - ARAMEX_ACCOUNT_PIN
                                - ARAMEX_ACCOUNT_ENTITY
                            ')
                            ->columnSpanFull(),

                        Forms\Components\Fieldset::make('Pickup Address')
                            ->schema([
                                Forms\Components\TextInput::make('aramex_sender_company')
                                    ->label('Company Name')
                                    ->default('AD Perfumes')
                                    ->maxLength(255)
                                    ->required(),

                                Forms\Components\TextInput::make('aramex_sender_name')
                                    ->label('Contact Person Name')
                                    ->default('AD Perfumes')
                                    ->maxLength(255)
                                    ->required(),

                                Forms\Components\Textarea::make('aramex_sender_address')
                                    ->label('Address Line 1')
                                    ->rows(2)
                                    ->required()
                                    ->placeholder('Building name, street address')
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('aramex_sender_address_2')
                                    ->label('Address Line 2')
                                    ->maxLength(255)
                                    ->placeholder('Floor, unit number (optional)')
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('aramex_sender_city')
                                    ->label('City')
                                    ->default('Dubai')
                                    ->maxLength(255)
                                    ->required(),

                                Forms\Components\Select::make('aramex_country_code')
                                    ->label('Country')
                                    ->options([
                                        'AE' => 'United Arab Emirates',
                                        'SA' => 'Saudi Arabia',
                                        'KW' => 'Kuwait',
                                        'BH' => 'Bahrain',
                                        'QA' => 'Qatar',
                                        'OM' => 'Oman',
                                    ])
                                    ->default('AE')
                                    ->required(),

                                Forms\Components\TextInput::make('aramex_sender_postal_code')
                                    ->label('Postal/ZIP Code')
                                    ->maxLength(255)
                                    ->helperText('Optional for UAE addresses'),
                            ])
                            ->columns(3)
                            ->columnSpanFull(),

                        Forms\Components\Fieldset::make('Contact Information')
                            ->schema([
                                Forms\Components\TextInput::make('aramex_sender_phone')
                                    ->label('Phone Number')
                                    ->tel()
                                    ->default('+971 4 1234567')
                                    ->maxLength(255)
                                    ->required()
                                    ->placeholder('+971 4 XXXXXXX'),

                                Forms\Components\TextInput::make('aramex_sender_mobile')
                                    ->label('Mobile Number')
                                    ->tel()
                                    ->default('+971 50 1234567')
                                    ->maxLength(255)
                                    ->required()
                                    ->placeholder('+971 50 XXXXXXX'),

                                Forms\Components\TextInput::make('aramex_sender_email')
                                    ->label('Email Address')
                                    ->email()
                                    ->default('shipping@adperfumes.com')
                                    ->maxLength(255)
                                    ->required(),
                            ])
                            ->columns(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(1)
                    ->visible(fn (Forms\Get $get) => $get('shipping_provider') === 'aramex'),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $settings = app(SettingsService::class);

        // Map form field names to settings keys (convert underscores to dots for nested keys)
        $keyMap = [
            'aramex_country_code' => 'aramex.country_code',
            'aramex_sender_company' => 'aramex.sender_company',
            'aramex_sender_name' => 'aramex.sender_name',
            'aramex_sender_address' => 'aramex.sender_address',
            'aramex_sender_address_2' => 'aramex.sender_address_2',
            'aramex_sender_city' => 'aramex.sender_city',
            'aramex_sender_postal_code' => 'aramex.sender_postal_code',
            'aramex_sender_phone' => 'aramex.sender_phone',
            'aramex_sender_mobile' => 'aramex.sender_mobile',
            'aramex_sender_email' => 'aramex.sender_email',
        ];

        foreach ($data as $key => $value) {
            // Use mapped key if exists, otherwise use original key
            $settingKey = $keyMap[$key] ?? $key;
            $settings->set($settingKey, $value);
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
