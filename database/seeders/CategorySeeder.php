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
        // Create at least 20 dummy categories
        $categories = [
            ['name' => 'School Supply', 'description' => 'All school supplies and educational materials'],
            ['name' => 'Computers & Laptops', 'description' => 'Laptops, desktops, and computer accessories'],
            ['name' => 'Smartphones & Accessories', 'description' => 'Mobile phones, cases, chargers, and phone accessories'],
            ['name' => 'Audio & Video', 'description' => 'Headphones, speakers, TVs, and audio/video equipment'],
            ['name' => 'Gaming & Consoles', 'description' => 'Gaming consoles, games, and gaming accessories'],
            ['name' => 'Cameras & Photography', 'description' => 'Cameras, lenses, and photography equipment'],
            ['name' => 'Men\'s Clothing', 'description' => 'Clothing and apparel for men'],
            ['name' => 'Women\'s Clothing', 'description' => 'Clothing and apparel for women'],
            ['name' => 'Furniture', 'description' => 'Home and office furniture'],
            ['name' => 'Kitchen & Dining', 'description' => 'Kitchen appliances, cookware, and dining items'],
            ['name' => 'Skincare', 'description' => 'Skincare products and beauty treatments'],
            ['name' => 'Makeup', 'description' => 'Cosmetics and makeup products'],
            ['name' => 'Fitness & Exercise', 'description' => 'Fitness equipment and exercise gear'],
            ['name' => 'Books', 'description' => 'Books and reading materials'],
            ['name' => 'Car Parts', 'description' => 'Automotive parts and accessories'],
            ['name' => 'Board Games', 'description' => 'Board games and tabletop games'],
            ['name' => 'Building Sets', 'description' => 'Construction toys and building sets'],
            ['name' => 'Coffee & Tea', 'description' => 'Coffee, tea, and related beverages'],
            ['name' => 'Technology', 'description' => 'Technology products and gadgets'],
            ['name' => 'Dog Supplies', 'description' => 'Pet supplies for dogs'],
            ['name' => 'Baby Care', 'description' => 'Baby care products and essentials'],
            ['name' => 'Diapers & Wipes', 'description' => 'Baby diapers and wipes'],
            ['name' => 'Drawing & Painting', 'description' => 'Art supplies for drawing and painting'],
            ['name' => 'Guitars', 'description' => 'Guitars and guitar accessories'],
            ['name' => 'Jewelry & Watches', 'description' => 'Jewelry, watches, and accessories'],
            ['name' => 'Garden & Outdoor', 'description' => 'Garden supplies and outdoor equipment'],
            ['name' => 'Personal Care', 'description' => 'Personal care and hygiene products'],
            ['name' => 'Snacks & Candy', 'description' => 'Snacks, candies, and treats'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }

        $this->command->info('Categories seeded successfully!');
    }
} 