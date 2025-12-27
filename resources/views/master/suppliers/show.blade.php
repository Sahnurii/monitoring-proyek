@extends('layouts.app')

@section('content')
    <div class="container-fluid px-0 px-lg-3">
        <header
            class="d-flex flex-wrap flex-md-nowrap align-items-start align-items-md-center justify-content-between gap-3 mb-4">
            <div>
                <h1 class="fw-bold mb-1">Detail Supplier</h1>
                <p class="text-muted mb-0">Informasi lengkap pemasok untuk referensi pengadaan dan evaluasi.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Kembali
                </a>
                <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-outline-primary">
                    <i class="bi bi-pencil me-2"></i>Ubah
                </a>
                <form action="{{ route('suppliers.destroy', $supplier) }}" method="POST"
                    onsubmit="return confirm('Hapus supplier ini?');">
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
                        <dt class="text-muted">Nama Supplier</dt>
                        <dd class="fs-5 fw-semibold">{{ $supplier->name }}</dd>
                    </div>
                    <div class="col-md-6">
                        <dt class="text-muted">NPWP</dt>
                        <dd>
                            @if ($supplier->npwp)
                                <span class="badge bg-light text-dark border">{{ $supplier->npwp }}</span>
                            @else
                                <span class="text-muted">Belum diisi</span>
                            @endif
                        </dd>
                    </div>
                    <div class="col-md-6">
                        <dt class="text-muted">Email</dt>
                        <dd class="fs-5">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-envelope text-muted"></i>
                                <span>{{ $supplier->email ?? 'Belum diisi' }}</span>
                            </div>
                        </dd>
                    </div>
                    <div class="col-md-6">
                        <dt class="text-muted">Nomor Telepon</dt>
                        <dd class="fs-5">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-telephone text-muted"></i>
                                <span>{{ $supplier->phone ?? 'Belum diisi' }}</span>
                            </div>
                        </dd>
                    </div>
                    <div class="col-md-6">
                        <dt class="text-muted">Rating</dt>
                        <dd>
                            @php
                                $ratingValue = (int) $supplier->rating;
                                $clampedRating = max(0, min($ratingValue, 5));
                            @endphp
                            <div class="text-warning fs-5">
                                @for ($i = 0; $i < $clampedRating; $i++)
                                    <i class="bi bi-star-fill"></i>
                                @endfor
                                @for ($i = $clampedRating; $i < 5; $i++)
                                    <i class="bi bi-star"></i>
                                @endfor
                            </div>
                            <div class="text-muted small">{{ $ratingValue }}/5</div>
                        </dd>
                    </div>
                    <div class="col-md-6">
                        <dt class="text-muted">Alamat</dt>
                        <dd>
                            <div>{{ $supplier->address ?? 'Belum diisi' }}</div>
                        </dd>
                    </div>
                    <div class="col-md-6">
                        <dt class="text-muted">Dibuat</dt>
                        <dd>
                            <div>{{ optional($supplier->created_at)->format('d M Y') }}</div>
                            <div class="text-muted small">{{ optional($supplier->created_at)->format('H:i') }} WITA</div>
                        </dd>
                    </div>
                    <div class="col-md-6">
                        <dt class="text-muted">Diperbarui Terakhir</dt>
                        <dd>
                            <div>{{ optional($supplier->updated_at)->format('d M Y') }}</div>
                            <div class="text-muted small">{{ optional($supplier->updated_at)->format('H:i') }} WITA</div>
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
@endsection
