<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GoodsReceiptItemSeeder extends Seeder
{
    public function run(): void
    {
        $goodsReceiptIds = DB::table('goods_receipts')->pluck('id', 'code');
        $purchaseOrderIds = DB::table('purchase_orders')->pluck('id', 'code');
        $materialIds = DB::table('materials')->pluck('id', 'sku');

        $items = [
            [
                'receipt_code' => 'GR-001',
                'order_code' => 'PO-001',
                'material_sku' => 'MAT-001',
                'qty' => 200,
                'returned_qty' => 0,
                'remarks' => 'Diterima lengkap dan sesuai.',
            ],
            [
                'receipt_code' => 'GR-001',
                'order_code' => 'PO-001',
                'material_sku' => 'MAT-002',
                'qty' => 1000,
                'returned_qty' => 0,
                'remarks' => 'Diterima sesuai permintaan.',
            ],
            [
                'receipt_code' => 'GR-003',
                'order_code' => 'PO-004',
                'material_sku' => 'MAT-006',
                'qty' => 50,
                'returned_qty' => 0,
                'remarks' => 'Kondisi baik.',
            ],
            [
                'receipt_code' => 'GR-003',
                'order_code' => 'PO-004',
                'material_sku' => 'MAT-009',
                'qty' => 40,
                'returned_qty' => 0,
                'remarks' => 'Kondisi baik, tidak ada kerusakan.',
            ],
            [
                'receipt_code' => 'GR-004',
                'order_code' => 'PO-005',
                'material_sku' => 'MAT-003',
                'qty' => 70,
                'returned_qty' => 0,
                'remarks' => 'Sebagian diterima, 30 batang masih di perjalanan.',
            ],
            [
                'receipt_code' => 'GR-005',
                'order_code' => 'PO-003',
                'material_sku' => 'MAT-005',
                'qty' => 80,
                'returned_qty' => 80,
                'remarks' => 'Seluruh barang dikembalikan karena pecah saat diterima.',
            ],
        ];

        $timestamp = now();

        foreach ($items as $item) {
            $receiptId = $goodsReceiptIds[$item['receipt_code']] ?? null;
            $purchaseOrderId = $purchaseOrderIds[$item['order_code']] ?? null;
            $materialId = $materialIds[$item['material_sku']] ?? null;

            if (! $receiptId || ! $purchaseOrderId || ! $materialId) {
                continue;
            }

            $purchaseOrderItemId = DB::table('purchase_order_items')
                ->where('purchase_order_id', $purchaseOrderId)
                ->where('material_id', $materialId)
                ->value('id');

            if (! $purchaseOrderItemId) {
                continue;
            }

            DB::table('goods_receipt_items')->updateOrInsert(
                [
                    'goods_receipt_id' => $receiptId,
                    'purchase_order_item_id' => $purchaseOrderItemId,
                    'material_id' => $materialId,
                ],
                [
                    'qty' => $item['qty'],
                    'returned_qty' => $item['returned_qty'],
                    'remarks' => $item['remarks'],
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ]
            );
        }
    }
}
