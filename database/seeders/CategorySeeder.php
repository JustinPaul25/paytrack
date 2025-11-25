<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create school supplies categories
        $categories = [
            ['name' => 'Books', 'description' => 'Textbooks, notebooks, storybooks, and reading materials'],
            ['name' => 'Pens', 'description' => 'Ballpoint pens, gel pens, fountain pens, and writing pens'],
            ['name' => 'Pencils', 'description' => 'Wooden pencils, mechanical pencils, and colored pencils'],
            ['name' => 'Notebooks', 'description' => 'Spiral notebooks, composition books, and writing pads'],
            ['name' => 'Erasers', 'description' => 'Pencil erasers, art erasers, and correction supplies'],
            ['name' => 'Rulers & Measuring Tools', 'description' => 'Rulers, protractors, compasses, and measuring instruments'],
            ['name' => 'Calculators', 'description' => 'Scientific calculators, basic calculators, and graphing calculators'],
            ['name' => 'Backpacks & Bags', 'description' => 'School backpacks, messenger bags, and pencil cases'],
            ['name' => 'Markers & Highlighters', 'description' => 'Permanent markers, dry erase markers, and highlighters'],
            ['name' => 'Art Supplies', 'description' => 'Crayons, colored pencils, watercolors, and art materials'],
            ['name' => 'Binders & Folders', 'description' => 'Three-ring binders, folders, and organization supplies'],
            ['name' => 'Staplers & Paper Clips', 'description' => 'Staplers, paper clips, binder clips, and office supplies'],
            ['name' => 'Glue & Adhesives', 'description' => 'School glue, glue sticks, tape, and adhesive products'],
            ['name' => 'Scissors', 'description' => 'Safety scissors, craft scissors, and cutting tools'],
            ['name' => 'Index Cards & Sticky Notes', 'description' => 'Flash cards, index cards, and sticky note pads'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }

        $this->command->info('Categories seeded successfully!');
    }
} 