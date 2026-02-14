<?php

namespace App\Filament\Resources\SocialMediaPostResource\Pages;

use App\Filament\Resources\SocialMediaPostResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSocialMediaPost extends EditRecord
{
    protected static string $resource = SocialMediaPostResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn ($record) => in_array($record->status, ['draft', 'failed'])),
        ];
    }
}
