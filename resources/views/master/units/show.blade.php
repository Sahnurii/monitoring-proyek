@extends('layouts.app')

@section('content')
    <div class="container-fluid px-0 px-lg-3">
        <header
            class="d-flex flex-wrap flex-md-nowrap align-items-start align-items-md-center justify-content-between gap-3 mb-4">
            <div>
                <h1 class="fw-bold mb-1">Detail Satuan</h1>
                <p class="text-muted mb-0">Informasi lengkap satuan untuk referensi material.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('units.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Kembali
                </a>
                <a href="{{ route('units.edit', $unit) }}" class="btn btn-outline-primary">
                    <i class="bi bi-pencil me-2"></i>Ubah
                </a>
                <form action="{{ route('units.destroy', $unit) }}" method="POST"
                    onsubmit="return confirm('Hapus satuan ini?');">
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
                        <dt class="text-muted">Nama Satuan</dt>
                        <dd class="fs-5 fw-semibold">{{ $unit->name }}</dd>
                    </div>
                    <div class="col-md-6">
                        <dt class="text-muted">Simbol</dt>
                        <dd>
                            <span class="badge bg-light text-dark border fs-6">{{ $unit->symbol }}</span>
                        </dd>
                    </div>
                    <div class="col-md-6">
                        <dt class="text-muted">Dibuat</dt>
                        <dd>
                            <div>{{ optional($unit->created_at)->format('d M Y') }}</div>
                            <div class="text-muted small">{{ optional($unit->created_at)->format('H:i') }} WITA</div>
                        </dd>
                    </div>
                    <div class="col-md-6">
                        <dt class="text-muted">Diperbarui Terakhir</dt>
                        <dd>
                            <div>{{ optional($unit->updated_at)->format('d M Y') }}</div>
                            <div class="text-muted small">{{ optional($unit->updated_at)->format('H:i') }} WITA</div>
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
@endsection
