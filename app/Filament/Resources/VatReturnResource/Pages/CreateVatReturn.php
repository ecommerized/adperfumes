<?php

namespace App\Filament\Resources\VatReturnResource\Pages;

use App\Filament\Resources\VatReturnResource;
use Filament\Resources\Pages\CreateRecord;

class CreateVatReturn extends CreateRecord
{
    protected static string $resource = VatReturnResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
