<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            UserSeeder::class,
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
