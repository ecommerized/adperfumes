<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use App\Services\Shipping\AramexService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('generateAwb')
                ->label('Generate AWB')
                ->icon('heroicon-o-truck')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Generate Aramex AWB')
                ->modalDescription('This will create a shipment with Aramex and generate an AWB (Air Waybill) for this order.')
                ->visible(fn (): bool =>
                    empty($this->record->tracking_number) &&
                    in_array($this->record->status, ['confirmed', 'processing']) &&
                    in_array($this->record->payment_status, ['paid', 'cod_pending'])
                )
                ->action(function () {
                    $aramexService = app(AramexService::class);
                    $record = $this->record;

                    // Validate required fields before generating AWB
                    $fullName = trim($record->full_name ?? '');
                    if (empty($fullName)) {
                        Notification::make()
                            ->title('Missing Customer Name')
                            ->body('Order must have a customer first name and last name.')
                            ->danger()
                            ->send();
                        return;
                    }

                    if (empty($record->phone)) {
                        Notification::make()
                            ->title('Missing Phone Number')
                            ->body('Order must have a customer phone number.')
                            ->danger()
                            ->send();
                        return;
                    }

                    if (empty($record->address) || empty($record->city)) {
                        Notification::make()
                            ->title('Missing Address')
                            ->body('Order must have a complete shipping address (address and city required).')
                            ->danger()
                            ->send();
                        return;
                    }

                    $shipmentData = [
                        'order_number' => $record->order_number,
                        'full_name' => $fullName,
                        'email' => $record->email ?? '',
                        'phone' => $record->phone,
                        'address' => $record->address,
                        'address2' => $record->address2 ?? '',
                        'city' => $record->city,
                        'country' => $record->country ?? 'UAE',
                        'postal_code' => $record->postal_code ?? '',
                        'weight' => 1.0, // Default 1kg
                        'pieces' => 1, // Default 1 piece
                    ];

                    $result = $aramexService->createShipment($shipmentData);

                    if ($result['success']) {
                        $record->update([
                            'tracking_number' => $result['tracking_number'],
                            'aramex_shipment_id' => $result['aramex_shipment_id'] ?? $result['tracking_number'],
                            'status' => 'processing',
                        ]);

                        Notification::make()
                            ->title('AWB Generated Successfully')
                            ->body('Tracking #: ' . $result['tracking_number'])
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('AWB Generation Failed')
                            ->body($result['message'] ?? 'Unknown error occurred')
                            ->danger()
                            ->send();
                    }
                }),
            Actions\Action::make('trackShipment')
                ->label('Track Shipment')
                ->icon('heroicon-o-map-pin')
                ->color('info')
                ->visible(fn (): bool => !empty($this->record->tracking_number))
                ->url(fn (): string => 'https://www.aramex.com/track/results?ShipmentNumber=' . $this->record->tracking_number)
                ->openUrlInNewTab(),
            Actions\EditAction::make(),
        ];
    }
}
