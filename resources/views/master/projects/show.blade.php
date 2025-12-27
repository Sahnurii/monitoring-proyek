@extends('layouts.app')

@section('content')
    <div class="container-fluid px-0 px-lg-3">
        <header
            class="d-flex flex-wrap flex-md-nowrap align-items-start align-items-md-center justify-content-between gap-3 mb-4">
            <div>
                <h1 class="fw-bold mb-1">Detail Proyek</h1>
                <p class="text-muted mb-0">Tinjau ringkasan proyek beserta status dan jadwalnya.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('projects.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Kembali
                </a>
                <a href="{{ route('projects.edit', $project) }}" class="btn btn-outline-primary">
                    <i class="bi bi-pencil me-2"></i>Ubah
                </a>
                <form action="{{ route('projects.destroy', $project) }}" method="POST"
                    onsubmit="return confirm('Hapus proyek ini?');">
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

        @php
            $statusClasses = [
                'planned' => 'bg-secondary',
                'ongoing' => 'bg-primary',
                'done' => 'bg-success',
                'archived' => 'bg-dark',
            ];
            $statusLabel = $statuses[$project->status] ?? ucfirst($project->status ?? '');
            $badgeClass = $statusClasses[$project->status] ?? 'bg-secondary';
        @endphp

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4 p-lg-5">
                <dl class="row mb-0 g-4">
                    <div class="col-md-6">
                        <dt class="text-muted">Kode Proyek</dt>
                        <dd class="fs-5 fw-semibold">{{ $project->code }}</dd>
                    </div>
                    <div class="col-md-6">
                        <dt class="text-muted">Status</dt>
                        <dd>
                            <span class="badge {{ $badgeClass }} fs-6">{{ $statusLabel }}</span>
                        </dd>
                    </div>
                    <div class="col-md-6">
                        <dt class="text-muted">Nama Proyek</dt>
                        <dd class="fs-5 fw-semibold">{{ $project->name }}</dd>
                    </div>
                    <div class="col-md-6">
                        <dt class="text-muted">Klien</dt>
                        <dd class="fs-5">{{ $project->client ?? 'Belum ditentukan' }}</dd>
                    </div>
                    <div class="col-md-6">
                        <dt class="text-muted">Lokasi</dt>
                        <dd class="fs-5">{{ $project->location ?? 'Belum ditentukan' }}</dd>
                    </div>
                    <div class="col-md-6">
                        <dt class="text-muted">Anggaran</dt>
                        <dd class="fs-5 fw-semibold">
                            @if (!is_null($project->budget))
                                Rp {{ number_format($project->budget, 2, ',', '.') }}
                            @else
                                <span class="text-muted">Belum ditentukan</span>
                            @endif
                        </dd>
                    </div>
                    <div class="col-md-6">
                        <dt class="text-muted">Tanggal Mulai</dt>
                        <dd>
                            <div>{{ optional($project->start_date)->format('d M Y') ?? 'Belum ditetapkan' }}</div>
                        </dd>
                    </div>
                    <div class="col-md-6">
                        <dt class="text-muted">Tanggal Selesai</dt>
                        <dd>
                            <div>{{ optional($project->end_date)->format('d M Y') ?? 'Belum ditetapkan' }}</div>
                        </dd>
                    </div>
                    <div class="col-md-6">
                        <dt class="text-muted">Dibuat</dt>
                        <dd>
                            <div>{{ optional($project->created_at)->format('d M Y') }}</div>
                            <div class="text-muted small">{{ optional($project->created_at)->format('H:i') }} WITA</div>
                        </dd>
                    </div>
                    <div class="col-md-6">
                        <dt class="text-muted">Diperbarui Terakhir</dt>
                        <dd>
                            <div>{{ optional($project->updated_at)->format('d M Y') }}</div>
                            <div class="text-muted small">{{ optional($project->updated_at)->format('H:i') }} WITA</div>
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
@endsection
