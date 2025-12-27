@extends('layouts.app')

@section('content')
    <div class="container-fluid px-0 px-lg-3">
        <header
            class="d-flex flex-wrap flex-md-nowrap align-items-start align-items-md-center justify-content-between gap-3 mb-4">
            <div>
                <h1 class="fw-bold mb-1">Detail Goods Issue</h1>
                <p class="text-muted mb-0">Tinjau pengeluaran material beserta status dan riwayat item.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('procurement.goods-issues.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Kembali
                </a>
                @if (auth()->user()->role->role_name === 'admin')
                    <a href="{{ route('procurement.goods-issues.edit', $goodsIssue) }}" class="btn btn-outline-primary">
                        <i class="bi bi-pencil me-2"></i>Ubah
                    </a>
                    <form action="{{ route('procurement.goods-issues.destroy', $goodsIssue) }}" method="POST"
                        onsubmit="return confirm('Hapus goods issue ini?');">
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

        @php
            $statusClasses = [
                'draft' => 'bg-secondary',
                'issued' => 'bg-info text-dark',
                'returned' => 'bg-warning text-dark',
            ];
            $statusLabel = $statuses[$goodsIssue->status] ?? ucfirst($goodsIssue->status ?? '');
            $badgeClass = $statusClasses[$goodsIssue->status] ?? 'bg-secondary';
            $issuedDate = optional($goodsIssue->issued_date)->format('d M Y');
        @endphp

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4 p-lg-5">
                <dl class="row mb-0 g-4">
                    <div class="col-md-4">
                        <dt class="text-muted">Kode Goods Issue</dt>
                        <dd class="fs-5 fw-semibold">{{ $goodsIssue->code }}</dd>
                    </div>
                    <div class="col-md-4">
                        <dt class="text-muted">Proyek</dt>
                        <dd class="fs-5 fw-semibold">
                            {{ optional($goodsIssue->project)->name ?? 'Tidak ada proyek' }}
                            <div class="text-muted small">{{ optional($goodsIssue->project)->code ?? '-' }}</div>
                        </dd>
                    </div>
                    <div class="col-md-4">
                        <dt class="text-muted">Status</dt>
                        <dd>
                            <span class="badge {{ $badgeClass }} fs-6">{{ $statusLabel }}</span>
                        </dd>
                    </div>
                    <div class="col-md-4">
                        <dt class="text-muted">Tanggal Pengeluaran</dt>
                        <dd>
                            <div class="fs-5">{{ $issuedDate ?? 'Belum ditentukan' }}</div>
                            <div class="text-muted small">{{ optional($goodsIssue->created_at)->format('H:i') }} WITA</div>
                        </dd>
                    </div>
                    <div class="col-md-4">
                        <dt class="text-muted">Petugas</dt>
                        <dd>
                            <div class="fs-5 fw-semibold">{{ optional($goodsIssue->issuer)->name ?? 'Tidak diketahui' }}
                            </div>
                            <div class="text-muted small">{{ optional($goodsIssue->issuer)->email ?? '-' }}</div>
                        </dd>
                    </div>
                    <div class="col-12">
                        <dt class="text-muted">Catatan</dt>
                        <dd class="fs-5">
                            @if ($goodsIssue->remarks)
                                {{ $goodsIssue->remarks }}
                            @else
                                <span class="text-muted">Tidak ada catatan tambahan.</span>
                            @endif
                        </dd>
                    </div>
                    <div class="col-md-6">
                        <dt class="text-muted">Dibuat</dt>
                        <dd>
                            <div>{{ optional($goodsIssue->created_at)->format('d M Y') }}</div>
                            <div class="text-muted small">{{ optional($goodsIssue->created_at)->format('H:i') }} WITA</div>
                        </dd>
                    </div>
                    <div class="col-md-6">
                        <dt class="text-muted">Diperbarui Terakhir</dt>
                        <dd>
                            <div>{{ optional($goodsIssue->updated_at)->format('d M Y') }}</div>
                            <div class="text-muted small">{{ optional($goodsIssue->updated_at)->format('H:i') }} WITA</div>
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h2 class="h5 mb-0">Daftar Material Dikeluarkan</h2>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 70px;">No.</th>
                                <th>Material</th>
                                <th style="width: 120px;" class="text-end">Jumlah</th>
                                <th style="width: 150px;">Satuan</th>
                                <th>Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($goodsIssue->items as $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td class="fw-semibold">
                                        {{ optional($item->material)->name ?? 'Material tidak ditemukan' }}
                                        <div class="text-muted small">ID #{{ $item->material_id }}</div>
                                    </td>
                                    <td class="text-end fw-semibold">
                                        {{ number_format($item->qty, 2, ',', '.') }}
                                    </td>
                                    <td>
                                        {{ optional($item->material?->unit)->symbol ?? (optional($item->material?->unit)->name ?? '-') }}
                                    </td>
                                    <td>{{ $item->remarks ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-5">
                                        <i class="bi bi-box text-muted display-5 d-block mb-3"></i>
                                        <p class="mb-1 fw-semibold">Tidak ada material tercatat</p>
                                        <p class="text-muted mb-0">Goods issue ini belum memiliki detail material.</p>
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
