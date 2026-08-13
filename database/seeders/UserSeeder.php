<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Seed the default accounts: an admin (J4U) and a doctor.
     *
     * Idempotent — re-running `db:seed` updates instead of duplicating.
     * Login: admin@gmail.com / doctor@gmail.com, password: password
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Admin',
                'email' => 'admin@gmail.com',
                'password' => 'password',
                'role' => UserRole::J4U,
            ],
            [
                'name' => 'Doctor',
                'email' => 'doctor@gmail.com',
                'password' => 'password',
                'role' => UserRole::Doctor,
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'password' => $user['password'],
                    'role' => $user['role'],
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}
