<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Seed Default Administrative User
        User::updateOrCreate(
            ['email' => 'admin@nbrb.go.ug'],
            [
                'name' => 'NBRB System Administrator',
                'password' => Hash::make('Admin@NBRB2026'),
                'bims_id' => 'NBRB-ADM-001',
                'role' => 'admin',
            ]
        );

        // 2. Seed Default Standard Professional User
        User::updateOrCreate(
            ['email' => 'engineer@bims.ug'],
            [
                'name' => 'Eng. Raymond Kigozi',
                'password' => Hash::make('Engineer@2026'),
                'bims_id' => 'BIMS-PE-9021',
                'role' => 'user',
            ]
        );
    }
}
