@extends('layouts.app')

@section('content')
    <div class="container-fluid px-0 px-lg-3">
        <header
            class="d-flex flex-wrap flex-md-nowrap align-items-start align-items-md-center justify-content-between gap-3 mb-4">
            <div>
                <h1 class="fw-bold mb-1">Penerimaan Barang</h1>
                <p class="text-muted mb-0">Monitor dan kelola seluruh penerimaan barang dari pemasok.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('procurement.goods-receipts.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-2"></i>Tambah Goods Receipt
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
                <form action="{{ route('procurement.goods-receipts.index') }}" method="GET"
                    class="row g-3 align-items-end mb-4">
                    <div class="col-md-4">
                        <label for="search" class="form-label">Cari Goods Receipt</label>
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
                        <label for="purchase_order_id" class="form-label">Purchase Order</label>
                        <select id="purchase_order_id" name="purchase_order_id" class="form-select">
                            <option value="">Semua Purchase Order</option>
                            @foreach ($purchaseOrders as $order)
                                <option value="{{ $order->id }}"
                                    @selected((string) $order->id === request('purchase_order_id'))>
                                    {{ $order->code }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="project_id" class="form-label">Proyek</label>
                        <select id="project_id" name="project_id" class="form-select">
                            <option value="">Semua Proyek</option>
                            @foreach ($projects as $project)
                                <option value="{{ $project->id }}"
                                    @selected((string) $project->id === request('project_id'))>
                                    {{ $project->code }} &mdash; {{ $project->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="supplier_id" class="form-label">Pemasok</label>
                        <select id="supplier_id" name="supplier_id" class="form-select">
                            <option value="">Semua Pemasok</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}"
                                    @selected((string) $supplier->id === request('supplier_id'))>
                                    {{ $supplier->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="received_by" class="form-label">Penerima</label>
                        <select id="received_by" name="received_by" class="form-select">
                            <option value="">Semua Penerima</option>
                            @foreach ($receivers as $receiver)
                                <option value="{{ $receiver->id }}"
                                    @selected((string) $receiver->id === request('received_by'))>
                                    {{ $receiver->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="received_date" class="form-label">Tanggal Penerimaan</label>
                        <input type="date" id="received_date" name="received_date"
                            value="{{ request('received_date') }}" class="form-control">
                    </div>

                    <div class="col-md-2">
                        <label for="per_page" class="form-label">Per Halaman</label>
                        <select id="per_page" name="per_page" class="form-select">
                            @foreach ([10, 25, 50, 100] as $size)
                                <option value="{{ $size }}"
                                    @selected((string) $size === (string) request('per_page', 10))>
                                    {{ $size }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4 col-lg-3 d-flex gap-2">
                        <button type="submit" class="btn btn-outline-primary w-100">
                            <i class="bi bi-funnel me-2"></i>Terapkan
                        </button>
                        <a href="{{ route('procurement.goods-receipts.index') }}"
                            class="btn btn-outline-secondary w-100">
                            <i class="bi bi-arrow-counterclockwise me-2"></i>Reset
                        </a>
                    </div>
                </form>

                @php
                    $statusClasses = [
                        'draft' => 'bg-secondary',
                        'in_progress' => 'bg-info text-dark',
                        'completed' => 'bg-success',
                        'returned' => 'bg-warning text-dark',
                    ];
                    $isPaginated = $receipts instanceof Illuminate\Pagination\AbstractPaginator;
                    $isLengthAware = $receipts instanceof Illuminate\Pagination\LengthAwarePaginator;
                @endphp

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col" style="width: 70px;">No.</th>
                                <th scope="col">Kode</th>
                                <th scope="col">Purchase Order</th>
                                <th scope="col">Proyek</th>
                                <th scope="col">Pemasok</th>
                                <th scope="col">Penerima</th>
                                <th scope="col">Tanggal</th>
                                <th scope="col">Status</th>
                                <th scope="col" class="text-center">Item</th>
                                <th scope="col" class="text-end" style="width: 150px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($receipts as $receipt)
                                @php
                                    $statusLabel = $statuses[$receipt->status] ?? ucfirst($receipt->status ?? '');
                                    $badgeClass = $statusClasses[$receipt->status] ?? 'bg-secondary';
                                    $project = $receipt->project ?? optional($receipt->purchaseOrder)->project;
                                    $supplier = $receipt->supplier ?? optional($receipt->purchaseOrder)->supplier;
                                    $receivedDate = $receipt->received_date
                                        ? \Illuminate\Support\Carbon::parse($receipt->received_date)->format('d M Y')
                                        : null;
                                @endphp
                                <tr>
                                    <td>
                                        {{ $isPaginated ? $receipts->firstItem() + $loop->index : $loop->iteration }}
                                    </td>
                                    <td class="fw-semibold">
                                        <div>{{ $receipt->code }}</div>
                                        <div class="text-muted small">ID #{{ $receipt->id }}</div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ optional($receipt->purchaseOrder)->code ?? '-' }}</div>
                                        @if ($receipt->purchase_order_id)
                                            <div class="text-muted small">ID #{{ $receipt->purchase_order_id }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ optional($project)->name ?? '-' }}</div>
                                        <div class="text-muted small">{{ optional($project)->code ?? 'Tidak ada kode' }}</div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ optional($supplier)->name ?? '-' }}</div>
                                        <div class="text-muted small">{{ optional($supplier)->email ?? '-' }}</div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ optional($receipt->receiver)->name ?? 'Tidak diketahui' }}</div>
                                        <div class="text-muted small">{{ optional($receipt->receiver)->email ?? '-' }}</div>
                                    </td>
                                    <td>
                                        <div>{{ $receivedDate ?? '-' }}</div>
                                        <div class="text-muted small">{{ $receipt->created_at?->format('H:i') ?? '' }}</div>
                                    </td>
                                    <td>
                                        <span class="badge {{ $badgeClass }}">{{ $statusLabel }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="fw-semibold">{{ $receipt->items_count ?? 0 }}</span>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group" role="group" aria-label="Aksi Goods Receipt">
                                            <a href="{{ route('procurement.goods-receipts.show', $receipt) }}"
                                                class="btn btn-sm btn-outline-secondary" title="Detail">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="{{ route('procurement.goods-receipts.edit', $receipt) }}"
                                                class="btn btn-sm btn-outline-primary" title="Ubah">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <form action="{{ route('procurement.goods-receipts.destroy', $receipt) }}"
                                                method="POST" class="d-inline"
                                                onsubmit="return confirm('Hapus goods receipt ini?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center py-5">
                                        <i class="bi bi-box-seam text-muted display-5 d-block mb-3"></i>
                                        <p class="mb-1 fw-semibold">Belum ada goods receipt</p>
                                        <p class="text-muted mb-3">Tambahkan penerimaan barang untuk mulai mencatat stok masuk.</p>
                                        <a href="{{ route('procurement.goods-receipts.create') }}" class="btn btn-primary">
                                            <i class="bi bi-plus-lg me-2"></i>Tambah Goods Receipt
                                        </a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($isPaginated)
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3 pt-4">
                        @if ($isLengthAware)
                            <div class="text-muted small">
                                Menampilkan {{ $receipts->firstItem() }} - {{ $receipts->lastItem() }} dari
                                {{ $receipts->total() }} goods receipt
                            </div>
                        @else
                            <div class="text-muted small">
                                Total goods receipt ditampilkan: {{ $receipts->count() }}
                            </div>
                        @endif

                        {{ $receipts->onEachSide(1)->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
