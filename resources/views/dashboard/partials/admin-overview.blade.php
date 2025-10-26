@php
    $projectStatusMeta = [
        'planned' => ['label' => 'Direncanakan', 'variant' => 'secondary'],
        'ongoing' => ['label' => 'Berjalan', 'variant' => 'primary'],
        'done' => ['label' => 'Selesai', 'variant' => 'success'],
        'archived' => ['label' => 'Diarsipkan', 'variant' => 'dark'],
    ];

    $materialRequestStatusMeta = [
        'draft' => ['label' => 'Draft', 'variant' => 'secondary'],
        'submitted' => ['label' => 'Menunggu', 'variant' => 'info'],
        'approved' => ['label' => 'Disetujui', 'variant' => 'success'],
        'rejected' => ['label' => 'Ditolak', 'variant' => 'danger'],
    ];

    $purchaseOrderStatusMeta = [
        'draft' => ['label' => 'Draft', 'variant' => 'secondary'],
        'approved' => ['label' => 'Disetujui', 'variant' => 'primary'],
        'partial' => ['label' => 'Sebagian', 'variant' => 'warning'],
        'received' => ['label' => 'Selesai', 'variant' => 'success'],
        'canceled' => ['label' => 'Dibatalkan', 'variant' => 'danger'],
    ];

    $goodsReceiptStatusMeta = [
        'draft' => ['label' => 'Draft', 'variant' => 'secondary'],
        'in_progress' => ['label' => 'Proses', 'variant' => 'primary'],
        'completed' => ['label' => 'Selesai', 'variant' => 'success'],
        'returned' => ['label' => 'Retur', 'variant' => 'warning'],
    ];

    $goodsIssueStatusMeta = [
        'draft' => ['label' => 'Draft', 'variant' => 'secondary'],
        'issued' => ['label' => 'Dikeluarkan', 'variant' => 'primary'],
        'returned' => ['label' => 'Retur', 'variant' => 'warning'],
    ];

    $percentage = static function (int $count, int $total): int {
        return $total > 0 ? (int) round(($count / $total) * 100) : 0;
    };

    $formatQuantity = static function (float|int|string|null $value): string {
        $number = (float) ($value ?? 0);
        $formatted = number_format($number, 2, ',', '.');

        return rtrim(rtrim($formatted, '0'), ',') ?: '0';
    };

    $formatCurrency = static function (float|int|string|null $value): string {
        return 'Rp' . number_format((float) ($value ?? 0), 0, ',', '.');
    };

    $formatDate = static function ($date) {
        if ($date instanceof \Carbon\CarbonInterface) {
            return $date->format('d M Y');
        }

        if ($date === null) {
            return 'Tidak ada tanggal';
        }

        return (string) $date;
    };

    $projectTotal = array_sum($projectStatusCounts);
    $materialRequestTotal = array_sum($materialRequestStatusCounts);
    $purchaseOrderTotal = array_sum($purchaseOrderStatusCounts);
    $goodsReceiptTotal = array_sum($goodsReceiptStatusCounts);
    $goodsIssueTotal = array_sum($goodsIssueStatusCounts);
@endphp

