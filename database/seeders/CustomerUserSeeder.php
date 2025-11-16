<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Seeder;

class CustomerUserSeeder extends Seeder
{
    public function run(): void
    {
        $customers = Customer::all();
        if ($customers->isEmpty()) {
            $this->command->warn('Skipping customer user seeding: no customers.');
            return;
        }

        foreach ($customers as $customer) {
            // Use customer's email; if missing, synthesize one
            $email = $customer->email ?: strtolower(str_replace(' ', '.', $customer->name)).'@example.com';

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $customer->name,
                    'password' => 'password',
                ]
            );

            // Ensure role
            $user->syncRoles(['Customer']);
        }

        $this->command->info('Customer accounts created/ensured for all customers.');
    }
}
