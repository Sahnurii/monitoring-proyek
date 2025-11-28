<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = Role::pluck('id', 'role_name');

        $users = [
            [
                'name' => 'Admin Monitoring',
                'email' => 'admin@example.com',
                'phone' => '081234567890',
                'role_name' => 'admin',
            ],
            [
                'name' => 'Manager Proyek',
                'email' => 'manager@example.com',
                'phone' => '081234567891',
                'role_name' => 'manager',
            ],
            [
                'name' => 'Operator Lapangan',
                'email' => 'operator@example.com',
                'phone' => '081234567892',
                'role_name' => 'operator',
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'phone' => $user['phone'],
                    'password' => Hash::make('password'),
                    'role_id' => $roles[$user['role_name']] ?? null,
                ]
            );
        }
    }
}
