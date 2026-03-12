<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * For deployment: only roles and admin/staff users are seeded.
     * Dummy data seeders (categories, products, customers, invoices, deliveries) are not run.
     */
    public function run(): void
    {
        $this->call([
            RolesAndUsersSeeder::class,
        ]);
    }
}
