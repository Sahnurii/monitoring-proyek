@extends('layouts.app')

@section('content')
    <div class="container-fluid px-0 px-lg-3">
        <header
            class="d-flex flex-wrap flex-md-nowrap align-items-start align-items-md-center justify-content-between gap-3 mb-4">
            <div>
                <h1 class="fw-bold mb-1">Stok Material</h1>
                <p class="text-muted mb-0">Pantau ketersediaan material dan tindak lanjuti kebutuhan restock.
                </p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('materials.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-database me-2"></i>Data Material
                </a>
                <a href="{{ route('procurement.goods-receipts.index') }}" class="btn btn-outline-warning">
                    <i class="bi bi-box-seam me-2"></i>Penerimaan (GR)
                </a>
            </div>
        </header>

        <section class="mb-4">
            <div class="row g-3">
                <div class="col-sm-6 col-xl-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="text-uppercase text-muted small">Total Material</span>
                                <span class="badge bg-light text-dark">Semua</span>
                            </div>
                            <h2 class="h3 mb-1">{{ number_format($summary['total_materials'] ?? 0) }}</h2>
                            <p class="text-muted mb-0">Material tercatat dalam sistem</p>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="text-uppercase text-muted small">Aman</span>
                                <span class="badge text-bg-success">Safe</span>
                            </div>
                            <h2 class="h3 mb-1">{{ number_format($summary['safe'] ?? 0) }}</h2>
                            <p class="text-muted mb-0">Material di atas stok minimal</p>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="text-uppercase text-muted small">Perlu Restock</span>
                                <span class="badge text-bg-warning">Warning</span>
                            </div>
                            <h2 class="h3 mb-1">{{ number_format($summary['warning'] ?? 0) }}</h2>
                            <p class="text-muted mb-0">Stok mendekati batas minimal</p>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="text-uppercase text-muted small">Kosong</span>
                                <span class="badge text-bg-danger">Critical</span>
                            </div>
                            <h2 class="h3 mb-1">{{ number_format($summary['critical'] ?? 0) }}</h2>
                            <p class="text-muted mb-0">Perlu permintaan material segera</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <form action="{{ route('supply.material-stock.index') }}" method="GET"
                    class="row g-3 align-items-end mb-4">
                    <div class="col-lg-4">
                        <label for="search" class="form-label">Pencarian</label>
                        <input type="search" id="search" name="search" value="{{ $filters['search'] ?? '' }}"
                            class="form-control" placeholder="Cari berdasarkan SKU atau nama material">
                    </div>
                    <div class="col-lg-3">
                        <label for="unit_id" class="form-label">Satuan</label>
                        <select id="unit_id" name="unit_id" class="form-select">
                            <option value="">Semua Satuan</option>
                            @foreach ($units as $unit)
                                <option value="{{ $unit->id }}" @selected((string) $unit->id === (string) ($filters['unit_id'] ?? ''))>
                                    {{ $unit->name }} ({{ $unit->symbol }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-lg-3">
                        <label for="stock_status" class="form-label">Status Stok</label>
                        <select id="stock_status" name="stock_status" class="form-select">
                            <option value="">Semua Status</option>
                            <option value="safe" @selected(($filters['stock_status'] ?? '') === 'safe')>Aman</option>
                            <option value="warning" @selected(($filters['stock_status'] ?? '') === 'warning')>Perlu Restock</option>
                            <option value="critical" @selected(($filters['stock_status'] ?? '') === 'critical')>Kosong</option>
                        </select>
                    </div>
                    <div class="col-lg-2">
                        <label for="per_page" class="form-label">Data/Halaman</label>
                        <select id="per_page" name="per_page" class="form-select">
                            @foreach ([10, 25, 50, 100] as $option)
                                <option value="{{ $option }}" @selected(($filters['per_page'] ?? 10) == $option)>
                                    {{ $option }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-funnel me-2"></i>Terapkan
                        </button>
                        <a href="{{ route('supply.material-stock.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-counterclockwise me-2"></i>Reset
                        </a>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col" style="width: 70px;">No.</th>
                                <th scope="col">SKU</th>
                                <th scope="col">Nama Material</th>
                                <th scope="col">Satuan</th>
                                <th scope="col" class="text-center">Stok Minimal</th>
                                <th scope="col" class="text-center">Total Diterima</th>
                                <th scope="col" class="text-center">Total Dikeluarkan</th>
                                <th scope="col" class="text-center">Stok Saat Ini</th>
                                <th scope="col" class="text-center">Status</th>
                                <th scope="col" class="text-end" style="width: 140px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $isPaginated = $materials instanceof Illuminate\Pagination\AbstractPaginator;
                            @endphp
                            @forelse ($materials as $material)
                                @php
                                    $iteration = $isPaginated ? $materials->firstItem() + $loop->index : $loop->iteration;
                                    $totalReceived = (float) ($material->total_received ?? 0);
                                    $totalIssued = (float) ($material->total_issued ?? 0);
                                    $currentStock = (float) ($material->current_stock ?? 0);
                                    $minStock = (float) ($material->min_stock ?? 0);
                                @endphp
                                <tr>
                                    <td>{{ $iteration }}</td>
                                    <td class="fw-semibold">{{ $material->sku }}</td>
                                    <td>
                                        <div class="fw-semibold">{{ $material->name }}</div>
                                        <div class="text-muted small">ID #{{ $material->id }}</div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            {{ optional($material->unit)->name ?? 'N/A' }}
                                        </span>
                                        <div class="text-muted small">{{ optional($material->unit)->symbol }}</div>
                                    </td>
                                    <td class="text-center">
                                        {{ number_format($minStock, 2, ',', '.') }}
                                    </td>
                                    <td class="text-center">
                                        {{ number_format($totalReceived, 2, ',', '.') }}
                                    </td>
                                    <td class="text-center">
                                        {{ number_format($totalIssued, 2, ',', '.') }}
                                    </td>
                                    <td class="text-center fw-semibold">
                                        {{ number_format($currentStock, 2, ',', '.') }}
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $material->stock_status_variant ?? 'secondary' }}">
                                            {{ $material->stock_status_label ?? 'Tidak diketahui' }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="{{ route('materials.show', $material) }}"
                                                class="btn btn-outline-secondary" title="Detail Material">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="{{ route('procurement.goods-receipts.create', ['material_id' => $material->id]) }}"
                                                class="btn btn-outline-success" title="Catat Penerimaan">
                                                <i class="bi bi-box-arrow-in-down"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center py-5">
                                        <i class="bi bi-inboxes display-5 text-muted d-block mb-3"></i>
                                        <p class="fw-semibold mb-1">Belum ada data stok</p>
                                        <p class="text-muted mb-3">Catat penerimaan atau pengeluaran material untuk melihat
                                            stok.
                                        </p>
                                        <a href="{{ route('procurement.goods-receipts.create') }}"
                                            class="btn btn-primary">
                                            <i class="bi bi-plus-lg me-2"></i>Catat Penerimaan Pertama
                                        </a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($materials instanceof Illuminate\Pagination\LengthAwarePaginator)
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3 pt-4">
                        <div class="text-muted small">
                            Menampilkan {{ $materials->firstItem() }} - {{ $materials->lastItem() }} dari
                            {{ $materials->total() }} data
                        </div>
                        {{ $materials->onEachSide(1)->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
