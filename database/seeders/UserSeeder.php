<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Seed the login admin (J4U) and a few non-login doctors.
     *
     * Idempotent — re-running `db:seed` updates instead of duplicating.
     * Admin login: admin@gmail.com / password. Doctors do not log in; they are
     * reached through their public link (see the Doctors admin page).
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Admin',
                'password' => 'password',
                'role' => UserRole::J4U,
                'email_verified_at' => now(),
            ]
        );

        $doctors = [
            ['name' => 'Dr. Budi Santoso', 'phone' => '+62 812 1000 2000', 'public_token' => 'demo-doctor-budi'],
            ['name' => 'Dr. Sari Lestari', 'phone' => '+62 813 3000 4000', 'public_token' => 'demo-doctor-sari'],
        ];

        foreach ($doctors as $doctor) {
            User::updateOrCreate(
                ['public_token' => $doctor['public_token']],
                [
                    'name' => $doctor['name'],
                    'phone' => $doctor['phone'],
                    'role' => UserRole::Doctor,
                    'email' => null,
                    'password' => null,
                ]
            );
        }
    }
}
