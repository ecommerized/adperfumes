<?php

namespace Database\Seeders;

use App\Models\Note;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class NotesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $topNotes = [
            'Bergamot',
            'Lemon',
            'Lavender',
            'Mandarin Orange',
            'Pink Pepper',
            'Grapefruit',
            'Neroli',
            'Cardamom',
            'Ginger',
            'Mint',
            'Lemon Verbena',
            'Petitgrain',
            'Orange Blossom',
            'Lime',
            'Blackcurrant',
            'Apple',
            'Pineapple',
            'Melon',
            'Peach',
            'Pear',
        ];

        $middleNotes = [
            'Rose',
            'Jasmine',
            'Iris',
            'Geranium',
            'Lily',
            'Violet',
            'Lily-of-the-Valley',
            'Ylang-Ylang',
            'Tuberose',
            'Magnolia',
            'Freesia',
            'Peony',
            'Orchid',
            'Cinnamon',
            'Nutmeg',
            'Clove',
            'Heliotrope',
            'Rosemary',
            'Thyme',
            'Sage',
        ];

        $baseNotes = [
            'Sandalwood',
            'Musk',
            'Amber',
            'Vanilla',
            'Patchouli',
            'Cedarwood',
            'Vetiver',
            'Oakmoss',
            'Tonka Bean',
            'Benzoin',
            'Incense',
            'Leather',
            'Oud',
            'Labdanum',
            'Ambergris',
            'Guaiac Wood',
            'Cashmere Wood',
            'White Musk',
            'Dark Musk',
            'Tobacco',
        ];

        foreach ($topNotes as $note) {
            Note::create([
                'name' => $note,
                'type' => 'top',
            ]);
        }

        foreach ($middleNotes as $note) {
            Note::create([
                'name' => $note,
                'type' => 'middle',
            ]);
        }

        foreach ($baseNotes as $note) {
            Note::create([
                'name' => $note,
                'type' => 'base',
            ]);
        }
    }
}
