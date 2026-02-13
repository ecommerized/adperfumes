<?php

namespace App\Filament\Merchant\Resources\ProductResource\Pages;

use App\Filament\Merchant\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    /**
     * Automatically set the merchant_id to the authenticated merchant
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['merchant_id'] = auth()->id();

        return $data;
    }
}
