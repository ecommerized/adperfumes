<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Product;
use App\Models\Note;
use App\Models\Accord;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create luxury brands
        $dior = Brand::firstOrCreate(
            ['slug' => 'dior'],
            [
                'name' => 'Dior',
                'description' => 'Christian Dior - French luxury fashion house',
                'status' => true,
            ]
        );

        $chanel = Brand::firstOrCreate(
            ['slug' => 'chanel'],
            [
                'name' => 'Chanel',
                'description' => 'Chanel - Iconic French luxury brand',
                'status' => true,
            ]
        );

        $tomford = Brand::firstOrCreate(
            ['slug' => 'tom-ford'],
            [
                'name' => 'Tom Ford',
                'description' => 'Tom Ford - American luxury fashion designer',
                'status' => true,
            ]
        );

        $creed = Brand::firstOrCreate(
            ['slug' => 'creed'],
            [
                'name' => 'Creed',
                'description' => 'Creed - Historic French perfume house',
                'status' => true,
            ]
        );

        // Get notes for products
        $bergamot = Note::where('name', 'Bergamot')->first();
        $lemon = Note::where('name', 'Lemon')->first();
        $lavender = Note::where('name', 'Lavender')->first();
        $pepper = Note::where('name', 'Pink Pepper')->first();
        $rose = Note::where('name', 'Rose')->first();
        $jasmine = Note::where('name', 'Jasmine')->first();
        $iris = Note::where('name', 'Iris')->first();
        $geranium = Note::where('name', 'Geranium')->first();
        $patchouli = Note::where('name', 'Patchouli')->first();
        $amber = Note::where('name', 'Amber')->first();
        $vanilla = Note::where('name', 'Vanilla')->first();
        $musk = Note::where('name', 'Musk')->first();
        $sandalwood = Note::where('name', 'Sandalwood')->first();
        $oud = Note::where('name', 'Oud')->first();

        // Get accords
        $woody = Accord::where('slug', 'woody')->first();
        $floral = Accord::where('slug', 'floral')->first();
        $fresh = Accord::where('slug', 'fresh')->first();
        $oriental = Accord::where('slug', 'oriental')->first();
        $aromatic = Accord::where('slug', 'aromatic')->first();

        // Product 1: Dior Sauvage
        $sauvage = Product::create([
            'name' => 'Sauvage Eau de Parfum',
            'slug' => 'dior-sauvage-edp',
            'description' => 'A powerful and noble fragrance. Sauvage Eau de Parfum unleashes a raw spirit in a composition distinguished by a spicy freshness.',
            'price' => 425.00,
            'stock' => 50,
            'brand_id' => $dior->id,
            'status' => true,
        ]);
        $sauvage->notes()->attach([$bergamot->id, $pepper->id, $lavender->id, $patchouli->id, $amber->id]);
        $sauvage->accords()->attach([
            $woody->id => ['percentage' => 60],
            $fresh->id => ['percentage' => 25],
            $aromatic->id => ['percentage' => 15],
        ]);

        // Product 2: Chanel Coco Mademoiselle
        $coco = Product::create([
            'name' => 'Coco Mademoiselle Eau de Parfum',
            'slug' => 'chanel-coco-mademoiselle',
            'description' => 'A vibrant, voluptuous fragrance with a clear and sensual character. An irresistibly sexy ambery scent.',
            'price' => 495.00,
            'stock' => 35,
            'brand_id' => $chanel->id,
            'status' => true,
        ]);
        $coco->notes()->attach([$bergamot->id, $lemon->id, $rose->id, $jasmine->id, $patchouli->id, $vanilla->id]);
        $coco->accords()->attach([
            $floral->id => ['percentage' => 60],
            $oriental->id => ['percentage' => 30],
            $fresh->id => ['percentage' => 10],
        ]);

        // Product 3: Tom Ford Oud Wood
        $oudwood = Product::create([
            'name' => 'Oud Wood Eau de Parfum',
            'slug' => 'tom-ford-oud-wood',
            'description' => 'Rare oud wood, exotic spices and cardamom give it a warm, woody scent. Sensual and enveloping.',
            'price' => 850.00,
            'stock' => 20,
            'brand_id' => $tomford->id,
            'status' => true,
        ]);
        $oudwood->notes()->attach([$oud->id, $sandalwood->id, $vanilla->id, $amber->id]);
        $oudwood->accords()->attach([
            $woody->id => ['percentage' => 75],
            $oriental->id => ['percentage' => 25],
        ]);

        // Product 4: Creed Aventus
        $aventus = Product::create([
            'name' => 'Aventus Eau de Parfum',
            'slug' => 'creed-aventus',
            'description' => 'Aventus celebrates strength, power and success, inspired by the dramatic life of war, peace and romance lived by Emperor Napoleon.',
            'price' => 1250.00,
            'stock' => 15,
            'brand_id' => $creed->id,
            'status' => true,
        ]);
        $aventus->notes()->attach([$bergamot->id, $lemon->id, $pepper->id, $patchouli->id, $musk->id]);
        $aventus->accords()->attach([
            $fresh->id => ['percentage' => 40],
            $woody->id => ['percentage' => 35],
            $aromatic->id => ['percentage' => 25],
        ]);

        // Product 5: Dior Miss Dior
        $missdior = Product::create([
            'name' => 'Miss Dior Eau de Parfum',
            'slug' => 'dior-miss-dior',
            'description' => 'A radiant floral bouquet with notes of Grasse Rose and Peony. A delicate and refined fragrance.',
            'price' => 465.00,
            'stock' => 40,
            'brand_id' => $dior->id,
            'status' => true,
        ]);
        $missdior->notes()->attach([$bergamot->id, $rose->id, $jasmine->id, $iris->id, $musk->id]);
        $missdior->accords()->attach([
            $floral->id => ['percentage' => 80],
            $fresh->id => ['percentage' => 20],
        ]);

        // Product 6: Chanel Bleu de Chanel
        $bleu = Product::create([
            'name' => 'Bleu de Chanel Eau de Parfum',
            'slug' => 'chanel-bleu',
            'description' => 'An aromatic woody fragrance that reveals the spirit of a man who chooses his own destiny with singular determination.',
            'price' => 515.00,
            'stock' => 45,
            'brand_id' => $chanel->id,
            'status' => true,
        ]);
        $bleu->notes()->attach([$lemon->id, $pepper->id, $geranium->id, $sandalwood->id, $amber->id]);
        $bleu->accords()->attach([
            $woody->id => ['percentage' => 50],
            $aromatic->id => ['percentage' => 30],
            $fresh->id => ['percentage' => 20],
        ]);
    }
}
