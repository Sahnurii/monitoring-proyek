<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use GuzzleHttp\Psr7\PumpStream;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
        ]);

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

        $this->call([
            UnitSeeder::class,
            MaterialSeeder::class,
            SupplierSeeder::class,
            ProjectSeeder::class,
            MaterialRequestSeeder::class,
            MaterialRequestItemSeeder::class,
            PurchaseOrderSeeder::class,
            PurchaseOrderItemSeeder::class,
            GoodsReceiptSeeder::class,
            GoodsReceiptItemSeeder::class,
        ]);
    }
}
