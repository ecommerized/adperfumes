<?php

namespace App\Filament\Pages;

use App\Models\Customer;
use App\Services\SettingsService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;

class WhatsAppMarketing extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationGroup = 'Marketing';

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'WhatsApp Marketing';

    protected static string $view = 'filament.pages.whatsapp-marketing';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = app(SettingsService::class);

        $this->form->fill([
            'whatsapp_business_number' => $settings->get('whatsapp_business_number'),
            'whatsapp_api_token' => $settings->get('whatsapp_api_token'),
            'whatsapp_business_id' => $settings->get('whatsapp_business_id'),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('WhatsApp Business API Configuration')
                    ->description('Configure your WhatsApp Business API credentials for sending marketing messages.')
                    ->schema([
                        Forms\Components\TextInput::make('whatsapp_business_number')
                            ->label('WhatsApp Business Phone Number')
                            ->placeholder('+971 50 123 4567')
                            ->tel()
                            ->maxLength(20),

                        Forms\Components\TextInput::make('whatsapp_business_id')
                            ->label('WhatsApp Business Account ID')
                            ->placeholder('e.g. 1234567890')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('whatsapp_api_token')
                            ->label('WhatsApp Cloud API Token')
                            ->password()
                            ->revealable()
                            ->placeholder('Permanent token from Meta Business Settings')
                            ->maxLength(500),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Customer::query()
                    ->where('marketing_whatsapp_opt_in', true)
                    ->whereNotNull('phone')
            )
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Customer')
                    ->searchable(['first_name', 'last_name'])
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-o-phone'),

                Tables\Columns\TextColumn::make('customer_segment')
                    ->label('Segment')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'vip' => 'success',
                        'regular' => 'primary',
                        'new' => 'info',
                        'inactive' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('total_orders')
                    ->label('Orders')
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('total_spent')
                    ->label('Total Spent')
                    ->money('AED'),

                Tables\Columns\TextColumn::make('last_order_at')
                    ->label('Last Order')
                    ->dateTime('M d, Y')
                    ->sortable(),
            ])
            ->defaultSort('last_order_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('customer_segment')
                    ->options([
                        'vip' => 'VIP Customers',
                        'regular' => 'Regular Customers',
                        'new' => 'New Customers',
                        'inactive' => 'Inactive Customers',
                    ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('exportNumbers')
                    ->label('Export Phone Numbers')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->requiresConfirmation()
                    ->action(function ($records) {
                        $data = "Name,Phone,Segment,Total Orders,Total Spent\n";
                        foreach ($records as $customer) {
                            $data .= implode(',', [
                                '"' . $customer->full_name . '"',
                                $customer->phone,
                                $customer->customer_segment ?? 'new',
                                $customer->total_orders,
                                $customer->total_spent,
                            ]) . "\n";
                        }

                        return response()->streamDownload(function () use ($data) {
                            echo $data;
                        }, 'whatsapp-contacts-' . now()->format('Y-m-d') . '.csv');
                    }),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $settings = app(SettingsService::class);

        foreach ($data as $key => $value) {
            $settings->set($key, $value);
        }

        Notification::make()
            ->title('WhatsApp settings saved successfully')
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
