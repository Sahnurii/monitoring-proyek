@php
    $tasks = [
        [
            'title' => 'Input Aktivitas Harian',
            'icon' => 'journal-text',
            'description' => 'Catat penggunaan material dan update progres pekerjaan harian.',
        ],
        [
            'title' => 'Penerimaan Material',
            'icon' => 'box-seam',
            'description' => 'Verifikasi material yang datang serta jumlah stok yang diterima.',
        ],
        [
            'title' => 'Pengeluaran ke Proyek',
            'icon' => 'truck',
            'description' => 'Lakukan serah terima material ke tim proyek dengan bukti digital.',
        ],
    ];
@endphp

<section class="dashboard-section mb-4">
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4 p-lg-5">
            <div class="d-flex flex-column flex-lg-row gap-4 align-items-start align-items-lg-center justify-content-between">
                <div>
                    <span class="badge text-bg-warning mb-3">Operator</span>
                    <h1 class="h3 mb-2">Hai, {{ $user->name }}!</h1>
                    <p class="text-muted mb-0">Input data penerimaan dan pengeluaran material secara cepat dan akurat.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a class="btn btn-warning text-white" href="#">Catat Penerimaan</a>
                    <a class="btn btn-outline-warning" href="#">Keluarkan Material</a>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="dashboard-section">
    <div class="row g-4">
        @foreach ($tasks as $task)
            <div class="col-md-4">
                <div class="module-card h-100">
                    <div class="module-card-header">
                        <div class="module-icon module-icon-soft text-warning">
                            <i class="bi bi-{{ $task['icon'] }}"></i>
                        </div>
                    </div>
                    <div class="module-card-body">
                        <h2 class="h5 mb-2">{{ $task['title'] }}</h2>
                        <p class="text-muted mb-0">{{ $task['description'] }}</p>
                    </div>
                    <div class="module-card-footer">
                        <button class="btn btn-link btn-sm px-0">Mulai</button>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</section>

<section class="dashboard-section mt-4">
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <h2 class="h5 mb-3">Riwayat Singkat</h2>
            <div class="activity-timeline">
                <div class="activity-item">
                    <span class="dot bg-warning"></span>
                    <div>
                        <p class="fw-semibold mb-1">Penerimaan PO #PO-2024-010</p>
                        <p class="text-muted small mb-0">10 Palet Semen - Diterima 15 menit lalu</p>
                    </div>
                </div>
                <div class="activity-item">
                    <span class="dot bg-success"></span>
                    <div>
                        <p class="fw-semibold mb-1">Pengeluaran ke Proyek "Gudang Barat"</p>
                        <p class="text-muted small mb-0">5 Drum Cat - Diserahkan ke Pak Budi</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
