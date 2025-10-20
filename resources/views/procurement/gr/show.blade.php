@extends('layouts.app')

@section('content')
    <div class="container-fluid px-0 px-lg-3">
        <header
            class="d-flex flex-wrap flex-md-nowrap align-items-start align-items-md-center justify-content-between gap-3 mb-4">
            <div>
                <h1 class="fw-bold mb-1">Detail Goods Receipt</h1>
                <p class="text-muted mb-0">Tinjau informasi penerimaan barang dan status pengecekan kualitas.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('procurement.goods-receipts.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Kembali
                </a>
                <a href="{{ route('procurement.goods-receipts.edit', $goodsReceipt) }}" class="btn btn-outline-primary">
                    <i class="bi bi-pencil me-2"></i>Ubah
                </a>
                <form action="{{ route('procurement.goods-receipts.destroy', $goodsReceipt) }}" method="POST"
                    onsubmit="return confirm('Hapus goods receipt ini?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger">
                        <i class="bi bi-trash me-2"></i>Hapus
                    </button>
                </form>
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
                            $statusClasses = [
                                'draft' => 'bg-secondary',
                                'in_progress' => 'bg-info text-dark',
                                'completed' => 'bg-success',
                                'returned' => 'bg-warning text-dark',
                            ];
                            $statusLabel = $statuses[$goodsReceipt->status] ?? ucfirst($goodsReceipt->status ?? '');
                            $badgeClass = $statusClasses[$goodsReceipt->status] ?? 'bg-secondary';
                            $purchaseOrder = $goodsReceipt->purchaseOrder ?? null;
                            $project = $goodsReceipt->project ?? optional($purchaseOrder)->project;
                            $supplier = $goodsReceipt->supplier ?? optional($purchaseOrder)->supplier;
                            $receiver = $goodsReceipt->receiver ?? null;
                            $verifier = $goodsReceipt->verifier ?? null;
                            $receivedDate = $goodsReceipt->received_date
                                ? \Illuminate\Support\Carbon::parse($goodsReceipt->received_date)->format('d M Y')
                                : null;
                            $verifiedAtDisplay = $goodsReceipt->verified_at
                                ? \Illuminate\Support\Carbon::parse($goodsReceipt->verified_at)->format('d M Y H:i')
                                : null;
                        @endphp
                        <dl class="row mb-0">
                            <dt class="col-sm-5 text-muted">Kode</dt>
                            <dd class="col-sm-7 fw-semibold">{{ $goodsReceipt->code }}</dd>

                            <dt class="col-sm-5 text-muted">Purchase Order</dt>
                            <dd class="col-sm-7">
                                <div class="fw-semibold">{{ optional($purchaseOrder)->code ?? '-' }}</div>
                                @if ($goodsReceipt->purchase_order_id)
                                    <div class="text-muted small">ID #{{ $goodsReceipt->purchase_order_id }}</div>
                                @endif
                            </dd>

                            <dt class="col-sm-5 text-muted">Proyek</dt>
                            <dd class="col-sm-7">
                                <div class="fw-semibold">{{ optional($project)->name ?? '-' }}</div>
                                <div class="text-muted small">{{ optional($project)->code ?? 'Tidak ada kode' }}</div>
                            </dd>

                            <dt class="col-sm-5 text-muted">Pemasok</dt>
                            <dd class="col-sm-7">
                                <div class="fw-semibold">{{ optional($supplier)->name ?? '-' }}</div>
                                <div class="text-muted small">{{ optional($supplier)->email ?? '-' }}</div>
                            </dd>

                            <dt class="col-sm-5 text-muted">Tanggal Penerimaan</dt>
                            <dd class="col-sm-7">{{ $receivedDate ?? '-' }}</dd>

                            <dt class="col-sm-5 text-muted">Status</dt>
                            <dd class="col-sm-7">
                                <span class="badge {{ $badgeClass }}">{{ $statusLabel }}</span>
                            </dd>

                            <dt class="col-sm-5 text-muted">Diterima Oleh</dt>
                            <dd class="col-sm-7">
                                <div class="fw-semibold">{{ optional($receiver)->name ?? 'Tidak diketahui' }}</div>
                                <div class="text-muted small">{{ optional($receiver)->email ?? '-' }}</div>
                            </dd>

                            <dt class="col-sm-5 text-muted">Diverifikasi Oleh</dt>
                            <dd class="col-sm-7">
                                @if ($goodsReceipt->verified_by)
                                    <div class="fw-semibold">{{ optional($verifier)->name ?? 'Tidak diketahui' }}</div>
                                    <div class="text-muted small">{{ optional($verifier)->email ?? '-' }}</div>
                                @else
                                    <span class="text-muted">Belum diverifikasi</span>
                                @endif
                            </dd>

                            <dt class="col-sm-5 text-muted">Tanggal Verifikasi</dt>
                            <dd class="col-sm-7">{{ $verifiedAtDisplay ?? '-' }}</dd>

                            <dt class="col-sm-5 text-muted">Catatan</dt>
                            <dd class="col-sm-7">
                                @if (!empty($goodsReceipt->remarks))
                                    <div>{!! nl2br(e($goodsReceipt->remarks)) !!}</div>
                                @else
                                    <span class="text-muted">Tidak ada catatan</span>
                                @endif
                            </dd>

                            <dt class="col-sm-5 text-muted">Dibuat</dt>
                            <dd class="col-sm-7 text-muted">
                                {{ $goodsReceipt->created_at?->format('d M Y H:i') ?? '-' }}
                            </dd>

                            <dt class="col-sm-5 text-muted">Diperbarui</dt>
                            <dd class="col-sm-7 text-muted">
                                {{ $goodsReceipt->updated_at?->format('d M Y H:i') ?? '-' }}
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <h2 class="h5 fw-semibold mb-3">Item Penerimaan</h2>
                        @php
                            $itemsCollection = collect($goodsReceipt->items ?? []);
                            $totalOrderedQty = $itemsCollection->sum(function ($item) {
                                $ordered = optional(optional($item)->purchaseOrderItem)->qty;
                                return (float) ($ordered ?? 0);
                            });
                            $totalReceivedQty = $itemsCollection->sum(function ($item) {
                                return (float) ($item->qty ?? 0);
                            });
                            $totalReturnedQty = $itemsCollection->sum(function ($item) {
                                return (float) ($item->returned_qty ?? 0);
                            });
                        @endphp
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Material</th>
                                        <th style="width: 120px;" class="text-end">Jumlah PO</th>
                                        <th style="width: 120px;" class="text-end">Diterima</th>
                                        <th style="width: 120px;" class="text-end">Diretur</th>
                                        <th>Catatan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($itemsCollection as $item)
                                        @php
                                            $material = $item->material ?? null;
                                            $unitLabel = $material && $material->unit
                                                ? ($material->unit->symbol ?? $material->unit->name)
                                                : null;
                                            $orderedQty = optional($item->purchaseOrderItem)->qty;
                                            $receivedQty = $item->qty ?? 0;
                                            $returnedQty = $item->returned_qty ?? 0;
                                        @endphp
                                        <tr>
                                            <td>
                                                <div class="fw-semibold">{{ $material->name ?? 'Material tidak tersedia' }}</div>
                                                @if ($unitLabel)
                                                    <div class="text-muted small">Satuan: {{ $unitLabel }}</div>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                {{ $orderedQty !== null ? number_format((float) $orderedQty, 2, ',', '.') : '-' }}
                                            </td>
                                            <td class="text-end fw-semibold">
                                                {{ number_format((float) $receivedQty, 2, ',', '.') }}
                                            </td>
                                            <td class="text-end">
                                                {{ number_format((float) $returnedQty, 2, ',', '.') }}
                                            </td>
                                            <td>
                                                {{ $item->remarks ?? '-' }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">
                                                <i class="bi bi-inboxes me-2"></i>Belum ada item pada goods receipt ini.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                @if ($itemsCollection->isNotEmpty())
                                    <tfoot class="table-light">
                                        <tr>
                                            <th class="text-end">Total</th>
                                            <th class="text-end">
                                                {{ $totalOrderedQty > 0 ? number_format($totalOrderedQty, 2, ',', '.') : '-' }}
                                            </th>
                                            <th class="text-end fw-bold">
                                                {{ number_format($totalReceivedQty, 2, ',', '.') }}
                                            </th>
                                            <th class="text-end">
                                                {{ number_format($totalReturnedQty, 2, ',', '.') }}
                                            </th>
                                            <th></th>
                                        </tr>
                                    </tfoot>
                                @endif
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
