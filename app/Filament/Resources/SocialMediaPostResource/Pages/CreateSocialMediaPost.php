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

        // Use generated image path if available
        if (!empty($data['generated_image_path'])) {
            $data['image_path'] = $data['generated_image_path'];
            unset($data['generated_image_path']);
        }
        // If no custom image but product selected, use product image
        elseif (empty($data['image_path']) && !empty($data['product_id'])) {
            $product = \App\Models\Product::find($data['product_id']);
            if ($product && $product->image) {
                $data['image_path'] = $product->image;
            }
        }

        return $data;
    }
}
