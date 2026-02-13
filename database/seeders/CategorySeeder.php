<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing categories to make seeder idempotent
        Category::query()->delete();

        $sortOrder = 0;

        // 1. Gender / Audience
        $gender = Category::create([
            'name' => 'Gender',
            'slug' => 'gender',
            'sort_order' => ++$sortOrder,
            'is_active' => true,
        ]);

        Category::create(['name' => 'Men', 'slug' => 'men', 'parent_id' => $gender->id, 'sort_order' => 1, 'is_active' => true]);
        Category::create(['name' => 'Women', 'slug' => 'women', 'parent_id' => $gender->id, 'sort_order' => 2, 'is_active' => true]);
        Category::create(['name' => 'Unisex', 'slug' => 'unisex', 'parent_id' => $gender->id, 'sort_order' => 3, 'is_active' => true]);

        // 2. Luxury Type
        $luxuryType = Category::create([
            'name' => 'Luxury Type',
            'slug' => 'luxury-type',
            'sort_order' => ++$sortOrder,
            'is_active' => true,
        ]);

        Category::create(['name' => 'Niche Perfumes', 'slug' => 'niche-perfumes', 'parent_id' => $luxuryType->id, 'sort_order' => 1, 'is_active' => true]);
        Category::create(['name' => 'Designer Perfumes', 'slug' => 'designer-perfumes', 'parent_id' => $luxuryType->id, 'sort_order' => 2, 'is_active' => true]);
        Category::create(['name' => 'Arabic Perfumes', 'slug' => 'arabic-perfumes', 'parent_id' => $luxuryType->id, 'sort_order' => 3, 'is_active' => true]);

        // 3. Concentration
        $concentration = Category::create([
            'name' => 'Concentration',
            'slug' => 'concentration',
            'sort_order' => ++$sortOrder,
            'is_active' => true,
        ]);

        Category::create(['name' => 'Parfum', 'slug' => 'parfum', 'parent_id' => $concentration->id, 'sort_order' => 1, 'is_active' => true]);
        Category::create(['name' => 'Eau de Parfum (EDP)', 'slug' => 'eau-de-parfum', 'parent_id' => $concentration->id, 'sort_order' => 2, 'is_active' => true]);
        Category::create(['name' => 'Eau de Toilette (EDT)', 'slug' => 'eau-de-toilette', 'parent_id' => $concentration->id, 'sort_order' => 3, 'is_active' => true]);

        // 4. Collections
        $collections = Category::create([
            'name' => 'Collections',
            'slug' => 'collections',
            'sort_order' => ++$sortOrder,
            'is_active' => true,
        ]);

        Category::create(['name' => 'New Arrivals', 'slug' => 'new-arrivals', 'parent_id' => $collections->id, 'sort_order' => 1, 'is_active' => true]);
        Category::create(['name' => 'Best Sellers', 'slug' => 'best-sellers', 'parent_id' => $collections->id, 'sort_order' => 2, 'is_active' => true]);
        Category::create(['name' => 'Gift Sets', 'slug' => 'gift-sets', 'parent_id' => $collections->id, 'sort_order' => 3, 'is_active' => true]);

        $this->command->info('✓ Created 4 parent categories and 12 child categories');
    }
}
