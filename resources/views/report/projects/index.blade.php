@extends('layouts.app')

@section('content')
    <div class="container-fluid px-0 px-lg-3">
        <header
            class="d-flex flex-wrap flex-md-nowrap align-items-start align-items-md-center justify-content-between gap-3 mb-4">
            <div>
                <h1 class="fw-bold mb-1">Laporan Data Proyek</h1>
                <p class="text-muted mb-0">Ringkasan performa, status, dan portofolio proyek yang sedang dikelola.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('projects.index') }}" class="btn btn-outline-primary">
                    <i class="bi bi-kanban me-2"></i>Kelola Data Proyek
                </a>
            </div>
        </header>

        <section class="mb-4">
            <div class="row g-3">
                <div class="col-sm-6 col-xl-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="text-uppercase text-muted small">Total Proyek</span>
                                <span class="badge bg-light text-dark">Keseluruhan</span>
                            </div>
                            <h2 class="h3 mb-1">{{ number_format($summary['total_projects']) }}</h2>
                            <p class="text-muted mb-0">Proyek tercatat aktif maupun arsip</p>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="text-uppercase text-muted small">Total Anggaran</span>
                                <span class="badge text-bg-success">Rp</span>
                            </div>
                            <h2 class="h3 mb-1">Rp {{ number_format($summary['total_budget'], 2, ',', '.') }}</h2>
                            <p class="text-muted mb-0">Akumulasi dari seluruh proyek terpilih</p>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="text-uppercase text-muted small">Rasio Selesai</span>
                                <span class="badge text-bg-primary">{{ $summary['completion_rate'] }}%</span>
                            </div>
                            <h2 class="h3 mb-1">{{ number_format($summary['completed_projects']) }} proyek</h2>
                            <p class="text-muted mb-0">{{ number_format($summary['active_projects']) }} sedang berjalan,
                                {{ number_format($summary['overdue_projects']) }} terlambat</p>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="text-uppercase text-muted small">Rata-rata Durasi</span>
                                <span class="badge text-bg-secondary">Hari</span>
                            </div>
                            <h2 class="h3 mb-1">
                                @if ($summary['average_duration'])
                                    {{ number_format($summary['average_duration']) }} hari
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </h2>
                            <p class="text-muted mb-0">Menggunakan proyek dengan periode lengkap</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                @php
                    $startDateValue = optional($filters['start_date'])->toDateString();
                    $endDateValue = optional($filters['end_date'])->toDateString();
                    $pdfRouteParams = $filterQuery ?? [];
                @endphp
                <form action="{{ route('reports.project') }}" method="GET" class="row g-3 align-items-end">
                    <div class="col-lg-3">
                        <label for="status" class="form-label">Status Proyek</label>
                        <select name="status" id="status" class="form-select">
                            <option value="">Semua Status</option>
                            @foreach ($statuses as $value => $label)
                                <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-3">
                        <label for="client" class="form-label">Klien</label>
                        <input type="search" name="client" id="client" class="form-control"
                            placeholder="Cari nama klien" value="{{ $filters['client'] ?? '' }}">
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <label for="start_date" class="form-label">Mulai dari</label>
                        <input type="date" name="start_date" id="start_date" class="form-control"
                            value="{{ $startDateValue }}">
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <label for="end_date" class="form-label">Sampai</label>
                        <input type="date" name="end_date" id="end_date" class="form-control"
                            value="{{ $endDateValue }}">
                    </div>
                    <div class="col-12 d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-funnel me-2"></i>Terapkan Filter
                        </button>
                        <a href="{{ route('reports.project') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-counterclockwise me-2"></i>Reset
                        </a>
                        <a href="{{ route('reports.project.pdf', $pdfRouteParams) }}" target="_blank"
                            class="btn btn-outline-success">
                            <i class="bi bi-filetype-pdf me-2"></i>Ekspor PDF
                        </a>
                        {{-- <button type="button" class="btn btn-outline-dark ms-auto" onclick="window.print()">
                            <i class="bi bi-printer me-2"></i>Cetak Laporan
                        </button> --}}
                    </div>
                </form>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h2 class="h5 mb-0">Distribusi Status</h2>
                            <span class="badge text-bg-light">{{ number_format($summary['total_projects']) }} proyek</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Status</th>
                                        <th class="text-center">Jumlah</th>
                                        <th class="text-end">Total Anggaran</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $statusBadge = [
                                            'planned' => 'bg-secondary',
                                            'ongoing' => 'bg-primary',
                                            'done' => 'bg-success',
                                            'archived' => 'bg-dark',
                                        ];
                                    @endphp
                                    @foreach ($statusSummary as $row)
                                        <tr>
                                            <td>
                                                <span
                                                    class="badge {{ $statusBadge[$row['key']] ?? 'bg-light text-dark border' }}">
                                                    {{ $row['label'] }}
                                                </span>
                                            </td>
                                            <td class="text-center">{{ number_format($row['count']) }}</td>
                                            <td class="text-end">Rp {{ number_format($row['budget'], 2, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h2 class="h5 mb-0">Klien Teratas</h2>
                            <span class="badge text-bg-light">Top 5</span>
                        </div>
                        @if (!empty($topClients))
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Klien</th>
                                            <th class="text-center">Proyek</th>
                                            <th class="text-end">Total Anggaran</th>
                                            <th class="text-end">Target Terakhir</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($topClients as $clientRow)
                                            <tr>
                                                <td class="fw-semibold">{{ $clientRow['client'] }}</td>
                                                <td class="text-center">{{ number_format($clientRow['projects']) }}</td>
                                                <td class="text-end">Rp
                                                    {{ number_format($clientRow['total_budget'], 2, ',', '.') }}</td>
                                                <td class="text-end">
                                                    {{ $clientRow['latest_end'] ?? 'Belum ditetapkan' }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-4 text-muted">
                                <i class="bi bi-people display-6 d-block mb-2"></i>
                                <p class="mb-0">Belum ada data klien untuk ditampilkan.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <h2 class="h5 mb-3">Timeline Pekerjaan</h2>
                @if (!empty($timeline))
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Periode Mulai</th>
                                    <th class="text-center">Jumlah Proyek</th>
                                    <th class="text-end">Total Anggaran</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($timeline as $item)
                                    <tr>
                                        <td class="fw-semibold">{{ $item['label'] }}</td>
                                        <td class="text-center">{{ number_format($item['count']) }}</td>
                                        <td class="text-end">Rp {{ number_format($item['budget'], 2, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-calendar-event display-6 d-block mb-2"></i>
                        <p class="mb-0">Belum ada jadwal mulai proyek yang sesuai filter.</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h2 class="h5 mb-3">Detail Proyek</h2>
                @php
                    $today = \Illuminate\Support\Carbon::today();
                @endphp
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col" style="width: 70px;">No.</th>
                                <th scope="col">Kode</th>
                                <th scope="col">Nama</th>
                                <th scope="col">Klien</th>
                                <th scope="col">Periode</th>
                                <th scope="col">Durasi</th>
                                <th scope="col" class="text-center">Status</th>
                                <th scope="col" class="text-end">Anggaran</th>
                                <th scope="col" class="text-end">Sisa Waktu</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($projects as $project)
                                @php
                                    $start = $project->start_date;
                                    $end = $project->end_date;
                                    $iteration = $loop->iteration;
                                    $duration = $start && $end ? $start->diffInDays($end) + 1 : null;
                                    $daysToGo = $end ? $today->diffInDays($end, false) : null;
                                    $statusLabel = $statuses[$project->status] ?? ucfirst($project->status ?? '');
                                    $statusClass = $statusBadge[$project->status] ?? 'bg-light text-dark border';
                                    $isOverdue =
                                        $end &&
                                        $daysToGo !== null &&
                                        $daysToGo < 0 &&
                                        !in_array($project->status, ['done', 'archived'], true);
                                @endphp
                                <tr>
                                    <td>{{ $iteration }}</td>
                                    <td class="fw-semibold">{{ $project->code }}</td>
                                    <td>
                                        <div class="fw-semibold">{{ $project->name }}</div>
                                        <div class="text-muted small">Dibuat
                                            {{ optional($project->created_at)->translatedFormat('d M Y') }}</div>
                                    </td>
                                    <td>{{ $project->client ?? '-' }}</td>
                                    <td>
                                        <div>{{ $start?->translatedFormat('d M Y') ?? 'Belum ditetapkan' }}</div>
                                        <div class="text-muted small">
                                            {{ $end ? 's.d. ' . $end->translatedFormat('d M Y') : 'Target belum ditetapkan' }}
                                        </div>
                                    </td>
                                    <td>
                                        @if ($duration)
                                            {{ number_format($duration) }} hari
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
                                    </td>
                                    <td class="text-end">
                                        @if (!is_null($project->budget))
                                            Rp {{ number_format($project->budget, 2, ',', '.') }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if ($daysToGo !== null)
                                            @if ($daysToGo > 0)
                                                <span class="badge text-bg-success">{{ $daysToGo }} hari lagi</span>
                                            @elseif ($daysToGo === 0)
                                                <span class="badge text-bg-warning">Jatuh tempo hari ini</span>
                                            @elseif($isOverdue)
                                                <span class="badge text-bg-danger">Terlambat {{ abs($daysToGo) }}
                                                    hari</span>
                                            @else
                                                <span class="badge text-bg-secondary">Selesai</span>
                                            @endif
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-5">
                                        <i class="bi bi-kanban text-muted display-6 d-block mb-3"></i>
                                        <p class="mb-1 fw-semibold">Belum ada proyek sesuai filter</p>
                                        <p class="text-muted mb-0">Atur ulang filter atau tambahkan proyek baru.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
