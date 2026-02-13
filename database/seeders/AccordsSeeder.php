<?php

namespace Database\Seeders;

use App\Models\Accord;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AccordsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $accords = [
            'Woody',
            'Floral',
            'Fresh',
            'Oriental',
            'Citrus',
            'Spicy',
            'Aquatic',
            'Aromatic',
            'Fruity',
            'Green',
            'Powdery',
            'Amber',
            'Musky',
            'Earthy',
            'Smoky',
            'Sweet',
            'Leathery',
            'Balsamic',
            'Aldehydic',
            'Gourmand',
        ];

        foreach ($accords as $accord) {
            Accord::create([
                'name' => $accord,
                'slug' => Str::slug($accord),
            ]);
        }
    }
}
