<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class DeliverySettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Set default base delivery fee
        Setting::updateOrCreate(
            ['key' => 'base_delivery_fee'],
            ['value' => '50.00']
        );

        // Set default rate per kilometer
        Setting::updateOrCreate(
            ['key' => 'rate_per_km'],
            ['value' => '10.00']
        );

        // Note: delivery_origin_address and delivery_origin_location
        // should be set by the admin through the Admin Settings page
        // as they are specific to each business location

        $this->command->info('✓ Delivery settings initialized');
        $this->command->info('  - Base Delivery Fee: ₱50.00');
        $this->command->info('  - Rate per Kilometer: ₱10.00');
        $this->command->warn('⚠ Please set your store location in Admin Settings for accurate delivery fee calculation');
    }
}
