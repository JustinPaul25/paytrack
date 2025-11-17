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
        // Create School Supply category only
        Category::create([
            'name' => 'School Supply',
            'description' => 'All school supplies and educational materials'
        ]);

        $this->command->info('Categories seeded successfully!');
    }
} 