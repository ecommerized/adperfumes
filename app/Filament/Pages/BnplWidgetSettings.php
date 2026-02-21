<?php

namespace App\Filament\Pages;

use App\Services\SettingsService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Notifications\Notification;

class BnplWidgetSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'BNPL Widgets';

    protected static ?string $title = 'Buy Now Pay Later Widget Settings';

    protected static string $view = 'filament.pages.bnpl-widget-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = app(SettingsService::class);

        $this->form->fill([
            // Tabby widget settings
            'bnpl_tabby_widget_enabled' => (bool) $settings->get('bnpl_tabby_widget_enabled', false),
            'bnpl_tabby_public_key' => $settings->get('bnpl_tabby_public_key', ''),
            'bnpl_tabby_merchant_code' => $settings->get('bnpl_tabby_merchant_code', ''),
            'bnpl_tabby_min_amount' => $settings->get('bnpl_tabby_min_amount', 200),
            'bnpl_tabby_max_amount' => $settings->get('bnpl_tabby_max_amount', 10000),
            'bnpl_tabby_installment_count' => $settings->get('bnpl_tabby_installment_count', 4),

            // Tamara widget settings
            'bnpl_tamara_widget_enabled' => (bool) $settings->get('bnpl_tamara_widget_enabled', false),
            'bnpl_tamara_public_key' => $settings->get('bnpl_tamara_public_key', ''),
            'bnpl_tamara_min_amount' => $settings->get('bnpl_tamara_min_amount', 100),
            'bnpl_tamara_max_amount' => $settings->get('bnpl_tamara_max_amount', 20000),
            'bnpl_tamara_installment_count' => $settings->get('bnpl_tamara_installment_count', 3),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Tabby — Split in 4')
                    ->description('Configure the Tabby installment widget shown on product pages. Customers see "4 interest-free payments of AED X.XX" below the price.')
                    ->schema([
                        Forms\Components\Toggle::make('bnpl_tabby_widget_enabled')
                            ->label('Enable Tabby Widget')
                            ->helperText('Show the installment message on product pages. The Tabby payment method must also be enabled in Payment & Delivery settings.'),

                        Forms\Components\TextInput::make('bnpl_tabby_public_key')
                            ->label('Tabby Public Key')
                            ->helperText('Public key for the widget. The secret API key remains in your .env file for security.')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('bnpl_tabby_merchant_code')
                            ->label('Merchant Code')
                            ->helperText('Your Tabby merchant code'),

                        Forms\Components\TextInput::make('bnpl_tabby_installment_count')
                            ->label('Number of Installments')
                            ->numeric()
                            ->default(4)
                            ->minValue(2)
                            ->maxValue(6)
                            ->helperText('How many installments to split the payment into'),

                        Forms\Components\TextInput::make('bnpl_tabby_min_amount')
                            ->label('Minimum Amount')
                            ->numeric()
                            ->prefix('AED')
                            ->default(200)
                            ->helperText('Widget only shows for products priced at or above this amount'),

                        Forms\Components\TextInput::make('bnpl_tabby_max_amount')
                            ->label('Maximum Amount')
                            ->numeric()
                            ->prefix('AED')
                            ->default(10000)
                            ->helperText('Widget only shows for products priced at or below this amount'),
                    ])->columns(2),

                Forms\Components\Section::make('Tamara — Pay in Installments')
                    ->description('Configure the Tamara installment widget shown on product pages. Customers see "3 monthly payments of AED X.XX" below the price.')
                    ->schema([
                        Forms\Components\Toggle::make('bnpl_tamara_widget_enabled')
                            ->label('Enable Tamara Widget')
                            ->helperText('Show the installment message on product pages. The Tamara payment method must also be enabled in Payment & Delivery settings.'),

                        Forms\Components\TextInput::make('bnpl_tamara_public_key')
                            ->label('Tamara Public Key')
                            ->helperText('Public key for the widget. The secret API token remains in your .env file for security.')
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('bnpl_tamara_installment_count')
                            ->label('Number of Installments')
                            ->numeric()
                            ->default(3)
                            ->minValue(2)
                            ->maxValue(6)
                            ->helperText('How many installments to split the payment into'),

                        Forms\Components\TextInput::make('bnpl_tamara_min_amount')
                            ->label('Minimum Amount')
                            ->numeric()
                            ->prefix('AED')
                            ->default(100)
                            ->helperText('Widget only shows for products priced at or above this amount'),

                        Forms\Components\TextInput::make('bnpl_tamara_max_amount')
                            ->label('Maximum Amount')
                            ->numeric()
                            ->prefix('AED')
                            ->default(20000)
                            ->helperText('Widget only shows for products priced at or below this amount'),
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
            ->title('BNPL widget settings saved successfully')
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
