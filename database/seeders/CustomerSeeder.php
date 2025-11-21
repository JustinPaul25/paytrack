<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\User;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customers = [
            [
                'name' => 'Maria Santos Cruz',
                'company_name' => 'Cruz Trading Company',
                'email' => 'maria.cruz@example.com',
                'phone' => '+63-912-345-6789',
                'address' => 'Prk. Atis Brgy. Cagangoha, Panabo City, Davao del Norte',
                'location' => json_encode(['lat' => 7.1907, 'lng' => 125.6833]), // Panabo City
            ],
            [
                'name' => 'Juan Dela Cruz',
                'company_name' => 'Dela Cruz Enterprises',
                'email' => 'juan.delacruz@example.com',
                'phone' => '+63-923-456-7890',
                'address' => 'Purok 5, Barangay 76-A, Davao City, Davao del Sur',
                'location' => json_encode(['lat' => 7.1907, 'lng' => 125.4553]), // Davao City
            ],
            [
                'name' => 'Ana Reyes Mendoza',
                'email' => 'ana.mendoza@example.com',
                'phone' => '+63-934-567-8901',
                'address' => 'Sitio San Roque, Barangay Matina, Davao City',
                'location' => json_encode(['lat' => 7.0731, 'lng' => 125.6144]), // Matina, Davao
            ],
            [
                'name' => 'Pedro Villanueva',
                'company_name' => 'Villanueva Construction',
                'email' => 'pedro.villanueva@example.com',
                'phone' => '+63-945-678-9012',
                'address' => 'Purok 3, Barangay San Antonio, General Santos City',
                'location' => json_encode(['lat' => 6.1128, 'lng' => 125.1716]), // General Santos City
            ],
            [
                'name' => 'Carmen Rodriguez',
                'email' => 'carmen.rodriguez@example.com',
                'phone' => '+63-956-789-0123',
                'address' => 'Barangay Poblacion, Koronadal City, South Cotabato',
                'location' => json_encode(['lat' => 6.5007, 'lng' => 124.8489]), // Koronadal City
            ],
            [
                'name' => 'Roberto Aquino',
                'company_name' => 'Aquino Farm Supply',
                'email' => 'roberto.aquino@example.com',
                'phone' => '+63-967-890-1234',
                'address' => 'Purok 2, Barangay San Jose, Kidapawan City',
                'location' => json_encode(['lat' => 7.0083, 'lng' => 125.0894]), // Kidapawan City
            ],
            [
                'name' => 'Luzviminda Santos',
                'email' => 'luz.santos@example.com',
                'phone' => '+63-978-901-2345',
                'address' => 'Barangay Poblacion, Tagum City, Davao del Norte',
                'location' => json_encode(['lat' => 7.4475, 'lng' => 125.8096]), // Tagum City
            ],
            [
                'name' => 'Antonio Reyes',
                'company_name' => 'Reyes Hardware Store',
                'email' => 'antonio.reyes@example.com',
                'phone' => '+63-989-012-3456',
                'address' => 'Purok 4, Barangay San Miguel, Digos City',
                'location' => json_encode(['lat' => 6.7497, 'lng' => 125.3570]), // Digos City
            ],
            [
                'name' => 'Elena Martinez',
                'email' => 'elena.martinez@example.com',
                'phone' => '+63-990-123-4567',
                'address' => 'Barangay Poblacion, Cotabato City, Maguindanao',
                'location' => json_encode(['lat' => 7.2041, 'lng' => 124.2154]), // Cotabato City
            ],
            [
                'name' => 'Fernando Torres',
                'company_name' => 'Torres Auto Parts',
                'email' => 'fernando.torres@example.com',
                'phone' => '+63-901-234-5678',
                'address' => 'Purok 1, Barangay San Isidro, Cagayan de Oro City',
                'location' => json_encode(['lat' => 8.4542, 'lng' => 124.6319]), // Cagayan de Oro City
            ],
        ];

        // Get products and a user for invoice creation
        $products = Product::all();
        $user = User::first();

        if ($products->isEmpty()) {
            $this->command->warn('No products found. Invoices will not be created. Please run ProductSeeder first.');
        }

        if (!$user) {
            $this->command->warn('No user found. Invoices will not be created. Please run RolesAndUsersSeeder first.');
        }

        foreach ($customers as $customerData) {
            $customer = Customer::create($customerData);

            // Create invoices for this customer only if products and user exist
            if (!$products->isEmpty() && $user) {
                // Create 2-3 invoices per customer with different statuses
                $statuses = ['completed', 'pending', 'draft'];
                $paymentMethods = ['cash', 'bank_transfer', 'e-wallet'];
                
                // Randomly select 2-3 invoices per customer
                $invoiceCount = rand(2, 3);
                
                for ($i = 0; $i < $invoiceCount; $i++) {
                    // Select 1-4 random products for this invoice
                    $selectedProducts = $products->random(min(4, max(1, rand(1, $products->count()))));
                    $subtotalAmount = 0;
                    $items = [];

                    foreach ($selectedProducts as $product) {
                        $quantity = rand(1, 5);
                        $price = $product->selling_price; // Returns dollars (accessor converts from cents)
                        $total = $quantity * $price;
                        $subtotalAmount += $total;
                        $items[] = [
                            'product' => $product,
                            'quantity' => $quantity,
                            'price' => $price,
                            'total' => $total
                        ];
                    }

                    // Calculate VAT (12%)
                    $vatRate = 12.00;
                    $vatAmount = $subtotalAmount * ($vatRate / 100);
                    $totalAmount = $subtotalAmount + $vatAmount;

                    // Create invoice (mutators will convert dollar values to cents)
                    $invoice = Invoice::create([
                        'customer_id' => $customer->id,
                        'user_id' => $user->id,
                        'status' => $statuses[$i % count($statuses)],
                        'payment_method' => $paymentMethods[$i % count($paymentMethods)],
                        'payment_reference' => 'CUST-SEED-' . strtoupper(substr($customer->name, 0, 3)) . '-' . uniqid(),
                        'notes' => 'Seeded invoice for ' . $customer->name,
                        'subtotal_amount' => $subtotalAmount,
                        'vat_amount' => $vatAmount,
                        'vat_rate' => $vatRate,
                        'total_amount' => $totalAmount,
                    ]);

                    // Create invoice items (mutators will convert dollar values to cents)
                    foreach ($items as $item) {
                        InvoiceItem::create([
                            'invoice_id' => $invoice->id,
                            'product_id' => $item['product']->id,
                            'quantity' => $item['quantity'],
                            'price' => $item['price'],
                            'total' => $item['total'],
                        ]);
                    }
                }
            }
        }

        $this->command->info('Customers and their invoices seeded successfully!');
    }
} 