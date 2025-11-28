<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>{{ $title ?? 'Laporan Data Proyek' }}</title>
    <style>
        @page {
            margin: 24px 28px;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #1f2933;
        }

        h1 {
            font-size: 20px;
            margin: 0 0 4px;
            color: #0f172a;
        }

        h2 {
            font-size: 14px;
            margin: 12px 0 6px;
            color: #111827;
        }

        .meta {
            font-size: 10px;
            color: #6b7280;
        }

        .filters {
            font-size: 10px;
            margin-top: 6px;
            color: #374151;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            border: 1px solid #d1d5db;
            padding: 6px;
            vertical-align: top;
        }

        th {
            background: #f3f4f6;
            font-weight: 600;
            color: #0f172a;
        }

        .summary-table th,
        .summary-table td {
            text-align: left;
            border: 1px solid #e5e7eb;
            padding: 6px 8px;
        }

        .summary-table th {
            width: 22%;
            background: #f8fafc;
        }

        .status-table th,
        .status-table td,
        .timeline-table th,
        .timeline-table td,
        .clients-table th,
        .clients-table td {
            text-align: left;
        }

        .status-table td,
        .timeline-table td,
        .clients-table td {
            font-size: 10px;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .muted {
            color: #6b7280;
            font-size: 10px;
        }

        .section {
            margin-top: 16px;
        }

        .badge {
            display: inline-block;
            padding: 2px 6px;
            font-size: 9px;
            border-radius: 4px;
            background: #e2e8f0;
            color: #1f2937;
        }

        .badge-success {
            background: #dcfce7;
            color: #166534;
        }

        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-danger {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-secondary {
            background: #e5e7eb;
            color: #374151;
        }

        .detail-table th,
        .detail-table td {
            font-size: 10px;
        }

        .detail-table th {
            background: #f1f5f9;
        }
    </style>
</head>

<body>
    @php
        $filterDescriptions = [];
        if (!empty($filters['status'])) {
            $filterDescriptions[] = 'Status: ' . ($statuses[$filters['status']] ?? ucfirst($filters['status']));
        }
        if (!empty($filters['client'])) {
            $filterDescriptions[] = 'Klien: ' . $filters['client'];
        }
        if ($filters['start_date']) {
            $filterDescriptions[] = 'Mulai ≥ ' . $filters['start_date']->translatedFormat('d M Y');
        }
        if ($filters['end_date']) {
            $filterDescriptions[] = 'Selesai ≤ ' . $filters['end_date']->translatedFormat('d M Y');
        }

        $today = \Illuminate\Support\Carbon::today();
        $statusBadgeClass = [
            'planned' => 'badge-secondary',
            'ongoing' => 'badge-secondary',
            'done' => 'badge-success',
            'archived' => 'badge-secondary',
        ];
    @endphp

    <div>
        <h1>{{ $title ?? 'Laporan Data Proyek' }}</h1>
        <div class="meta">Dicetak pada {{ $generatedAt->translatedFormat('d M Y H:i') }}</div>
        @if (!empty($filterDescriptions))
            <div class="filters">Filter diterapkan: {{ implode(' | ', $filterDescriptions) }}</div>
        @endif
        <div class="filters">Total proyek dalam laporan: {{ number_format($summary['total_projects']) }} proyek</div>
    </div>

    <div class="section">
        <h2>Ringkasan Utama</h2>
        <table class="summary-table">
            <tr>
                <th>Total Proyek</th>
                <td>{{ number_format($summary['total_projects']) }}</td>
                <th>Proyek Berjalan</th>
                <td>{{ number_format($summary['active_projects']) }}</td>
            </tr>
            <tr>
                <th>Total Anggaran</th>
                <td>Rp {{ number_format($summary['total_budget'], 2, ',', '.') }}</td>
                <th>Proyek Selesai</th>
                <td>{{ number_format($summary['completed_projects']) }} ({{ $summary['completion_rate'] }}%)</td>
            </tr>
            <tr>
                <th>Rata-rata Anggaran</th>
                <td>Rp {{ number_format($summary['average_budget'], 2, ',', '.') }}</td>
                <th>Proyek Terlambat</th>
                <td>{{ number_format($summary['overdue_projects']) }}</td>
            </tr>
            <tr>
                <th>Rata-rata Durasi</th>
                <td>
                    @if ($summary['average_duration'])
                        {{ number_format($summary['average_duration']) }} hari
                    @else
                        <span class="muted">Tidak tersedia</span>
                    @endif
                </td>
                <th>Proyek Perencanaan</th>
                <td>{{ number_format($summary['planned_projects']) }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h2>Distribusi Status</h2>
        <table class="status-table">
            <thead>
                <tr>
                    <th>Status</th>
                    <th class="text-center">Jumlah Proyek</th>
                    <th class="text-right">Total Anggaran</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($statusSummary as $row)
                    <tr>
                        <td>{{ $row['label'] }}</td>
                        <td class="text-center">{{ number_format($row['count']) }}</td>
                        <td class="text-right">Rp {{ number_format($row['budget'], 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>Klien Teratas</h2>
        <table class="clients-table">
            <thead>
                <tr>
                    <th>Klien</th>
                    <th class="text-center">Jumlah Proyek</th>
                    <th class="text-right">Total Anggaran</th>
                    <th class="text-center">Target Selesai Terakhir</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($topClients as $clientRow)
                    <tr>
                        <td>{{ $clientRow['client'] }}</td>
                        <td class="text-center">{{ number_format($clientRow['projects']) }}</td>
                        <td class="text-right">Rp {{ number_format($clientRow['total_budget'], 2, ',', '.') }}</td>
                        <td class="text-center">{{ $clientRow['latest_end'] ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center muted">Belum ada data klien yang dapat ditampilkan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>Timeline Proyek</h2>
        <table class="timeline-table">
            <thead>
                <tr>
                    <th>Periode Mulai</th>
                    <th class="text-center">Jumlah Proyek</th>
                    <th class="text-right">Total Anggaran</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($timeline as $item)
                    <tr>
                        <td>{{ $item['label'] }}</td>
                        <td class="text-center">{{ number_format($item['count']) }}</td>
                        <td class="text-right">Rp {{ number_format($item['budget'], 2, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center muted">Belum ada data timeline yang sesuai filter.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>Rincian Proyek</h2>
        <table class="detail-table">
            <thead>
                <tr>
                    <th style="width: 32px;">No</th>
                    <th>Kode</th>
                    <th>Nama Proyek</th>
                    <th>Klien</th>
                    <th>Tanggal Mulai</th>
                    <th>Tanggal Selesai</th>
                    <th>Durasi</th>
                    <th>Status</th>
                    <th class="text-right">Anggaran</th>
                    <th class="text-center">Sisa Waktu</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($projects as $project)
                    @php
                        $start = $project->start_date;
                        $end = $project->end_date;
                        $duration = $start && $end ? $start->diffInDays($end) + 1 : null;
                        $daysToGo = $end ? $today->diffInDays($end, false) : null;
                        $statusLabel = $statuses[$project->status] ?? ucfirst($project->status ?? '');
                        $badgeClass = $statusBadgeClass[$project->status] ?? 'badge-secondary';
                        $isOpenStatus = !in_array($project->status, ['done', 'archived'], true);
                    @endphp
                    <tr>
                        <td class="text-center">{{ $loop->iteration }}</td>
                        <td>{{ $project->code }}</td>
                        <td>{{ $project->name }}</td>
                        <td>{{ $project->client ?? '-' }}</td>
                        <td>{{ $start ? $start->translatedFormat('d M Y') : '-' }}</td>
                        <td>{{ $end ? $end->translatedFormat('d M Y') : '-' }}</td>
                        <td class="text-center">
                            @if ($duration)
                                {{ number_format($duration) }} hari
                            @else
                                -
                            @endif
                        </td>
                        <td><span class="badge {{ $badgeClass }}">{{ $statusLabel }}</span></td>
                        <td class="text-right">
                            @if (!is_null($project->budget))
                                Rp {{ number_format($project->budget, 2, ',', '.') }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="text-center">
                            @if ($daysToGo !== null)
                                @if ($daysToGo > 0 && $isOpenStatus)
                                    <span class="badge badge-success">{{ $daysToGo }} hari lagi</span>
                                @elseif ($daysToGo === 0 && $isOpenStatus)
                                    <span class="badge badge-warning">Jatuh tempo</span>
                                @elseif ($daysToGo < 0 && $isOpenStatus)
                                    <span class="badge badge-danger">Terlambat {{ abs($daysToGo) }} hari</span>
                                @else
                                    <span class="badge badge-secondary">Selesai</span>
                                @endif
                            @else
                                <span class="muted">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center muted">Tidak ada proyek yang sesuai dengan filter saat ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</body>

</html>
