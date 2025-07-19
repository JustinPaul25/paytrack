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
        // Main Categories
        $mainCategories = [
            'Electronics' => [
                'Computers & Laptops',
                'Smartphones & Accessories',
                'Audio & Video',
                'Gaming & Consoles',
                'Cameras & Photography'
            ],
            'Fashion & Apparel' => [
                'Men\'s Clothing',
                'Women\'s Clothing',
                'Kids & Baby',
                'Shoes & Footwear',
                'Jewelry & Watches',
                'Bags & Accessories'
            ],
            'Home & Garden' => [
                'Furniture',
                'Kitchen & Dining',
                'Bedding & Bath',
                'Home Decor',
                'Garden & Outdoor',
                'Tools & Hardware'
            ],
            'Health & Beauty' => [
                'Skincare',
                'Makeup',
                'Hair Care',
                'Personal Care',
                'Health & Wellness',
                'Fragrances'
            ],
            'Sports & Outdoors' => [
                'Fitness & Exercise',
                'Team Sports',
                'Outdoor Recreation',
                'Swimming',
                'Cycling',
                'Hunting & Fishing'
            ],
            'Books & Media' => [
                'Books',
                'Movies & TV Shows',
                'Music',
                'Magazines',
                'Educational Materials',
                'Art & Craft Supplies'
            ],
            'Automotive' => [
                'Car Parts',
                'Motorcycle Parts',
                'Tires & Wheels',
                'Car Care',
                'Electronics & GPS',
                'Tools & Equipment'
            ],
            'Toys & Games' => [
                'Action Figures',
                'Board Games',
                'Educational Toys',
                'Building Sets',
                'Dolls & Accessories',
                'Outdoor Toys'
            ],
            'Food & Beverages' => [
                'Snacks & Candy',
                'Beverages',
                'Organic & Natural',
                'International Foods',
                'Baking Supplies',
                'Coffee & Tea'
            ],
            'Office & Business' => [
                'Office Supplies',
                'Paper & Stationery',
                'Printers & Scanners',
                'Furniture',
                'Technology',
                'Business Services'
            ]
        ];

        foreach ($mainCategories as $mainCategory => $subCategories) {
            // Create main category
            $mainCat = Category::create([
                'name' => $mainCategory,
                'description' => "All products related to {$mainCategory}",
                'parent_id' => null
            ]);

            // Create sub-categories
            foreach ($subCategories as $subCategory) {
                Category::create([
                    'name' => $subCategory,
                    'description' => "Products in the {$subCategory} category",
                    'parent_id' => $mainCat->id
                ]);
            }
        }

        // Additional specialized categories
        $specializedCategories = [
            'Pet Supplies' => [
                'Dog Supplies',
                'Cat Supplies',
                'Fish & Aquarium',
                'Bird Supplies',
                'Small Animal Supplies'
            ],
            'Baby & Kids' => [
                'Baby Care',
                'Diapers & Wipes',
                'Baby Food',
                'Strollers & Car Seats',
                'Kids Furniture',
                'School Supplies'
            ],
            'Art & Crafts' => [
                'Drawing & Painting',
                'Sewing & Needlework',
                'Scrapbooking',
                'Model Building',
                'Candle Making',
                'Jewelry Making'
            ],
            'Musical Instruments' => [
                'Guitars',
                'Pianos & Keyboards',
                'Drums & Percussion',
                'Wind Instruments',
                'String Instruments',
                'Accessories'
            ]
        ];

        foreach ($specializedCategories as $mainCategory => $subCategories) {
            // Create main category
            $mainCat = Category::create([
                'name' => $mainCategory,
                'description' => "All products related to {$mainCategory}",
                'parent_id' => null
            ]);

            // Create sub-categories
            foreach ($subCategories as $subCategory) {
                Category::create([
                    'name' => $subCategory,
                    'description' => "Products in the {$subCategory} category",
                    'parent_id' => $mainCat->id
                ]);
            }
        }

        $this->command->info('Categories seeded successfully!');
    }
} 