<div class="container-fluid px-0 px-lg-3">
    <section class="dashboard-section mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4 p-xl-5">
                <div class="d-flex flex-column flex-xl-row gap-4 align-items-start align-items-xl-center justify-content-between">
                    <div>
                        <span class="badge text-bg-primary mb-3">Admin</span>
                        <h1 class="h3 mb-2">Halo, {{ $user->name }}!</h1>
                        <p class="text-muted mb-0">Lihat ringkasan pengadaan, proyek, dan stok material untuk memastikan semua proses berjalan lancar.</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <a class="btn btn-primary" href="{{ route('procurement.material-requests.create') }}">
                            <i class="bi bi-plus-lg me-1"></i>
                            Permintaan Material
                        </a>
                        <a class="btn btn-outline-primary" href="{{ route('procurement.purchase-orders.create') }}">
                            <i class="bi bi-file-earmark-text me-1"></i>
                            Pesanan Pembelian
                        </a>
                        <a class="btn btn-outline-secondary" href="{{ route('procurement.goods-receipts.create') }}">
                            <i class="bi bi-upload me-1"></i>
                            Penerimaan Barang
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="dashboard-section">
        <div class="row g-3 g-lg-4">
            @foreach ($summaryCards as $card)
                <div class="col-xl-3 col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body d-flex gap-3 align-items-center">
                            <span class="rounded-circle bg-{{ $card['variant'] }} bg-opacity-10 text-{{ $card['variant'] }} d-inline-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                                <i class="bi bi-{{ $card['icon'] }} fs-4"></i>
                            </span>
                            <div>
                                <p class="text-muted text-uppercase fw-semibold small mb-1">{{ $card['label'] }}</p>
                                <div class="d-flex align-items-baseline gap-2">
                                    <span class="h3 mb-0">{{ number_format($card['value']) }}</span>
                                </div>
                                <p class="text-muted small mb-0">{{ $card['hint'] }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <section class="dashboard-section mt-4">
        <div class="row g-4">
            <div class="col-xxl-8">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4 p-lg-5">
                        <div class="d-flex align-items-center justify-content-between mb-4">
                            <div>
                                <h2 class="h5 mb-1">Status Kegiatan</h2>
                                <p class="text-muted small mb-0">Distribusi progres proyek, permintaan, dan pengadaan terkini.</p>
                            </div>
                            <a class="btn btn-outline-secondary btn-sm" href="{{ route('projects.index') }}">Kelola Proyek</a>
                        </div>

                        <div class="row g-4">
                            <div class="col-md-6">
                                <h3 class="h6 text-muted text-uppercase mb-3">Proyek</h3>
                                <div class="d-flex flex-column gap-3">
                                    @foreach ($projectStatusMeta as $status => $meta)
                                        @php
                                            $count = $projectStatusCounts[$status] ?? 0;
                                        @endphp
                                        <div class="d-flex align-items-center gap-3">
                                            <span class="badge text-bg-{{ $meta['variant'] }} flex-shrink-0">{{ $meta['label'] }}</span>
                                            <div class="flex-grow-1">
                                                <div class="d-flex justify-content-between small mb-1">
                                                    <span>{{ number_format($count) }}</span>
                                                    <span>{{ $percentage($count, $projectTotal) }}%</span>
                                                </div>
                                                <div class="progress" style="height: 4px;">
                                                    <div class="progress-bar bg-{{ $meta['variant'] }}" style="width: {{ $percentage($count, $projectTotal) }}%"></div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h3 class="h6 text-muted text-uppercase mb-3">Permintaan Material</h3>
                                <div class="d-flex flex-column gap-3">
                                    @foreach ($materialRequestStatusMeta as $status => $meta)
                                        @php
                                            $count = $materialRequestStatusCounts[$status] ?? 0;
                                        @endphp
                                        <div class="d-flex align-items-center gap-3">
                                            <span class="badge text-bg-{{ $meta['variant'] }} flex-shrink-0">{{ $meta['label'] }}</span>
                                            <div class="flex-grow-1">
                                                <div class="d-flex justify-content-between small mb-1">
                                                    <span>{{ number_format($count) }}</span>
                                                    <span>{{ $percentage($count, $materialRequestTotal) }}%</span>
                                                </div>
                                                <div class="progress" style="height: 4px;">
                                                    <div class="progress-bar bg-{{ $meta['variant'] }}" style="width: {{ $percentage($count, $materialRequestTotal) }}%"></div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="row g-4">
                            <div class="col-md-4">
                                <h3 class="h6 text-muted text-uppercase mb-3">Pesanan Pembelian</h3>
                                <div class="d-flex flex-column gap-3">
                                    @foreach ($purchaseOrderStatusMeta as $status => $meta)
                                        @php
                                            $count = $purchaseOrderStatusCounts[$status] ?? 0;
                                        @endphp
                                        <div class="d-flex align-items-center gap-3">
                                            <span class="badge text-bg-{{ $meta['variant'] }} flex-shrink-0">{{ $meta['label'] }}</span>
                                            <div class="flex-grow-1">
                                                <div class="d-flex justify-content-between small mb-1">
                                                    <span>{{ number_format($count) }}</span>
                                                    <span>{{ $percentage($count, $purchaseOrderTotal) }}%</span>
                                                </div>
                                                <div class="progress" style="height: 4px;">
                                                    <div class="progress-bar bg-{{ $meta['variant'] }}" style="width: {{ $percentage($count, $purchaseOrderTotal) }}%"></div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="col-md-4">
                                <h3 class="h6 text-muted text-uppercase mb-3">Penerimaan Barang</h3>
                                <div class="d-flex flex-column gap-3">
                                    @foreach ($goodsReceiptStatusMeta as $status => $meta)
                                        @php
                                            $count = $goodsReceiptStatusCounts[$status] ?? 0;
                                        @endphp
                                        <div class="d-flex align-items-center gap-3">
                                            <span class="badge text-bg-{{ $meta['variant'] }} flex-shrink-0">{{ $meta['label'] }}</span>
                                            <div class="flex-grow-1">
                                                <div class="d-flex justify-content-between small mb-1">
                                                    <span>{{ number_format($count) }}</span>
                                                    <span>{{ $percentage($count, $goodsReceiptTotal) }}%</span>
                                                </div>
                                                <div class="progress" style="height: 4px;">
                                                    <div class="progress-bar bg-{{ $meta['variant'] }}" style="width: {{ $percentage($count, $goodsReceiptTotal) }}%"></div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="col-md-4">
                                <h3 class="h6 text-muted text-uppercase mb-3">Pengeluaran Barang</h3>
                                <div class="d-flex flex-column gap-3">
                                    @foreach ($goodsIssueStatusMeta as $status => $meta)
                                        @php
                                            $count = $goodsIssueStatusCounts[$status] ?? 0;
                                        @endphp
                                        <div class="d-flex align-items-center gap-3">
                                            <span class="badge text-bg-{{ $meta['variant'] }} flex-shrink-0">{{ $meta['label'] }}</span>
                                            <div class="flex-grow-1">
                                                <div class="d-flex justify-content-between small mb-1">
                                                    <span>{{ number_format($count) }}</span>
                                                    <span>{{ $percentage($count, $goodsIssueTotal) }}%</span>
                                                </div>
                                                <div class="progress" style="height: 4px;">
                                                    <div class="progress-bar bg-{{ $meta['variant'] }}" style="width: {{ $percentage($count, $goodsIssueTotal) }}%"></div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xxl-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4 p-lg-5">
                        <div class="d-flex align-items-center justify-content-between mb-4">
                            <div>
                                <h2 class="h5 mb-1">Kesehatan Stok</h2>
                                <p class="text-muted small mb-0">Pantau material dengan stok minim dan status gudang.</p>
                            </div>
                            <a class="btn btn-outline-secondary btn-sm" href="{{ route('supply.material-stock.index') }}">Lihat Stok</a>
                        </div>

                        <div class="row text-center mb-4">
                            <div class="col-4">
                                <p class="text-muted small mb-1">Material</p>
                                <span class="h5 mb-0">{{ number_format($stockSummary['total_materials'] ?? 0) }}</span>
                            </div>
                            <div class="col-4">
                                <p class="text-muted small mb-1">Unit Aman</p>
                                <span class="h5 text-success mb-0">{{ number_format($stockSummary['safe'] ?? 0) }}</span>
                            </div>
                            <div class="col-4">
                                <p class="text-muted small mb-1">Perlu Aksi</p>
                                <span class="h5 text-warning mb-0">{{ number_format(($stockSummary['warning'] ?? 0) + ($stockSummary['critical'] ?? 0)) }}</span>
                            </div>
                        </div>

                        <div class="list-group list-group-flush">
                            @forelse ($lowStockMaterials as $material)
                                <div class="list-group-item px-0 d-flex justify-content-between align-items-start">
                                    <div class="me-3">
                                        <p class="fw-semibold mb-1">{{ $material->name }}</p>
                                        <div class="d-flex flex-wrap gap-2 small text-muted">
                                            <span>{{ $material->sku }}</span>
                                            @if ($material->min_stock)
                                                <span>Min {{ $formatQuantity($material->min_stock) }} {{ $material->unit_label }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge text-bg-{{ $material->stock_status === 'critical' ? 'danger' : 'warning' }}">{{ $material->stock_status_label }}</span>
                                        <p class="mb-0 mt-2 small">{{ $formatQuantity($material->current_stock ?? 0) }} {{ $material->unit_label }}</p>
                                    </div>
                                </div>
                            @empty
                                <div class="list-group-item px-0">
                                    <p class="text-muted small mb-0">Semua material berada pada stok aman.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="dashboard-section mt-4">
        <div class="row g-4">
            <div class="col-xxl-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4 p-lg-5 d-flex flex-column">
                        <div class="d-flex align-items-center justify-content-between mb-4">
                            <div>
                                <h2 class="h5 mb-1">Permintaan Material Terbaru</h2>
                                <p class="text-muted small mb-0">Pantau permintaan yang sedang menunggu tindak lanjut.</p>
                            </div>
                            <a class="btn btn-outline-secondary btn-sm" href="{{ route('procurement.material-requests.index') }}">Semua Permintaan</a>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Kode</th>
                                        <th>Proyek</th>
                                        <th>Status</th>
                                        <th>Tanggal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($recentMaterialRequests as $request)
                                        @php
                                            $meta = $materialRequestStatusMeta[$request->status]
                                                ?? ['label' => \Illuminate\Support\Str::title(str_replace('_', ' ', $request->status)), 'variant' => 'secondary'];
                                        @endphp
                                        <tr>
                                            <td class="fw-semibold">{{ $request->code }}</td>
                                            <td>{{ optional($request->project)->name ?? '—' }}</td>
                                            <td>
                                                <span class="badge text-bg-{{ $meta['variant'] }}">{{ $meta['label'] }}</span>
                                            </td>
                                            <td>{{ $formatDate($request->request_date ?? $request->created_at) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td class="text-muted small" colspan="4">Belum ada data permintaan material.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xxl-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4 p-lg-5 d-flex flex-column">
                        <div class="d-flex align-items-center justify-content-between mb-4">
                            <div>
                                <h2 class="h5 mb-1">Pesanan Pembelian Terbaru</h2>
                                <p class="text-muted small mb-0">Ringkasan PO terbaru beserta supplier terkait.</p>
                            </div>
                            <a class="btn btn-outline-secondary btn-sm" href="{{ route('procurement.purchase-orders.index') }}">Semua PO</a>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Kode</th>
                                        <th>Supplier</th>
                                        <th>Status</th>
                                        <th class="text-end">Nilai</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($recentPurchaseOrders as $order)
                                        @php
                                            $meta = $purchaseOrderStatusMeta[$order->status]
                                                ?? ['label' => \Illuminate\Support\Str::title(str_replace('_', ' ', $order->status)), 'variant' => 'secondary'];
                                        @endphp
                                        <tr>
                                            <td class="fw-semibold">{{ $order->code }}</td>
                                            <td>{{ optional($order->supplier)->name ?? '—' }}</td>
                                            <td><span class="badge text-bg-{{ $meta['variant'] }}">{{ $meta['label'] }}</span></td>
                                            <td class="text-end">{{ $formatCurrency($order->total) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td class="text-muted small" colspan="4">Belum ada data pesanan pembelian.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="dashboard-section mt-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4 p-lg-5 d-flex flex-column">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div>
                        <h2 class="h5 mb-1">Penerimaan Barang Terakhir</h2>
                        <p class="text-muted small mb-0">Ikhtisar penerimaan terbaru yang telah dicatat.</p>
                    </div>
                    <a class="btn btn-outline-secondary btn-sm" href="{{ route('procurement.goods-receipts.index') }}">Semua Penerimaan</a>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Kode</th>
                                <th>Proyek</th>
                                <th>Status</th>
                                <th>Tanggal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentGoodsReceipts as $receipt)
                                @php
                                    $meta = $goodsReceiptStatusMeta[$receipt->status]
                                        ?? ['label' => \Illuminate\Support\Str::title(str_replace('_', ' ', $receipt->status)), 'variant' => 'secondary'];
                                @endphp
                                <tr>
                                    <td class="fw-semibold">{{ $receipt->code }}</td>
                                    <td>{{ optional($receipt->project)->name ?? '—' }}</td>
                                    <td><span class="badge text-bg-{{ $meta['variant'] }}">{{ $meta['label'] }}</span></td>
                                    <td>{{ $formatDate($receipt->received_date ?? $receipt->created_at) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="text-muted small" colspan="4">Belum ada data penerimaan barang.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>
