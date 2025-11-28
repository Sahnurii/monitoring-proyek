<?php

namespace Database\Seeders;

use App\Models\MaterialRequest;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class MaterialRequestSeeder extends Seeder
{
    public function run(): void
    {
        $projects = Project::pluck('id', 'code');
        $usersByEmail = User::pluck('id', 'email');

        $requests = [
            [
                'code' => 'MRQ-001',
                'project_code' => 'PRJ-001',
                'requested_email' => 'manager@example.com',
                'request_date' => '2025-02-15',
                'status' => 'approved',
                'notes' => 'Permintaan material tahap pertama untuk pondasi dan struktur dasar.',
                'approved_email' => 'admin@example.com',
                'approved_at' => '2025-02-16 10:00:00',
            ],
            [
                'code' => 'MRQ-002',
                'project_code' => 'PRJ-002',
                'requested_email' => 'operator@example.com',
                'request_date' => '2025-03-10',
                'status' => 'submitted',
                'notes' => 'Material baja tambahan untuk perkuatan jembatan.',
                'approved_email' => null,
                'approved_at' => null,
            ],
            [
                'code' => 'MRQ-003',
                'project_code' => 'PRJ-003',
                'requested_email' => 'manager@example.com',
                'request_date' => '2025-03-20',
                'status' => 'rejected',
                'notes' => 'Pengajuan ditolak karena anggaran sudah melebihi batas.',
                'approved_email' => 'admin@example.com',
                'approved_at' => '2025-03-21 09:30:00',
            ],
            [
                'code' => 'MRQ-004',
                'project_code' => 'PRJ-004',
                'requested_email' => 'operator@example.com',
                'request_date' => '2025-04-01',
                'status' => 'draft',
                'notes' => 'Masih tahap perencanaan kebutuhan interior.',
                'approved_email' => null,
                'approved_at' => null,
            ],
            [
                'code' => 'MRQ-005',
                'project_code' => 'PRJ-005',
                'requested_email' => 'manager@example.com',
                'request_date' => '2025-05-05',
                'status' => 'approved',
                'notes' => 'Permintaan bahan cat dan gypsum untuk finishing.',
                'approved_email' => 'admin@example.com',
                'approved_at' => '2025-05-06 14:20:00',
            ],
        ];

        foreach ($requests as $request) {
            $projectId = $projects[$request['project_code']] ?? null;
            $requestedBy = $usersByEmail[$request['requested_email']] ?? null;

            if (! $projectId || ! $requestedBy) {
                continue;
            }

            $approvedBy = $request['approved_email']
                ? ($usersByEmail[$request['approved_email']] ?? null)
                : null;

            MaterialRequest::updateOrCreate(
                ['code' => $request['code'] ?? MaterialRequest::generateNextCode()],
                [
                    'project_id' => $projectId,
                    'requested_by' => $requestedBy,
                    'request_date' => $request['request_date'],
                    'status' => $request['status'],
                    'notes' => $request['notes'],
                    'approved_by' => $approvedBy,
                    'approved_at' => $request['approved_at'],
                ]
            );
        }
    }
}
