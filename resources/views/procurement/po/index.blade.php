@extends('layouts.app')

@section('content')
    <div class="container-fluid px-0 px-lg-3">
        <header
            class="d-flex flex-wrap flex-md-nowrap align-items-start align-items-md-center justify-content-between gap-3 mb-4">
            <div>
                <h1 class="fw-bold mb-1">Purchase Order</h1>
                <p class="text-muted mb-0">Kelola seluruh purchase order pengadaan dan pantau status pemesanan.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('procurement.purchase-orders.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-2"></i>Tambah Purchase Order
                </a>
            </div>
        </header>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
            </div>
        @endif

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <form action="{{ route('procurement.purchase-orders.index') }}" method="GET"
                    class="row g-3 align-items-end mb-4">
                    <div class="col-md-4">
                        <label for="search" class="form-label">Cari Purchase Order</label>
                        <input type="search" id="search" name="search" value="{{ request('search') }}"
                            class="form-control" placeholder="Cari kode, pemasok, atau proyek">
                    </div>

                    <div class="col-md-3">
                        <label for="status" class="form-label">Status</label>
                        <select id="status" name="status" class="form-select">
                            <option value="">Semua Status</option>
                            @foreach ($statuses as $value => $label)
                                <option value="{{ $value }}" @selected((string) $value === request('status'))>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="supplier_id" class="form-label">Pemasok</label>
                        <select id="supplier_id" name="supplier_id" class="form-select">
                            <option value="">Semua Pemasok</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" @selected((string) $supplier->id === request('supplier_id'))>
                                    {{ $supplier->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="project_id" class="form-label">Proyek</label>
                        <select id="project_id" name="project_id" class="form-select">
                            <option value="">Semua Proyek</option>
                            @foreach ($projects as $project)
                                <option value="{{ $project->id }}" @selected((string) $project->id === request('project_id'))>
                                    {{ $project->code }} &mdash; {{ $project->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="material_request_id" class="form-label">Permintaan Material</label>
                        <select id="material_request_id" name="material_request_id" class="form-select">
                            <option value="">Semua Permintaan</option>
                            @foreach ($materialRequests as $requestOption)
                                <option value="{{ $requestOption->id }}" @selected((string) $requestOption->id === request('material_request_id'))>
                                    {{ $requestOption->code }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="order_date" class="form-label">Tanggal Pemesanan</label>
                        <input type="date" id="order_date" name="order_date" value="{{ request('order_date') }}"
                            class="form-control">
                    </div>

                    <div class="col-md-2">
                        <label for="per_page" class="form-label">Per Halaman</label>
                        <select id="per_page" name="per_page" class="form-select">
                            @foreach ([10, 25, 50, 100] as $size)
                                <option value="{{ $size }}" @selected((string) $size === (string) request('per_page', 10))>
                                    {{ $size }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4 col-lg-3 d-flex gap-2">
                        <button type="submit" class="btn btn-outline-primary w-100">
                            <i class="bi bi-funnel me-2"></i>Terapkan
                        </button>
                        <a href="{{ route('procurement.purchase-orders.index') }}" class="btn btn-outline-secondary w-100">
                            <i class="bi bi-arrow-counterclockwise me-2"></i>Reset
                        </a>
                    </div>
                </form>

                @php
                    $statusClasses = [
                        'draft' => 'bg-secondary',
                        'approved' => 'bg-success',
                        'partial' => 'bg-warning text-dark',
                        'received' => 'bg-primary',
                        'canceled' => 'bg-danger',
                    ];
                    $isPaginated = $orders instanceof Illuminate\Pagination\AbstractPaginator;
                    $isLengthAware = $orders instanceof Illuminate\Pagination\LengthAwarePaginator;
                @endphp

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col" style="width: 70px;">No.</th>
                                <th scope="col">Kode</th>
                                <th scope="col">Pemasok</th>
                                <th scope="col">Proyek</th>
                                <th scope="col">Permintaan Material</th>
                                <th scope="col">Tanggal</th>
                                <th scope="col">Status</th>
                                <th scope="col" class="text-center">Item</th>
                                <th scope="col" class="text-end">Total</th>
                                <th scope="col" class="text-end" style="width: 150px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($orders as $order)
                                @php
                                    $statusLabel = $statuses[$order->status] ?? ucfirst($order->status ?? '');
                                    $badgeClass = $statusClasses[$order->status] ?? 'bg-secondary';
                                    $orderDate = optional($order->order_date)->format('d M Y');
                                    $totalFormatted = 'Rp ' . number_format($order->total ?? 0, 2, ',', '.');
                                @endphp
                                <tr>
                                    <td>
                                        {{ $isPaginated ? $orders->firstItem() + $loop->index : $loop->iteration }}
                                    </td>
                                    <td class="fw-semibold">
                                        <div>{{ $order->code }}</div>
                                        <div class="text-muted small">ID #{{ $order->id }}</div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ optional($order->supplier)->name ?? '-' }}</div>
                                        <div class="text-muted small">{{ optional($order->supplier)->email ?? '-' }}</div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ optional($order->project)->name ?? '-' }}</div>
                                        <div class="text-muted small">
                                            {{ optional($order->project)->code ?? 'Tidak ada kode' }}</div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ optional($order->materialRequest)->code ?? '-' }}
                                        </div>
                                        <div class="text-muted small">
                                            @if ($order->material_request_id)
                                                ID #{{ $order->material_request_id }}
                                            @else
                                                Tidak terhubung
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div>{{ $orderDate ?? '-' }}</div>
                                        <div class="text-muted small">{{ $order->created_at?->format('H:i') ?? '' }}</div>
                                    </td>
                                    <td>
                                        <span class="badge {{ $badgeClass }}">{{ $statusLabel }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="fw-semibold">{{ $order->items_count ?? 0 }}</span>
                                    </td>
                                    <td class="text-end fw-semibold">{{ $totalFormatted }}</td>
                                    <td class="text-end">
                                        <div class="btn-group" role="group" aria-label="Aksi Purchase Order">
                                            <a href="{{ route('procurement.purchase-orders.show', $order) }}"
                                                class="btn btn-sm btn-outline-secondary" title="Detail">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            @if ($order->status === 'draft')
                                                <a href="{{ route('procurement.purchase-orders.edit', $order) }}"
                                                    class="btn btn-sm btn-outline-primary" title="Ubah">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <form action="{{ route('procurement.purchase-orders.destroy', $order) }}"
                                                    method="POST" class="d-inline"
                                                    onsubmit="return confirm('Hapus purchase order ini?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                                        title="Hapus">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center text-muted py-5">
                                        <i class="bi bi-inboxes display-6 d-block mb-3"></i>
                                        Belum ada purchase order yang tercatat.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($isPaginated)
                    <div class="d-flex flex-column flex-lg-row align-items-center justify-content-between gap-2 mt-4">
                        @if ($isLengthAware)
                            <div class="text-muted small">
                                Menampilkan {{ $orders->firstItem() }} sampai {{ $orders->lastItem() }} dari
                                {{ $orders->total() }} data
                            </div>
                        @endif
                        {{ $orders->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
