@extends('layouts.app')

@section('content')
    <div class="container-fluid px-0 px-lg-3">
        <header
            class="d-flex flex-wrap flex-md-nowrap align-items-start align-items-md-center justify-content-between gap-3 mb-4">
            <div>
                <h1 class="fw-bold mb-1">Detail Material</h1>
                <p class="text-muted mb-0">Informasi lengkap material untuk referensi dan pengelolaan data.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('materials.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Kembali
                </a>
                <a href="{{ route('materials.edit', $material) }}" class="btn btn-outline-primary">
                    <i class="bi bi-pencil me-2"></i>Ubah
                </a>
                <form action="{{ route('materials.destroy', $material) }}" method="POST"
                    onsubmit="return confirm('Hapus material ini?');">
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

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4 p-lg-5">
                <dl class="row mb-0 g-4">
                    <div class="col-md-6">
                        <dt class="text-muted">SKU</dt>
                        <dd class="fs-5 fw-semibold">{{ $material->sku }}</dd>
                    </div>
                    <div class="col-md-6">
                        <dt class="text-muted">Nama Material</dt>
                        <dd class="fs-5 fw-semibold">{{ $material->name }}</dd>
                    </div>
                    <div class="col-md-6">
                        <dt class="text-muted">Satuan</dt>
                        <dd>
                            <span class="badge bg-light text-dark border">{{ optional($material->unit)->name ?? 'Tidak diketahui' }}</span>
                            <span class="text-muted ms-2">{{ optional($material->unit)->symbol }}</span>
                        </dd>
                    </div>
                    <div class="col-md-6">
                        <dt class="text-muted">Stok Minimal</dt>
                        <dd class="fs-5 fw-semibold">
                            {{ number_format($material->min_stock ?? 0, 2, ',', '.') }}
                            <span class="fs-6 text-muted">{{ optional($material->unit)->symbol }}</span>
                        </dd>
                    </div>
                    <div class="col-md-6">
                        <dt class="text-muted">Dibuat</dt>
                        <dd>
                            <div>{{ optional($material->created_at)->format('d M Y') }}</div>
                            <div class="text-muted small">{{ optional($material->created_at)->format('H:i') }} WIB</div>
                        </dd>
                    </div>
                    <div class="col-md-6">
                        <dt class="text-muted">Diperbarui Terakhir</dt>
                        <dd>
                            <div>{{ optional($material->updated_at)->format('d M Y') }}</div>
                            <div class="text-muted small">{{ optional($material->updated_at)->format('H:i') }} WIB</div>
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
@endsection
