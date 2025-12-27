@extends('layouts.app')

@section('content')
    <div class="container-fluid px-0 px-lg-3">
        <header
            class="d-flex flex-wrap flex-md-nowrap align-items-start align-items-md-center justify-content-between gap-3 mb-4">
            <div>
                <h1 class="fw-bold mb-1">Detail Purchase Order</h1>
                <p class="text-muted mb-0">Tinjau informasi lengkap purchase order dan status pemenuhan.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('procurement.purchase-orders.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Kembali
                </a>
                @if ($purchaseOrder->status === 'draft' && optional(auth()->user()->role)->role_name === 'admin')
                    <form action="{{ route('procurement.purchase-orders.mark-ordered', $purchaseOrder) }}" method="POST"
                        class="d-inline">
                        @csrf
                        @method('PATCH')
                        <button class="btn btn-success">
                            <i class="bi bi-check2-circle me-1"></i>Konfirmasi & Pesan
                        </button>
                    </form>
                @endif
                @if ($purchaseOrder->status === 'approved')
                    <a href="{{ route('procurement.goods-receipts.create', $purchaseOrder) }}" class="btn btn-primary">
                        <i class="bi bi-box-seam me-1"></i>Terima Barang
                    </a>
                @endif

                @if ($purchaseOrder->status === 'draft' && optional(auth()->user()->role)->role_name === 'admin')
                    <a href="{{ route('procurement.purchase-orders.edit', $purchaseOrder) }}"
                        class="btn btn-outline-primary">
                        <i class="bi bi-pencil me-2"></i>Ubah
                    </a>

                    <form action="{{ route('procurement.purchase-orders.destroy', $purchaseOrder) }}" method="POST"
                        class="d-inline" onsubmit="return confirm('Hapus purchase order ini?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger">
                            <i class="bi bi-trash me-2"></i>Hapus
                        </button>
                    </form>
                @endif
            </div>
        </header>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
            </div>
        @endif

        <div class="row g-4">
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <h2 class="h5 fw-semibold mb-3">Informasi Umum</h2>
                        @php
                            $statusLabel = $statuses[$purchaseOrder->status] ?? ucfirst($purchaseOrder->status ?? '');
                            $statusClasses = [
                                'draft' => 'bg-secondary',
                                'approved' => 'bg-success',
                                'partial' => 'bg-warning text-dark',
                                'received' => 'bg-primary',
                                'canceled' => 'bg-danger',
                            ];
                            $badgeClass = $statusClasses[$purchaseOrder->status] ?? 'bg-secondary';
                        @endphp
                        <dl class="row mb-0">
                            <dt class="col-sm-5 text-muted">Kode</dt>
                            <dd class="col-sm-7 fw-semibold">{{ $purchaseOrder->code }}</dd>

                            <dt class="col-sm-5 text-muted">Pemasok</dt>
                            <dd class="col-sm-7">
                                <div class="fw-semibold">{{ optional($purchaseOrder->supplier)->name ?? '-' }}</div>
                                <div class="text-muted small">{{ optional($purchaseOrder->supplier)->email ?? '-' }}</div>
                            </dd>

                            <dt class="col-sm-5 text-muted">Proyek</dt>
                            <dd class="col-sm-7">
                                <div class="fw-semibold">{{ optional($purchaseOrder->project)->name ?? '-' }}</div>
                                <div class="text-muted small">
                                    {{ optional($purchaseOrder->project)->code ?? 'Tidak ada kode' }}</div>
                            </dd>

                            <dt class="col-sm-5 text-muted">Permintaan Material</dt>
                            <dd class="col-sm-7">
                                <div class="fw-semibold">{{ optional($purchaseOrder->materialRequest)->code ?? '-' }}</div>
                                @if ($purchaseOrder->material_request_id)
                                    <div class="text-muted small">ID #{{ $purchaseOrder->material_request_id }}</div>
                                @endif
                            </dd>

                            <dt class="col-sm-5 text-muted">Tanggal Pemesanan</dt>
                            <dd class="col-sm-7">{{ optional($purchaseOrder->order_date)->format('d M Y') ?? '-' }}</dd>

                            <dt class="col-sm-5 text-muted">Status</dt>
                            <dd class="col-sm-7">
                                <span class="badge {{ $badgeClass }}">{{ $statusLabel }}</span>
                            </dd>

                            <dt class="col-sm-5 text-muted">Total</dt>
                            <dd class="col-sm-7 fw-semibold">
                                Rp {{ number_format($purchaseOrder->total ?? 0, 2, ',', '.') }}
                            </dd>

                            <dt class="col-sm-5 text-muted">Disetujui Oleh</dt>
                            <dd class="col-sm-7">
                                @if ($purchaseOrder->approved_by)
                                    <div class="fw-semibold">
                                        {{ optional($purchaseOrder->approver)->name ?? 'Tidak diketahui' }}</div>
                                    <div class="text-muted small">
                                        {{ $purchaseOrder->approved_at?->format('d M Y H:i') ?? '-' }}
                                    </div>
                                @else
                                    <span class="text-muted">Belum disetujui</span>
                                @endif
                            </dd>

                            <dt class="col-sm-5 text-muted">Dibuat</dt>
                            <dd class="col-sm-7 text-muted">
                                {{ $purchaseOrder->created_at?->format('d M Y H:i') ?? '-' }}
                            </dd>

                            <dt class="col-sm-5 text-muted">Diperbarui</dt>
                            <dd class="col-sm-7 text-muted">
                                {{ $purchaseOrder->updated_at?->format('d M Y H:i') ?? '-' }}
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <h2 class="h5 fw-semibold mb-3">Item Purchase Order</h2>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Material</th>
                                        <th style="width: 120px;" class="text-end">Jumlah</th>
                                        <th style="width: 150px;" class="text-end">Harga Satuan</th>
                                        <th style="width: 150px;" class="text-end">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($purchaseOrder->items as $item)
                                        @php
                                            $material = $item->material;
                                            $unitLabel =
                                                $material && $material->unit
                                                    ? $material->unit->symbol ?? $material->unit->name
                                                    : null;
                                        @endphp
                                        <tr>
                                            <td>
                                                <div class="fw-semibold">{{ $material->name ?? 'Material tidak tersedia' }}
                                                </div>
                                                @if ($unitLabel)
                                                    <div class="text-muted small">Satuan: {{ $unitLabel }}</div>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                {{ number_format((float) $item->qty, 2, ',', '.') }}
                                            </td>
                                            <td class="text-end">
                                                Rp {{ number_format((float) $item->price, 2, ',', '.') }}
                                            </td>
                                            <td class="text-end fw-semibold">
                                                Rp {{ number_format((float) $item->subtotal, 2, ',', '.') }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">
                                                <i class="bi bi-inboxes me-2"></i>Belum ada item pada purchase order ini.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="3" class="text-end">Total</th>
                                        <th class="text-end fw-bold">
                                            Rp {{ number_format($purchaseOrder->total ?? 0, 2, ',', '.') }}
                                        </th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
