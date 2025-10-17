@php
    $widgets = [
        [
            'title' => 'Progress Proyek',
            'icon' => 'bar-chart-line',
            'description' => 'Pantau status proyek aktif dan catatan material yang dibutuhkan.',
            'items' => [
                'Projects → Material Requests',
                'Material Requests → Items',
            ],
        ],
        [
            'title' => 'Permintaan Material',
            'icon' => 'clipboard-data',
            'description' => 'Review dan tindak lanjuti permintaan material dari lapangan.',
            'items' => [
                'Users → Material Requests',
                'Material Requests → Purchase Orders',
            ],
        ],
        [
            'title' => 'Distribusi Material',
            'icon' => 'box-arrow-up',
            'description' => 'Pastikan kebutuhan proyek terpenuhi tepat waktu.',
            'items' => [
                'Goods Issues → Items',
                'Materials → Stock Movements',
            ],
        ],
    ];
@endphp

<section class="dashboard-section mb-4">
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4 p-lg-5">
            <div class="d-flex flex-column flex-lg-row gap-4 align-items-start align-items-lg-center justify-content-between">
                <div>
                    <span class="badge text-bg-success mb-3">Manager</span>
                    <h1 class="h3 mb-2">Selamat datang, {{ $user->name }}.</h1>
                    <p class="text-muted mb-0">Monitor kebutuhan material dan koordinasikan tim untuk menjaga kelancaran proyek.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a class="btn btn-success" href="#">Ajukan Permintaan</a>
                    <a class="btn btn-outline-success" href="#">Lihat Jadwal Proyek</a>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="dashboard-section">
    <div class="row g-4">
        @foreach ($widgets as $widget)
            <div class="col-lg-4">
                <div class="module-card h-100">
                    <div class="module-card-header">
                        <div class="module-icon module-icon-soft text-success">
                            <i class="bi bi-{{ $widget['icon'] }}"></i>
                        </div>
                    </div>
                    <div class="module-card-body">
                        <h2 class="h5 mb-2">{{ $widget['title'] }}</h2>
                        <p class="text-muted mb-3">{{ $widget['description'] }}</p>
                        <ul class="module-relations-list">
                            @foreach ($widget['items'] as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="module-card-footer">
                        <button class="btn btn-link btn-sm px-0">Lihat detail</button>
                        <button class="btn btn-link btn-sm px-0">Ekspor laporan</button>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</section>
