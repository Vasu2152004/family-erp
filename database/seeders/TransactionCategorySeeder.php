<?php

namespace Database\Seeders;

use App\Models\TransactionCategory;
use Illuminate\Database\Seeder;

class TransactionCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // This seeder will be called per family when needed
        // Predefined categories will be created for each family
    }

    /**
     * Seed predefined categories for a family.
     */
    public function seedForFamily(int $tenantId, int $familyId): void
    {
        $categories = [
            // INCOME categories
            ['name' => 'Salary', 'type' => 'INCOME', 'icon' => '💰', 'color' => '#10b981'],
            ['name' => 'Business', 'type' => 'INCOME', 'icon' => '💼', 'color' => '#3b82f6'],
            ['name' => 'Investment', 'type' => 'INCOME', 'icon' => '📈', 'color' => '#8b5cf6'],
            ['name' => 'Gift', 'type' => 'INCOME', 'icon' => '🎁', 'color' => '#f59e0b'],
            ['name' => 'Other', 'type' => 'INCOME', 'icon' => '📊', 'color' => '#6b7280'],

            // EXPENSE categories
            ['name' => 'Food', 'type' => 'EXPENSE', 'icon' => '🍔', 'color' => '#ef4444'],
            ['name' => 'Transport', 'type' => 'EXPENSE', 'icon' => '🚗', 'color' => '#f97316'],
            ['name' => 'Medical', 'type' => 'EXPENSE', 'icon' => '🏥', 'color' => '#ec4899'],
            ['name' => 'Entertainment', 'type' => 'EXPENSE', 'icon' => '🎬', 'color' => '#a855f7'],
            ['name' => 'Shopping', 'type' => 'EXPENSE', 'icon' => '🛒', 'color' => '#6366f1'],
            ['name' => 'Bills', 'type' => 'EXPENSE', 'icon' => '💳', 'color' => '#14b8a6'],
            ['name' => 'Education', 'type' => 'EXPENSE', 'icon' => '📚', 'color' => '#0ea5e9'],
            ['name' => 'Travel', 'type' => 'EXPENSE', 'icon' => '✈️', 'color' => '#06b6d4'],
            ['name' => 'Other', 'type' => 'EXPENSE', 'icon' => '📋', 'color' => '#64748b'],
        ];

        foreach ($categories as $category) {
            TransactionCategory::firstOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'family_id' => $familyId,
                    'name' => $category['name'],
                    'type' => $category['type'],
                ],
                [
                    'tenant_id' => $tenantId,
                    'family_id' => $familyId,
                    'name' => $category['name'],
                    'type' => $category['type'],
                    'is_system' => true,
                    'icon' => $category['icon'],
                    'color' => $category['color'],
                ]
            );
        }
    }
}
