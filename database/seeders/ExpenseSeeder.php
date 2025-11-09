<?php

namespace Database\Seeders;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ExpenseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first();

        if (!$user) {
            $this->command->warn('No users found. Please run the user seeder first.');
            return;
        }

        $expenses = [
            [
                'amount' => 5000.00,
                'expense_type' => 'Salary',
                'description' => 'Monthly salary payment for January 2024',
                'date' => '2024-01-15',
                'user_id' => $user->id,
            ],
            [
                'amount' => 2500.00,
                'expense_type' => 'Bills',
                'description' => 'Electricity bill for December 2023',
                'date' => '2024-01-10',
                'user_id' => $user->id,
            ],
            [
                'amount' => 1500.00,
                'expense_type' => 'Transportation',
                'description' => 'Fuel expenses for company vehicles',
                'date' => '2024-01-08',
                'user_id' => $user->id,
            ],
            [
                'amount' => 3000.00,
                'expense_type' => 'Cash Advance',
                'description' => 'Advance payment for business trip',
                'date' => '2024-01-05',
                'user_id' => $user->id,
            ],
            [
                'amount' => 8000.00,
                'expense_type' => 'Tax',
                'description' => 'Quarterly tax payment',
                'date' => '2024-01-01',
                'user_id' => $user->id,
            ],
            [
                'amount' => 1200.00,
                'expense_type' => 'Bills',
                'description' => 'Internet and phone bills',
                'date' => '2023-12-28',
                'user_id' => $user->id,
            ],
            [
                'amount' => 2000.00,
                'expense_type' => 'Transportation',
                'description' => 'Vehicle maintenance and repairs',
                'date' => '2023-12-25',
                'user_id' => $user->id,
            ],
            [
                'amount' => 4500.00,
                'expense_type' => 'Salary',
                'description' => 'Bonus payment for December 2023',
                'date' => '2023-12-20',
                'user_id' => $user->id,
            ],
        ];

        foreach ($expenses as $expense) {
            Expense::create($expense);
        }

        $this->command->info('Expenses seeded successfully.');
    }
}
