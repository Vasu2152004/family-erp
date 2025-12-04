<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create a default tenant
        $tenant = Tenant::firstOrCreate(
            ['name' => 'Default Family'],
            ['name' => 'Default Family']
        );

        // Create a test user with known credentials
        $user = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => Hash::make('password'), // Password: password
                'tenant_id' => $tenant->id,
            ]
        );

        // Update existing user if tenant_id is missing
        if ($user->tenant_id === null) {
            $user->update(['tenant_id' => $tenant->id]);
        }

        $this->command->info('Test user created:');
        $this->command->info('Email: test@example.com');
        $this->command->info('Password: password');
    }
}
