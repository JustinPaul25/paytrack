<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Branch;

class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $branches = [
            [
                'name' => 'Main Branch',
                'code' => 'BR001',
                'address' => '123 Main Street, Metro Manila, Philippines',
                'phone' => '+63 2 1234 5678',
                'email' => 'main@paytrack.com',
                'description' => 'Our flagship branch located in the heart of Metro Manila',
                'status' => 'active',
                'manager_name' => 'Maria Santos',
                'manager_phone' => '+63 917 123 4567',
                'manager_email' => 'maria.santos@paytrack.com',
                'opening_time' => '08:00',
                'closing_time' => '18:00',
                'location' => ['lat' => 14.5995, 'lng' => 120.9842], // Manila coordinates
            ],
            [
                'name' => 'Cebu Branch',
                'code' => 'BR002',
                'address' => '456 Cebu Avenue, Cebu City, Philippines',
                'phone' => '+63 32 2345 6789',
                'email' => 'cebu@paytrack.com',
                'description' => 'Serving the Visayas region with excellent customer service',
                'status' => 'active',
                'manager_name' => 'Juan Dela Cruz',
                'manager_phone' => '+63 918 234 5678',
                'manager_email' => 'juan.delacruz@paytrack.com',
                'opening_time' => '09:00',
                'closing_time' => '17:00',
                'location' => ['lat' => 10.3157, 'lng' => 123.8854], // Cebu coordinates
            ],
            [
                'name' => 'Davao Branch',
                'code' => 'BR003',
                'address' => '789 Davao Road, Davao City, Philippines',
                'phone' => '+63 82 3456 7890',
                'email' => 'davao@paytrack.com',
                'description' => 'Expanding our services to Mindanao region',
                'status' => 'active',
                'manager_name' => 'Ana Rodriguez',
                'manager_phone' => '+63 919 345 6789',
                'manager_email' => 'ana.rodriguez@paytrack.com',
                'opening_time' => '08:30',
                'closing_time' => '17:30',
                'location' => ['lat' => 7.1907, 'lng' => 125.4553], // Davao coordinates
            ],
            [
                'name' => 'Baguio Branch',
                'code' => 'BR004',
                'address' => '321 Baguio Street, Baguio City, Philippines',
                'phone' => '+63 74 4567 8901',
                'email' => 'baguio@paytrack.com',
                'description' => 'Mountain city branch serving the Cordillera region',
                'status' => 'maintenance',
                'manager_name' => 'Pedro Martinez',
                'manager_phone' => '+63 920 456 7890',
                'manager_email' => 'pedro.martinez@paytrack.com',
                'opening_time' => '09:00',
                'closing_time' => '16:00',
                'location' => ['lat' => 16.4023, 'lng' => 120.5960], // Baguio coordinates
            ],
            [
                'name' => 'Iloilo Branch',
                'code' => 'BR005',
                'address' => '654 Iloilo Boulevard, Iloilo City, Philippines',
                'phone' => '+63 33 5678 9012',
                'email' => 'iloilo@paytrack.com',
                'description' => 'Western Visayas regional branch',
                'status' => 'inactive',
                'manager_name' => 'Carmen Garcia',
                'manager_phone' => '+63 921 567 8901',
                'manager_email' => 'carmen.garcia@paytrack.com',
                'opening_time' => '08:00',
                'closing_time' => '18:00',
                'location' => ['lat' => 10.7203, 'lng' => 122.5621], // Iloilo coordinates
            ],
        ];

        foreach ($branches as $branch) {
            Branch::create($branch);
        }
    }
} 