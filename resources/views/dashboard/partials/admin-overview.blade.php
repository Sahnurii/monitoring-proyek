@php
    $modules = [
        [
            'title' => 'Permintaan Material',
            'icon' => 'clipboard-data',
            'description' => 'Monitor alur permintaan material dari proyek dan status persetujuan.',
            'relations' => [
                'Users → Material Requests (requested_by)',
                'Projects → Material Requests',
                'Material Requests → Material Request Items',
                'Material Requests → Purchase Orders',
            ],
        ],
        [
            'title' => 'Pesanan Pembelian',
            'icon' => 'file-earmark-text',
            'description' => 'Kelola pembuatan dan persetujuan PO untuk kebutuhan material.',
            'relations' => [
                'Users → Purchase Orders (approved_by)',
                'Suppliers → Purchase Orders',
                'Purchase Orders → Purchase Order Items',
                'Purchase Orders → Goods Receipts',
            ],
        ],
        [
            'title' => 'Penerimaan Barang',
            'icon' => 'box-arrow-in-down',
            'description' => 'Catat penerimaan barang dari supplier dan update stok material.',
            'relations' => [
                'Users → Goods Receipts (received_by)',
                'Purchase Orders → Goods Receipts',
                'Goods Receipts → Goods Receipt Items',
                'Materials → Goods Receipt Items',
            ],
        ],
        [
            'title' => 'Pengeluaran Barang',
            'icon' => 'box-arrow-up',
            'description' => 'Kelola distribusi material ke proyek dan track penggunaan.',
            'relations' => [
                'Users → Goods Issues (issued_by)',
                'Projects → Goods Issues',
                'Goods Issues → Goods Issue Items',
                'Materials → Goods Issue Items',
            ],
        ],
        [
            'title' => 'Manajemen Material',
            'icon' => 'upc-scan',
            'description' => 'Atur master data material termasuk satuan dan supplier terkait.',
            'relations' => [
                'Units → Materials',
                'Materials → Stock Movements',
                'Materials → Purchase Order Items',
                'Materials → Material Request Items',
            ],
        ],
        [
            'title' => 'Proyek & Monitoring',
            'icon' => 'kanban',
            'description' => 'Lihat status proyek, kebutuhan material, dan progres distribusi.',
            'relations' => [
                'Projects → Material Requests',
                'Projects → Goods Issues',
                'Projects → Purchase Orders (opsional)',
            ],
        ],
    ];
@endphp

<section class="dashboard-section mb-4">
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4 p-xl-5">
            <div class="d-flex flex-column flex-xl-row gap-4 align-items-start align-items-xl-center justify-content-between">
                <div>
                    <span class="badge text-bg-primary mb-3">Admin</span>
                    <h1 class="h3 mb-2">Halo, {{ $user->name }}!</h1>
                    <p class="text-muted mb-0">Kelola seluruh proses proyek dan pengadaan material dalam satu tempat terintegrasi.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a class="btn btn-primary" href="#">Buat Permintaan Material</a>
                    <a class="btn btn-outline-primary" href="#">Tambah Pesanan Pembelian</a>
                    <a class="btn btn-outline-secondary" href="#">Unggah Penerimaan</a>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="dashboard-section">
    <div class="row g-4">
        @foreach ($modules as $module)
            <div class="col-xl-4 col-lg-6">
                <div class="module-card h-100">
                    <div class="module-card-header d-flex align-items-start justify-content-between gap-3">
                        <div class="module-icon">
                            <i class="bi bi-{{ $module['icon'] }}"></i>
                        </div>
                        <button class="btn btn-sm btn-outline-primary" type="button">Lihat Detail</button>
                    </div>
                    <div class="module-card-body">
                        <h2 class="h5 mb-2">{{ $module['title'] }}</h2>
                        <p class="text-muted mb-3">{{ $module['description'] }}</p>
                        <div class="module-relations">
                            <span class="module-relations-label">Relasi Utama</span>
                            <ul class="module-relations-list">
                                @foreach ($module['relations'] as $relation)
                                    <li>{{ $relation }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                    <div class="module-card-footer">
                        <button class="btn btn-link btn-sm px-0">Tambahkan data baru</button>
                        <button class="btn btn-link btn-sm px-0">Import dari Excel</button>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</section>

<section class="dashboard-section mt-4">
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <h2 class="h5 mb-3">Alur Kerja Terintegrasi</h2>
            <div class="workflow-map">
                <div class="workflow-step">
                    <div class="workflow-badge bg-primary">1</div>
                    <div>
                        <h3 class="h6 mb-1">Permintaan Material</h3>
                        <p class="text-muted small mb-0">Diajukan oleh proyek, diverifikasi oleh admin, lalu diteruskan ke pengadaan.</p>
                    </div>
                </div>
                <span class="workflow-separator"><i class="bi bi-arrow-right"></i></span>
                <div class="workflow-step">
                    <div class="workflow-badge bg-success">2</div>
                    <div>
                        <h3 class="h6 mb-1">Pesanan Pembelian</h3>
                        <p class="text-muted small mb-0">Admin membuat PO, memilih supplier, dan mengatur jadwal pengiriman.</p>
                    </div>
                </div>
                <span class="workflow-separator"><i class="bi bi-arrow-right"></i></span>
                <div class="workflow-step">
                    <div class="workflow-badge bg-info">3</div>
                    <div>
                        <h3 class="h6 mb-1">Penerimaan & Stok</h3>
                        <p class="text-muted small mb-0">Barang diterima gudang, stok diperbarui, dan siap distribusi ke proyek.</p>
                    </div>
                </div>
                <span class="workflow-separator"><i class="bi bi-arrow-right"></i></span>
                <div class="workflow-step">
                    <div class="workflow-badge bg-warning">4</div>
                    <div>
                        <h3 class="h6 mb-1">Pengeluaran Barang</h3>
                        <p class="text-muted small mb-0">Material dikeluarkan ke proyek disertai bukti serah terima dan pelacakan.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
