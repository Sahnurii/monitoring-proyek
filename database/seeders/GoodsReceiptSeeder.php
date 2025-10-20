<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GoodsReceiptSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('goods_receipts')->insert([
            [
                'code' => 'GR-001',
                'purchase_order_id' => 1, // PO-001
                'project_id' => 1,
                'supplier_id' => 1,
                'received_date' => '2025-02-22',
                'status' => 'completed',
                'received_by' => 2, // user lapangan
                'verified_by' => 1, // admin/manajer proyek
                'verified_at' => '2025-02-23 09:00:00',
                'remarks' => 'Barang diterima lengkap dan sesuai dengan PO-001.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'GR-002',
                'purchase_order_id' => 2, // PO-002 (draft)
                'project_id' => 2,
                'supplier_id' => 2,
                'received_date' => '2025-03-20',
                'status' => 'draft',
                'received_by' => 3,
                'verified_by' => null,
                'verified_at' => null,
                'remarks' => 'Masih menunggu persetujuan sebelum penerimaan.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'GR-003',
                'purchase_order_id' => 4, // PO-004
                'project_id' => 4,
                'supplier_id' => 4,
                'received_date' => '2025-04-15',
                'status' => 'completed',
                'received_by' => 2,
                'verified_by' => 1,
                'verified_at' => '2025-04-16 11:30:00',
                'remarks' => 'Cat tembok dan gypsum diterima dalam kondisi baik.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'GR-004',
                'purchase_order_id' => 5, // PO-005
                'project_id' => 5,
                'supplier_id' => 5,
                'received_date' => '2025-05-20',
                'status' => 'in_progress',
                'received_by' => 4,
                'verified_by' => null,
                'verified_at' => null,
                'remarks' => 'Sebagian barang sudah diterima, sebagian masih dalam pengiriman.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'GR-005',
                'purchase_order_id' => 3, // PO-003 (canceled)
                'project_id' => 3,
                'supplier_id' => 3,
                'received_date' => '2025-03-28',
                'status' => 'returned',
                'received_by' => 2,
                'verified_by' => 1,
                'verified_at' => '2025-03-29 14:00:00',
                'remarks' => 'Barang dikembalikan karena kerusakan saat pengiriman.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
