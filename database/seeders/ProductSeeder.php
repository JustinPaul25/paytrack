<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Category;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all categories for product assignment
        $categories = Category::all();
        
        // Helper function to get category ID safely
        $getCategoryId = function($categoryName) use ($categories) {
            $category = $categories->where('name', $categoryName)->first();
            if (!$category) {
                throw new \Exception("Category '{$categoryName}' not found. Please run CategorySeeder first.");
            }
            return $category->id;
        };
        
        // Product data with realistic information
        $products = [
            // Electronics - Computers & Laptops
            [
                'name' => 'MacBook Pro 14" M3 Chip',
                'description' => 'Latest MacBook Pro with M3 chip, 14-inch Retina display, 16GB RAM, 512GB SSD. Perfect for professionals and creatives.',
                'category_id' => $getCategoryId('Computers & Laptops'),
                'purchase_price' => 1800.00,
                'selling_price' => 2199.00,
                'stock' => 15,
                'SKU' => 'MBP14-M3-001'
            ],
            [
                'name' => 'Dell XPS 13 Laptop',
                'description' => '13.4-inch InfinityEdge display, Intel Core i7, 16GB RAM, 512GB SSD. Ultra-thin and lightweight design.',
                'category_id' => $getCategoryId('Computers & Laptops'),
                'purchase_price' => 1200.00,
                'selling_price' => 1499.00,
                'stock' => 22,
                'SKU' => 'DLL-XPS13-002'
            ],
            [
                'name' => 'HP Pavilion Gaming Laptop',
                'description' => '15.6-inch gaming laptop with NVIDIA RTX 3060, AMD Ryzen 7, 16GB RAM, 1TB SSD. Perfect for gaming and content creation.',
                'category_id' => $getCategoryId('Computers & Laptops'),
                'purchase_price' => 950.00,
                'selling_price' => 1299.00,
                'stock' => 18,
                'SKU' => 'HP-PAV-GAM-003'
            ],
            
            // Electronics - Smartphones & Accessories
            [
                'name' => 'iPhone 15 Pro Max',
                'description' => '6.7-inch Super Retina XDR display, A17 Pro chip, 48MP camera system, 256GB storage. Latest iPhone with advanced features.',
                'category_id' => $getCategoryId('Smartphones & Accessories'),
                'purchase_price' => 1100.00,
                'selling_price' => 1299.00,
                'stock' => 25,
                'SKU' => 'IPH15-PRO-004'
            ],
            [
                'name' => 'Samsung Galaxy S24 Ultra',
                'description' => '6.8-inch Dynamic AMOLED display, Snapdragon 8 Gen 3, 200MP camera, S Pen included, 256GB storage.',
                'category_id' => $getCategoryId('Smartphones & Accessories'),
                'purchase_price' => 1050.00,
                'selling_price' => 1249.00,
                'stock' => 20,
                'SKU' => 'SMS-GAL-S24-005'
            ],
            [
                'name' => 'AirPods Pro 2nd Generation',
                'description' => 'Active noise cancellation, spatial audio, sweat and water resistant, up to 6 hours listening time.',
                'category_id' => $getCategoryId('Smartphones & Accessories'),
                'purchase_price' => 180.00,
                'selling_price' => 249.00,
                'stock' => 45,
                'SKU' => 'AIR-PRO-2-006'
            ],
            
            // Electronics - Audio & Video
            [
                'name' => 'Sony WH-1000XM5 Headphones',
                'description' => 'Industry-leading noise cancellation, 30-hour battery life, premium comfort, exceptional sound quality.',
                'category_id' => $getCategoryId( 'Audio & Video'),
                'purchase_price' => 280.00,
                'selling_price' => 399.00,
                'stock' => 30,
                'SKU' => 'SNY-WH1000-007'
            ],
            [
                'name' => 'Samsung 65" QLED 4K TV',
                'description' => '65-inch QLED 4K UHD Smart TV with Quantum HDR, Alexa Built-in, Gaming Hub, and Ambient Mode.',
                'category_id' => $getCategoryId( 'Audio & Video'),
                'purchase_price' => 1200.00,
                'selling_price' => 1599.00,
                'stock' => 12,
                'SKU' => 'SMS-65QLED-008'
            ],
            
            // Fashion - Men's Clothing
            [
                'name' => 'Nike Air Jordan 1 Retro High',
                'description' => 'Classic Air Jordan 1 in Chicago colorway, premium leather upper, Air-Sole unit for lightweight cushioning.',
                'category_id' => $getCategoryId( 'Men\'s Clothing'),
                'purchase_price' => 120.00,
                'selling_price' => 170.00,
                'stock' => 35,
                'SKU' => 'NKE-AJ1-CHI-009'
            ],
            [
                'name' => 'Levi\'s 501 Original Jeans',
                'description' => 'Classic straight fit jeans, 100% cotton denim, button fly, timeless style for everyday wear.',
                'category_id' => $getCategoryId( 'Men\'s Clothing'),
                'purchase_price' => 45.00,
                'selling_price' => 69.00,
                'stock' => 60,
                'SKU' => 'LVS-501-ORG-010'
            ],
            
            // Fashion - Women's Clothing
            [
                'name' => 'Zara Oversized Blazer',
                'description' => 'Contemporary oversized blazer in wool blend, perfect for office wear or casual styling.',
                'category_id' => $getCategoryId( 'Women\'s Clothing'),
                'purchase_price' => 85.00,
                'selling_price' => 129.00,
                'stock' => 28,
                'SKU' => 'ZRA-BLZ-OVR-011'
            ],
            [
                'name' => 'H&M Summer Dress',
                'description' => 'Floral print summer dress, lightweight fabric, adjustable straps, perfect for warm weather.',
                'category_id' => $getCategoryId( 'Women\'s Clothing'),
                'purchase_price' => 35.00,
                'selling_price' => 49.00,
                'stock' => 42,
                'SKU' => 'HM-DRS-SUM-012'
            ],
            
            // Home & Garden - Furniture
            [
                'name' => 'IKEA MALM Bed Frame',
                'description' => 'Queen-size bed frame in white, clean lines, under-bed storage, easy assembly.',
                'category_id' => $getCategoryId( 'Furniture'),
                'purchase_price' => 180.00,
                'selling_price' => 249.00,
                'stock' => 15,
                'SKU' => 'IKE-MALM-BED-013'
            ],
            [
                'name' => 'West Elm Sofa',
                'description' => 'Modern 3-seater sofa in gray fabric, comfortable cushions, sturdy frame, perfect for living room.',
                'category_id' => $getCategoryId( 'Furniture'),
                'purchase_price' => 800.00,
                'selling_price' => 1199.00,
                'stock' => 8,
                'SKU' => 'WST-ELM-SOF-014'
            ],
            
            // Home & Garden - Kitchen & Dining
            [
                'name' => 'KitchenAid Stand Mixer',
                'description' => 'Professional 5-quart stand mixer in red, 10-speed motor, includes flat beater, dough hook, and wire whip.',
                'category_id' => $getCategoryId( 'Kitchen & Dining'),
                'purchase_price' => 280.00,
                'selling_price' => 399.00,
                'stock' => 20,
                'SKU' => 'KIT-STD-MIX-015'
            ],
            [
                'name' => 'Le Creuset Dutch Oven',
                'description' => '5.5-quart round Dutch oven in blue, enameled cast iron, perfect for braising, stewing, and baking.',
                'category_id' => $getCategoryId( 'Kitchen & Dining'),
                'purchase_price' => 220.00,
                'selling_price' => 299.00,
                'stock' => 18,
                'SKU' => 'LCR-DUT-OVN-016'
            ],
            
            // Health & Beauty - Skincare
            [
                'name' => 'La Mer Moisturizing Cream',
                'description' => 'Luxury moisturizing cream with Miracle Broth, intensely hydrating, suitable for all skin types.',
                'category_id' => $getCategoryId( 'Skincare'),
                'purchase_price' => 280.00,
                'selling_price' => 350.00,
                'stock' => 25,
                'SKU' => 'LMR-MOI-CRM-017'
            ],
            [
                'name' => 'The Ordinary Niacinamide Serum',
                'description' => '10% Niacinamide + 1% Zinc serum, reduces blemishes and regulates sebum production.',
                'category_id' => $getCategoryId( 'Skincare'),
                'purchase_price' => 8.00,
                'selling_price' => 12.00,
                'stock' => 80,
                'SKU' => 'ORD-NIA-SER-018'
            ],
            
            // Health & Beauty - Makeup
            [
                'name' => 'MAC Ruby Woo Lipstick',
                'description' => 'Iconic matte red lipstick, long-wearing formula, suits all skin tones, classic MAC shade.',
                'category_id' => $getCategoryId( 'Makeup'),
                'purchase_price' => 15.00,
                'selling_price' => 19.00,
                'stock' => 55,
                'SKU' => 'MAC-RUB-WOO-019'
            ],
            [
                'name' => 'Urban Decay Naked Palette',
                'description' => '12 neutral eyeshadows in warm tones, highly pigmented, perfect for everyday and evening looks.',
                'category_id' => $getCategoryId( 'Makeup'),
                'purchase_price' => 45.00,
                'selling_price' => 54.00,
                'stock' => 30,
                'SKU' => 'URB-NAK-PAL-020'
            ],
            
            // Sports & Outdoors - Fitness & Exercise
            [
                'name' => 'Peloton Bike+',
                'description' => 'Premium indoor cycling bike with 24" HD rotating touchscreen, live classes, and performance tracking.',
                'category_id' => $getCategoryId( 'Fitness & Exercise'),
                'purchase_price' => 2400.00,
                'selling_price' => 2995.00,
                'stock' => 10,
                'SKU' => 'PEL-BIK-PLS-021'
            ],
            [
                'name' => 'Lululemon Align Leggings',
                'description' => 'Ultra-soft Nulu fabric, high-rise fit, perfect for yoga and low-impact activities.',
                'category_id' => $getCategoryId( 'Fitness & Exercise'),
                'purchase_price' => 75.00,
                'selling_price' => 98.00,
                'stock' => 40,
                'SKU' => 'LUL-ALN-LEG-022'
            ],
            
            // Books & Media - Books
            [
                'name' => 'The Seven Husbands of Evelyn Hugo',
                'description' => 'Bestselling novel by Taylor Jenkins Reid, compelling story of Hollywood glamour and secrets.',
                'category_id' => $getCategoryId( 'Books'),
                'purchase_price' => 12.00,
                'selling_price' => 16.99,
                'stock' => 65,
                'SKU' => 'BOK-SEV-HUS-023'
            ],
            [
                'name' => 'Atomic Habits by James Clear',
                'description' => 'International bestseller about building good habits and breaking bad ones, practical strategies.',
                'category_id' => $getCategoryId( 'Books'),
                'purchase_price' => 15.00,
                'selling_price' => 19.99,
                'stock' => 50,
                'SKU' => 'BOK-ATM-HAB-024'
            ],
            
            // Automotive - Car Parts
            [
                'name' => 'Michelin Pilot Sport 4S Tires',
                'description' => 'High-performance summer tires, excellent grip and handling, 245/40R18 size.',
                'category_id' => $getCategoryId( 'Car Parts'),
                'purchase_price' => 180.00,
                'selling_price' => 245.00,
                'stock' => 25,
                'SKU' => 'MCL-PLT-SPT-025'
            ],
            [
                'name' => 'Bosch Premium Wiper Blades',
                'description' => 'Premium windshield wiper blades, all-weather performance, easy installation.',
                'category_id' => $getCategoryId( 'Car Parts'),
                'purchase_price' => 25.00,
                'selling_price' => 34.99,
                'stock' => 45,
                'SKU' => 'BSC-PRM-WIP-026'
            ],
            
            // Toys & Games - Board Games
            [
                'name' => 'Catan Board Game',
                'description' => 'Classic strategy board game, build settlements, trade resources, 3-4 players, ages 10+.',
                'category_id' => $getCategoryId( 'Board Games'),
                'purchase_price' => 35.00,
                'selling_price' => 44.99,
                'stock' => 30,
                'SKU' => 'TOY-CAT-BRD-027'
            ],
            [
                'name' => 'LEGO Star Wars Millennium Falcon',
                'description' => 'Ultimate Collector Series Millennium Falcon, 7,500+ pieces, detailed replica, display model.',
                'category_id' => $getCategoryId( 'Building Sets'),
                'purchase_price' => 650.00,
                'selling_price' => 799.99,
                'stock' => 8,
                'SKU' => 'LGO-MIL-FAL-028'
            ],
            
            // Food & Beverages - Coffee & Tea
            [
                'name' => 'Starbucks Pike Place Roast',
                'description' => 'Medium roast coffee beans, smooth and balanced flavor, 1lb bag, whole bean.',
                'category_id' => $getCategoryId( 'Coffee & Tea'),
                'purchase_price' => 12.00,
                'selling_price' => 15.99,
                'stock' => 75,
                'SKU' => 'SBX-PIK-PLC-029'
            ],
            [
                'name' => 'Twinings English Breakfast Tea',
                'description' => 'Classic black tea blend, 100 tea bags, rich and full-bodied flavor.',
                'category_id' => $getCategoryId( 'Coffee & Tea'),
                'purchase_price' => 8.00,
                'selling_price' => 11.99,
                'stock' => 60,
                'SKU' => 'TWN-ENG-BRK-030'
            ],
            
            // Office & Business - Office Supplies
            [
                'name' => 'Apple iPad Pro 12.9"',
                'description' => '12.9-inch Liquid Retina XDR display, M2 chip, 256GB storage, perfect for creative professionals.',
                'category_id' => $getCategoryId( 'Technology'),
                'purchase_price' => 900.00,
                'selling_price' => 1099.00,
                'stock' => 20,
                'SKU' => 'APP-IPD-PRO-031'
            ],
            [
                'name' => 'Canon EOS R6 Mark II',
                'description' => 'Full-frame mirrorless camera, 24.2MP sensor, 4K video, advanced autofocus system.',
                'category_id' => $getCategoryId( 'Cameras & Photography'),
                'purchase_price' => 2200.00,
                'selling_price' => 2499.00,
                'stock' => 12,
                'SKU' => 'CNN-EOS-R6-032'
            ],
            
            // Pet Supplies - Dog Supplies
            [
                'name' => 'Kong Classic Dog Toy',
                'description' => 'Durable rubber dog toy, stuffable design, promotes chewing and mental stimulation.',
                'category_id' => $getCategoryId( 'Dog Supplies'),
                'purchase_price' => 12.00,
                'selling_price' => 16.99,
                'stock' => 40,
                'SKU' => 'KNG-CLS-DOG-033'
            ],
            [
                'name' => 'Royal Canin Dog Food',
                'description' => 'Premium adult dog food, chicken flavor, 30lb bag, balanced nutrition for all breeds.',
                'category_id' => $getCategoryId( 'Dog Supplies'),
                'purchase_price' => 45.00,
                'selling_price' => 59.99,
                'stock' => 35,
                'SKU' => 'RCL-CAN-DOG-034'
            ],
            
            // Baby & Kids - Baby Care
            [
                'name' => 'Philips Avent Baby Bottles',
                'description' => 'Natural baby bottles, BPA-free, anti-colic system, 4-pack, 8oz size.',
                'category_id' => $getCategoryId( 'Baby Care'),
                'purchase_price' => 25.00,
                'selling_price' => 34.99,
                'stock' => 30,
                'SKU' => 'PHL-AVT-BTL-035'
            ],
            [
                'name' => 'Huggies Diapers Size 4',
                'description' => 'Premium diapers, 22-37 lbs, 120 count, hypoallergenic, comfortable fit.',
                'category_id' => $getCategoryId( 'Diapers & Wipes'),
                'purchase_price' => 35.00,
                'selling_price' => 44.99,
                'stock' => 50,
                'SKU' => 'HUG-DIA-SZ4-036'
            ],
            
            // Art & Crafts - Drawing & Painting
            [
                'name' => 'Winsor & Newton Professional Paint Set',
                'description' => '24-tube watercolor paint set, professional quality, vibrant colors, perfect for artists.',
                'category_id' => $getCategoryId( 'Drawing & Painting'),
                'purchase_price' => 85.00,
                'selling_price' => 119.99,
                'stock' => 25,
                'SKU' => 'WNS-NWT-PNT-037'
            ],
            [
                'name' => 'Canson XL Watercolor Paper',
                'description' => '140lb cold-pressed watercolor paper, 9x12 inches, 30 sheets, acid-free.',
                'category_id' => $getCategoryId( 'Drawing & Painting'),
                'purchase_price' => 15.00,
                'selling_price' => 22.99,
                'stock' => 40,
                'SKU' => 'CSN-XL-PPR-038'
            ],
            
            // Musical Instruments - Guitars
            [
                'name' => 'Fender Stratocaster Electric Guitar',
                'description' => 'Classic electric guitar, maple neck, alder body, 3 single-coil pickups, iconic sound.',
                'category_id' => $getCategoryId( 'Guitars'),
                'purchase_price' => 650.00,
                'selling_price' => 799.99,
                'stock' => 15,
                'SKU' => 'FND-STR-ELC-039'
            ],
            [
                'name' => 'Yamaha FG800 Acoustic Guitar',
                'description' => 'Dreadnought acoustic guitar, solid spruce top, mahogany back and sides, great for beginners.',
                'category_id' => $getCategoryId( 'Guitars'),
                'purchase_price' => 180.00,
                'selling_price' => 229.99,
                'stock' => 25,
                'SKU' => 'YAM-FG8-ACO-040'
            ],
            
            // Gaming & Consoles
            [
                'name' => 'PlayStation 5 Console',
                'description' => 'Next-gen gaming console, 4K graphics, ultra-high speed SSD, DualSense controller included.',
                'category_id' => $getCategoryId( 'Gaming & Consoles'),
                'purchase_price' => 450.00,
                'selling_price' => 499.99,
                'stock' => 18,
                'SKU' => 'PS5-CON-SYS-041'
            ],
            [
                'name' => 'Nintendo Switch OLED',
                'description' => '7-inch OLED screen, enhanced audio, 64GB storage, perfect for gaming on the go.',
                'category_id' => $getCategoryId( 'Gaming & Consoles'),
                'purchase_price' => 280.00,
                'selling_price' => 349.99,
                'stock' => 22,
                'SKU' => 'NIN-SWT-OLD-042'
            ],
            
            // Jewelry & Watches
            [
                'name' => 'Cartier Love Bracelet',
                'description' => '18k yellow gold bracelet, iconic screw design, 17cm size, luxury jewelry piece.',
                'category_id' => $getCategoryId( 'Jewelry & Watches'),
                'purchase_price' => 6500.00,
                'selling_price' => 7999.99,
                'stock' => 5,
                'SKU' => 'CRT-LOV-BRC-043'
            ],
            [
                'name' => 'Apple Watch Series 9',
                'description' => 'Latest Apple Watch, 45mm GPS, aluminum case, always-on display, health monitoring.',
                'category_id' => $getCategoryId( 'Jewelry & Watches'),
                'purchase_price' => 350.00,
                'selling_price' => 399.99,
                'stock' => 30,
                'SKU' => 'APP-WCH-S9-044'
            ],
            
            // Garden & Outdoor
            [
                'name' => 'Weber Spirit II Gas Grill',
                'description' => '3-burner gas grill, 529 sq in cooking area, porcelain-enameled grates, perfect for outdoor cooking.',
                'category_id' => $getCategoryId( 'Garden & Outdoor'),
                'purchase_price' => 450.00,
                'selling_price' => 599.99,
                'stock' => 12,
                'SKU' => 'WBR-SPR-GAS-045'
            ],
            [
                'name' => 'Miracle-Gro Potting Mix',
                'description' => 'Premium potting soil, 16qt bag, enriched with plant food, perfect for indoor and outdoor plants.',
                'category_id' => $getCategoryId( 'Garden & Outdoor'),
                'purchase_price' => 12.00,
                'selling_price' => 16.99,
                'stock' => 45,
                'SKU' => 'MIR-GRO-POT-046'
            ],
            
            // Personal Care
            [
                'name' => 'Philips Sonicare Electric Toothbrush',
                'description' => 'Advanced electric toothbrush, sonic technology, pressure sensor, 2-minute timer.',
                'category_id' => $getCategoryId( 'Personal Care'),
                'purchase_price' => 85.00,
                'selling_price' => 119.99,
                'stock' => 35,
                'SKU' => 'PHL-SNC-TBR-047'
            ],
            [
                'name' => 'Dyson Supersonic Hair Dryer',
                'description' => 'Professional hair dryer, intelligent heat control, fast drying, reduces heat damage.',
                'category_id' => $getCategoryId( 'Personal Care'),
                'purchase_price' => 350.00,
                'selling_price' => 429.99,
                'stock' => 20,
                'SKU' => 'DYS-SUP-HDR-048'
            ],
            
            // Snacks & Candy
            [
                'name' => 'Lindt Lindor Truffles',
                'description' => 'Premium chocolate truffles, assorted flavors, 60-count box, smooth and creamy center.',
                'category_id' => $getCategoryId( 'Snacks & Candy'),
                'purchase_price' => 18.00,
                'selling_price' => 24.99,
                'stock' => 55,
                'SKU' => 'LND-LIN-TRF-049'
            ],
            [
                'name' => 'Pringles Original Chips',
                'description' => 'Classic potato chips, original flavor, stackable design, 5.2oz can, perfect snack.',
                'category_id' => $getCategoryId( 'Snacks & Candy'),
                'purchase_price' => 2.50,
                'selling_price' => 3.99,
                'stock' => 100,
                'SKU' => 'PRN-ORG-CHP-050'
            ]
        ];

        // Create products
        foreach ($products as $product) {
            Product::create($product);
        }

        $this->command->info('Products seeded successfully!');
    }
} 