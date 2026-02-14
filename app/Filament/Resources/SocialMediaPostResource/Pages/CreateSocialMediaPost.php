<?php

namespace App\Filament\Resources\SocialMediaPostResource\Pages;

use App\Filament\Resources\SocialMediaPostResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSocialMediaPost extends CreateRecord
{
    protected static string $resource = SocialMediaPostResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = (string) (auth()->id() ?? 'admin');
        $data['source'] = 'manual';

        // If no custom image but product selected, use product image
        if (empty($data['image_path']) && !empty($data['product_id'])) {
            $product = \App\Models\Product::find($data['product_id']);
            if ($product && $product->image) {
                $data['image_path'] = $product->image;
            }
        }

        return $data;
    }
}
