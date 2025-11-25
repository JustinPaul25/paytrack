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
        
        // Product data - School Supplies only
        $products = [
            // Writing Supplies
            [
                'name' => 'Ballpoint Pen - Blue (Pack of 10)',
                'description' => 'Smooth writing ballpoint pens, blue ink, retractable design, pack of 10 pens. Perfect for everyday writing and school work.',
                'category_id' => $getCategoryId('School Supply'),
                'purchase_price' => 2.50,
                'selling_price' => 4.99,
                'stock' => 150,
                'unit' => 'pack',
                'SKU' => 'SCH-BLP-PEN-001'
            ],
            [
                'name' => 'Ballpoint Pen - Black (Pack of 10)',
                'description' => 'Smooth writing ballpoint pens, black ink, retractable design, pack of 10 pens. Essential for school and office use.',
                'category_id' => $getCategoryId('School Supply'),
                'purchase_price' => 2.50,
                'selling_price' => 4.99,
                'stock' => 150,
                'unit' => 'pack',
                'SKU' => 'SCH-BLP-BLK-002'
            ],
            [
                'name' => 'Red Pen (Pack of 5)',
                'description' => 'Fine point red pens for corrections and grading, pack of 5 pens.',
                'category_id' => $getCategoryId('School Supply'),
                'purchase_price' => 1.50,
                'selling_price' => 2.99,
                'stock' => 120,
                'unit' => 'pack',
                'SKU' => 'SCH-RED-PEN-003'
            ],
            [
                'name' => 'Pencil HB #2 (Pack of 12)',
                'description' => 'Standard HB #2 pencils, pre-sharpened, pack of 12. Ideal for tests and everyday writing.',
                'category_id' => $getCategoryId('School Supply'),
                'purchase_price' => 1.20,
                'selling_price' => 2.49,
                'stock' => 200,
                'unit' => 'pack',
                'SKU' => 'SCH-PCL-HB-004'
            ],
            [
                'name' => 'Mechanical Pencil 0.7mm',
                'description' => 'Durable mechanical pencil with 0.7mm lead, includes eraser and extra leads. Perfect for precise writing.',
                'category_id' => $getCategoryId('School Supply'),
                'purchase_price' => 1.80,
                'selling_price' => 3.99,
                'stock' => 100,
                'unit' => 'pcs',
                'SKU' => 'SCH-MCH-PCL-005'
            ],
            
            // Paper & Notebooks
            [
                'name' => 'Spiral Notebook - Wide Ruled (70 sheets)',
                'description' => 'Single subject spiral notebook, wide ruled, 70 sheets, 8.5 x 11 inches. Durable cover.',
                'category_id' => $getCategoryId('School Supply'),
                'purchase_price' => 1.50,
                'selling_price' => 2.99,
                'stock' => 180,
                'unit' => 'pcs',
                'SKU' => 'SCH-SPR-WR-006'
            ],
            [
                'name' => 'Spiral Notebook - College Ruled (70 sheets)',
                'description' => 'Single subject spiral notebook, college ruled, 70 sheets, 8.5 x 11 inches. Perfect for detailed notes.',
                'category_id' => $getCategoryId('School Supply'),
                'purchase_price' => 1.50,
                'selling_price' => 2.99,
                'stock' => 180,
                'unit' => 'pcs',
                'SKU' => 'SCH-SPR-CR-007'
            ],
            [
                'name' => 'Composition Notebook (100 sheets)',
                'description' => 'Marbled composition notebook, 100 sheets, sewn binding, durable cover. Classic school notebook.',
                'category_id' => $getCategoryId('School Supply'),
                'purchase_price' => 2.00,
                'selling_price' => 3.99,
                'stock' => 150,
                'unit' => 'pcs',
                'SKU' => 'SCH-COMP-NB-008'
            ],
            [
                'name' => 'Lined Paper (Pack of 100 sheets)',
                'description' => 'Wide ruled lined paper, 100 sheets per pack, 8.5 x 11 inches. Loose leaf paper for binders.',
                'category_id' => $getCategoryId('School Supply'),
                'purchase_price' => 1.80,
                'selling_price' => 3.49,
                'stock' => 200,
                'unit' => 'pack',
                'SKU' => 'SCH-LIN-PPR-009'
            ],
            [
                'name' => 'Graph Paper (Pack of 50 sheets)',
                'description' => '1/4 inch grid graph paper, 50 sheets per pack, perfect for math and science classes.',
                'category_id' => $getCategoryId('School Supply'),
                'purchase_price' => 1.50,
                'selling_price' => 2.99,
                'stock' => 120,
                'unit' => 'pack',
                'SKU' => 'SCH-GRF-PPR-010'
            ],
            
            // Folders & Organizers
            [
                'name' => 'Pocket Folder with Prongs (2-pocket)',
                'description' => 'Two-pocket folder with three-hole prongs, assorted colors. Keep papers organized.',
                'category_id' => $getCategoryId('School Supply'),
                'purchase_price' => 0.80,
                'selling_price' => 1.99,
                'stock' => 250,
                'unit' => 'pcs',
                'SKU' => 'SCH-FLD-2PK-011'
            ],
            [
                'name' => '3-Ring Binder (1 inch)',
                'description' => 'Durable 3-ring binder, 1 inch capacity, clear view cover with pockets. Available in various colors.',
                'category_id' => $getCategoryId('School Supply'),
                'purchase_price' => 2.50,
                'selling_price' => 4.99,
                'stock' => 150,
                'unit' => 'pcs',
                'SKU' => 'SCH-BND-1IN-012'
            ],
            [
                'name' => '3-Ring Binder (2 inch)',
                'description' => 'Heavy-duty 3-ring binder, 2 inch capacity, D-ring design. Perfect for multiple subjects.',
                'category_id' => $getCategoryId('School Supply'),
                'purchase_price' => 3.50,
                'selling_price' => 6.99,
                'stock' => 100,
                'unit' => 'pcs',
                'SKU' => 'SCH-BND-2IN-013'
            ],
            
            // Erasers & Correction Supplies
            [
                'name' => 'Pink Eraser (Pack of 4)',
                'description' => 'Soft pink erasers, pack of 4. Clean erasing without smudging.',
                'category_id' => $getCategoryId('School Supply'),
                'purchase_price' => 0.80,
                'selling_price' => 1.49,
                'stock' => 200,
                'unit' => 'pack',
                'SKU' => 'SCH-ERS-PNK-014'
            ],
            [
                'name' => 'Correction Tape',
                'description' => 'White correction tape, dry and non-smearing, easy to use. Perfect for neat corrections.',
                'category_id' => $getCategoryId('School Supply'),
                'purchase_price' => 1.20,
                'selling_price' => 2.49,
                'stock' => 120,
                'unit' => 'pcs',
                'SKU' => 'SCH-COR-TAP-015'
            ],
            
            // Rulers & Measuring Tools
            [
                'name' => '12-Inch Ruler (Wooden)',
                'description' => 'Standard 12-inch wooden ruler with both inch and centimeter markings. Durable and accurate.',
                'category_id' => $getCategoryId('School Supply'),
                'purchase_price' => 0.60,
                'selling_price' => 1.49,
                'stock' => 180,
                'unit' => 'pcs',
                'SKU' => 'SCH-RUL-12-016'
            ],
            [
                'name' => '6-Inch Ruler (Plastic)',
                'description' => 'Compact 6-inch plastic ruler, clear design, inch and metric measurements. Perfect for pencil cases.',
                'category_id' => $getCategoryId('School Supply'),
                'purchase_price' => 0.40,
                'selling_price' => 0.99,
                'stock' => 200,
                'unit' => 'pcs',
                'SKU' => 'SCH-RUL-6-017'
            ],
            [
                'name' => 'Protractor (180 degrees)',
                'description' => 'Semi-circle protractor, 180 degrees, clear plastic, degree markings. Essential for geometry class.',
                'category_id' => $getCategoryId('School Supply'),
                'purchase_price' => 0.80,
                'selling_price' => 1.99,
                'stock' => 150,
                'unit' => 'pcs',
                'SKU' => 'SCH-PRT-180-018'
            ],
            
            // Highlighters & Markers
            [
                'name' => 'Highlighter Set (4 colors)',
                'description' => 'Chisel-tip highlighters, 4 assorted colors (yellow, pink, green, blue). Bright, non-bleeding ink.',
                'category_id' => $getCategoryId('School Supply'),
                'purchase_price' => 2.00,
                'selling_price' => 3.99,
                'stock' => 150,
                'unit' => 'set',
                'SKU' => 'SCH-HLT-4CL-019'
            ],
            [
                'name' => 'Yellow Highlighter (Pack of 3)',
                'description' => 'Classic yellow highlighters, chisel tip, pack of 3. Perfect for marking important text.',
                'category_id' => $getCategoryId('School Supply'),
                'purchase_price' => 1.50,
                'selling_price' => 2.99,
                'stock' => 180,
                'unit' => 'pack',
                'SKU' => 'SCH-HLT-YEL-020'
            ],
            [
                'name' => 'Washable Markers (8 colors)',
                'description' => 'Broad tip washable markers, 8 vibrant colors, safe and non-toxic. Great for school projects.',
                'category_id' => $getCategoryId('School Supply'),
                'purchase_price' => 3.00,
                'selling_price' => 5.99,
                'stock' => 120,
                'unit' => 'set',
                'SKU' => 'SCH-MRK-8CL-021'
            ],
            
            // Scissors & Cutting Tools
            [
                'name' => 'School Scissors (5 inch)',
                'description' => 'Safety scissors with rounded tips, 5 inch blades, comfortable handles. Safe for children.',
                'category_id' => $getCategoryId('School Supply'),
                'purchase_price' => 1.50,
                'selling_price' => 2.99,
                'stock' => 150,
                'unit' => 'pcs',
                'SKU' => 'SCH-SCS-5IN-022'
            ],
            [
                'name' => 'Colored Pencils (12 pack)',
                'description' => 'Vibrant colored pencils, 12 assorted colors, smooth coloring. Perfect for art projects and assignments.',
                'category_id' => $getCategoryId('School Supply'),
                'purchase_price' => 2.50,
                'selling_price' => 4.99,
                'stock' => 130,
                'unit' => 'set',
                'SKU' => 'SCH-COL-PCL-023'
            ],
            [
                'name' => 'Crayons (24 pack)',
                'description' => 'Classic crayons, 24 colors, non-toxic and washable. Essential for elementary school.',
                'category_id' => $getCategoryId('School Supply'),
                'purchase_price' => 2.00,
                'selling_price' => 3.99,
                'stock' => 140,
                'unit' => 'box',
                'SKU' => 'SCH-CRY-24-024'
            ],
            
            // Glue & Adhesives
            [
                'name' => 'Elmer\'s White School Glue (4oz)',
                'description' => 'Washable white school glue, 4oz bottle, safe and non-toxic. Perfect for art and crafts.',
                'category_id' => $getCategoryId('School Supply'),
                'purchase_price' => 0.80,
                'selling_price' => 1.99,
                'stock' => 200,
                'unit' => 'pcs',
                'SKU' => 'SCH-GLU-4OZ-025'
            ],
            [
                'name' => 'Glue Sticks (4 pack)',
                'description' => 'Washable glue sticks, 4 pack, smooth application, no mess. Convenient for school projects.',
                'category_id' => $getCategoryId('School Supply'),
                'purchase_price' => 1.20,
                'selling_price' => 2.49,
                'stock' => 180,
                'unit' => 'pack',
                'SKU' => 'SCH-GLS-STK-026'
            ],
            
            // Index Cards & Sticky Notes
            [
                'name' => 'Index Cards (3x5 inches, Pack of 100)',
                'description' => 'Lined index cards, 3x5 inches, 100 cards per pack. Perfect for flashcards and study notes.',
                'category_id' => $getCategoryId('School Supply'),
                'purchase_price' => 1.00,
                'selling_price' => 1.99,
                'stock' => 200,
                'unit' => 'pack',
                'SKU' => 'SCH-IDX-3X5-027'
            ],
            [
                'name' => 'Sticky Notes (3x3 inches, Pack of 5)',
                'description' => 'Yellow sticky notes, 3x3 inches, 5 pads per pack. Great for reminders and bookmarks.',
                'category_id' => $getCategoryId('School Supply'),
                'purchase_price' => 1.50,
                'selling_price' => 2.99,
                'stock' => 150,
                'unit' => 'pack',
                'SKU' => 'SCH-STK-NTS-028'
            ],
            
            // Backpacks & Bags
            [
                'name' => 'Student Backpack (Medium)',
                'description' => 'Durable student backpack with padded straps, multiple compartments, water-resistant material. Various colors available.',
                'category_id' => $getCategoryId('School Supply'),
                'purchase_price' => 15.00,
                'selling_price' => 24.99,
                'stock' => 80,
                'unit' => 'pcs',
                'SKU' => 'SCH-BPK-MED-029'
            ],
            [
                'name' => 'Pencil Case (Zipper)',
                'description' => 'Durable zipper pencil case with multiple compartments, fits pens, pencils, and small supplies.',
                'category_id' => $getCategoryId('School Supply'),
                'purchase_price' => 2.00,
                'selling_price' => 3.99,
                'stock' => 180,
                'unit' => 'pcs',
                'SKU' => 'SCH-PCL-CSE-030'
            ],
            
            // Calculators
            [
                'name' => 'Basic Calculator',
                'description' => '8-digit display calculator, basic math functions, solar powered with battery backup.',
                'category_id' => $getCategoryId('School Supply'),
                'purchase_price' => 3.00,
                'selling_price' => 5.99,
                'stock' => 120,
                'unit' => 'pcs',
                'SKU' => 'SCH-CAL-BAS-031'
            ],
            [
                'name' => 'Scientific Calculator',
                'description' => 'Advanced scientific calculator with 300+ functions, LCD display, perfect for high school math and science.',
                'category_id' => $getCategoryId('School Supply'),
                'purchase_price' => 12.00,
                'selling_price' => 19.99,
                'stock' => 60,
                'unit' => 'pcs',
                'SKU' => 'SCH-CAL-SCI-032'
            ],
            
            // Art Supplies
            [
                'name' => 'Watercolor Paint Set (12 colors)',
                'description' => 'Non-toxic watercolor paint set, 12 vibrant colors, includes brush. Perfect for art class projects.',
                'category_id' => $getCategoryId('School Supply'),
                'purchase_price' => 4.50,
                'selling_price' => 7.99,
                'stock' => 100,
                'unit' => 'set',
                'SKU' => 'SCH-WTR-12-033'
            ],
            [
                'name' => 'Paint Brushes Set (5 brushes)',
                'description' => 'Assorted paint brushes, 5 different sizes, synthetic bristles. Versatile for various painting techniques.',
                'category_id' => $getCategoryId('School Supply'),
                'purchase_price' => 2.50,
                'selling_price' => 4.99,
                'stock' => 110,
                'unit' => 'set',
                'SKU' => 'SCH-BRS-5-034'
            ],
            
            // Additional Supplies
            [
                'name' => 'Stapler',
                'description' => 'Compact desktop stapler, holds up to 50 staples, durable metal construction.',
                'category_id' => $getCategoryId('School Supply'),
                'purchase_price' => 3.50,
                'selling_price' => 5.99,
                'stock' => 100,
                'unit' => 'pcs',
                'SKU' => 'SCH-STP-001-035'
            ],
            [
                'name' => 'Staples (1000 count)',
                'description' => 'Standard staples for staplers, 1000 count box, size 26/6.',
                'category_id' => $getCategoryId('School Supply'),
                'purchase_price' => 1.00,
                'selling_price' => 1.99,
                'stock' => 200,
                'unit' => 'box',
                'SKU' => 'SCH-STP-1000-036'
            ],
            [
                'name' => 'Paper Clips (100 count)',
                'description' => 'Jumbo paper clips, 100 count box, assorted colors. Great for organizing papers.',
                'category_id' => $getCategoryId('School Supply'),
                'purchase_price' => 0.80,
                'selling_price' => 1.49,
                'stock' => 250,
                'unit' => 'box',
                'SKU' => 'SCH-PPR-CLP-037'
            ],
            [
                'name' => 'Binder Dividers (5 tabs)',
                'description' => 'Set of 5 binder dividers with tabs, clear pockets, numbered tabs. Keep subjects organized.',
                'category_id' => $getCategoryId('School Supply'),
                'purchase_price' => 1.50,
                'selling_price' => 2.99,
                'stock' => 150,
                'unit' => 'set',
                'SKU' => 'SCH-DIV-5-038'
            ],
            [
                'name' => 'Pencil Sharpener (Manual)',
                'description' => 'Compact manual pencil sharpener with cover, dual hole design for standard and jumbo pencils.',
                'category_id' => $getCategoryId('School Supply'),
                'purchase_price' => 0.60,
                'selling_price' => 1.49,
                'stock' => 180,
                'unit' => 'pcs',
                'SKU' => 'SCH-SHR-MAN-039'
            ],
            [
                'name' => 'Pencil Sharpener (Electric)',
                'description' => 'Electric pencil sharpener, auto-stop feature, large shavings receptacle. Fast and efficient.',
                'category_id' => $getCategoryId('School Supply'),
                'purchase_price' => 8.00,
                'selling_price' => 12.99,
                'stock' => 50,
                'unit' => 'pcs',
                'SKU' => 'SCH-SHR-ELC-040'
            ]
        ];

        // Create products
        foreach ($products as $product) {
            Product::create($product);
        }

        $this->command->info('Products seeded successfully!');
    }
} 