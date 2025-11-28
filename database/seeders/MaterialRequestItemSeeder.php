<?php

namespace Database\Seeders;

use App\Models\Material;
use App\Models\MaterialRequest;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MaterialRequestItemSeeder extends Seeder
{
    public function run(): void
    {
        $materials = Material::pluck('id', 'sku');
        $materialPrices = Material::pluck('unit_price', 'id');
        $requests = MaterialRequest::pluck('id', 'code');

        $items = [
            [
                'request_code' => 'MRQ-001',
                'material_sku' => 'MAT-001',
                'qty' => 200,
                'remarks' => 'Untuk pekerjaan pondasi utama',
            ],
            [
                'request_code' => 'MRQ-001',
                'material_sku' => 'MAT-002',
                'qty' => 1000,
                'remarks' => 'Campuran beton pondasi',
            ],
            [
                'request_code' => 'MRQ-002',
                'material_sku' => 'MAT-003',
                'qty' => 150,
                'remarks' => 'Tambahan untuk struktur jembatan',
            ],
            [
                'request_code' => 'MRQ-002',
                'material_sku' => 'MAT-004',
                'qty' => 10,
                'remarks' => 'Pengikat besi beton',
            ],
            [
                'request_code' => 'MRQ-003',
                'material_sku' => 'MAT-005',
                'qty' => 80,
                'remarks' => 'Drainase saluran air',
            ],
            [
                'request_code' => 'MRQ-005',
                'material_sku' => 'MAT-006',
                'qty' => 50,
                'remarks' => 'Finishing dinding interior',
            ],
            [
                'request_code' => 'MRQ-005',
                'material_sku' => 'MAT-009',
                'qty' => 40,
                'remarks' => 'Plafon ruang kerja dan ruang rapat',
            ],
        ];

        $now = Carbon::now();

        $rows = collect($items)
            ->map(function ($item) use ($materials, $materialPrices, $requests, $now) {
                $requestId = $requests[$item['request_code']] ?? null;
                $materialId = $materials[$item['material_sku']] ?? null;

                if (! $requestId || ! $materialId) {
                    return null;
                }

                $unitPrice = (float) ($materialPrices[$materialId] ?? 0);
                $totalPrice = round($unitPrice * $item['qty'], 2);

                return [
                    'material_request_id' => $requestId,
                    'material_id' => $materialId,
                    'qty' => $item['qty'],
                    'unit_price' => $unitPrice,
                    'total_price' => $totalPrice,
                    'remarks' => $item['remarks'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })
            ->filter()
            ->values();

        if ($rows->isEmpty()) {
            return;
        }

        DB::table('material_request_items')->insert($rows->all());

        $rows
            ->groupBy('material_request_id')
            ->each(function ($groupedRows, $requestId) use ($now) {
                $totalAmount = $groupedRows->sum('total_price');

                MaterialRequest::whereKey($requestId)->update([
                    'total_amount' => round($totalAmount, 2),
                    'updated_at' => $now,
                ]);
            });
    }
}
