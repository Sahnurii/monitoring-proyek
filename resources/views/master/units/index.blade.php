@extends('layouts.app')

@section('content')
    <div class="container-fluid px-0 px-lg-3">
        <header
            class="d-flex flex-wrap flex-md-nowrap align-items-start align-items-md-center justify-content-between gap-3 mb-4">
            <div>
                <h1 class="fw-bold mb-1">Data Satuan</h1>
                <p class="text-muted mb-0">Kelola daftar satuan yang digunakan di proyek Anda.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('units.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-2"></i>Tambah Satuan
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
                <form action="{{ route('units.index') }}" method="GET" class="row g-3 align-items-end mb-4">
                    <div class="col-md-6 col-lg-4">
                        <label for="search" class="form-label">Cari Satuan</label>
                        <input type="search" id="search" name="search" value="{{ request('search') }}"
                            class="form-control" placeholder="Cari berdasarkan nama atau simbol">
                    </div>

                    <div class="col-md-3 col-lg-2">
                        <label for="per_page" class="form-label">Tampil</label>
                        <select id="per_page" name="per_page" class="form-select">
                            @foreach ([10, 25, 50, 100] as $perPageOption)
                                <option value="{{ $perPageOption }}" @selected((int) request('per_page', 10) === $perPageOption)>
                                    {{ $perPageOption }} data
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3 col-lg-2 d-flex gap-2">
                        <button type="submit" class="btn btn-outline-primary w-100">
                            <i class="bi bi-funnel me-2"></i>Terapkan
                        </button>
                        <a href="{{ route('units.index') }}" class="btn btn-outline-secondary w-100">
                            <i class="bi bi-arrow-counterclockwise me-2"></i>Reset
                        </a>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col" style="width: 70px;">No.</th>
                                <th scope="col">Nama Satuan</th>
                                <th scope="col">Simbol</th>
                                <th scope="col">Dibuat</th>
                                <th scope="col">Diperbarui</th>
                                <th scope="col" class="text-end" style="width: 150px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $isLengthAware = $units instanceof Illuminate\Pagination\LengthAwarePaginator;
                                $isPaginated = $units instanceof Illuminate\Pagination\AbstractPaginator;
                            @endphp
                            @forelse ($units as $unit)
                                <tr>
                                    <td>
                                        {{ $isPaginated ? $units->firstItem() + $loop->index : $loop->iteration }}
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ $unit->name }}</div>
                                        <div class="text-muted small">ID #{{ $unit->id }}</div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">{{ $unit->symbol }}</span>
                                    </td>
                                    <td>
                                        <div>{{ optional($unit->created_at)->format('d M Y') }}</div>
                                        <div class="text-muted small">{{ optional($unit->created_at)->format('H:i') }} WITA
                                        </div>
                                    </td>
                                    <td>
                                        <div>{{ optional($unit->updated_at)->format('d M Y') }}</div>
                                        <div class="text-muted small">{{ optional($unit->updated_at)->format('H:i') }} WITA
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group" role="group" aria-label="Aksi Satuan">
                                            <a href="{{ route('units.show', $unit) }}"
                                                class="btn btn-sm btn-outline-secondary" title="Detail">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="{{ route('units.edit', $unit) }}"
                                                class="btn btn-sm btn-outline-primary" title="Ubah">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <form action="{{ route('units.destroy', $unit) }}" method="POST"
                                                class="d-inline" onsubmit="return confirm('Hapus satuan ini?');">
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
                                    <td colspan="6" class="text-center py-5">
                                        <i class="bi bi-archive text-muted display-5 d-block mb-3"></i>
                                        <p class="mb-1 fw-semibold">Belum ada data satuan</p>
                                        <p class="text-muted mb-3">Mulai tambahkan satuan untuk mengelola material Anda.</p>
                                        <a href="{{ route('units.create') }}" class="btn btn-primary">
                                            <i class="bi bi-plus-lg me-2"></i>Tambah Satuan Pertama
                                        </a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($isPaginated ?? false)
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3 pt-4">
                        <div class="text-muted small">
                            Menampilkan
                            <span class="fw-semibold">{{ $units->count() }}</span>
                            @if ($isLengthAware ?? false)
                                dari
                                <span class="fw-semibold">{{ $units->total() }}</span>
                                satuan
                            @else
                                satuan pada halaman ini
                            @endif
                        </div>
                        <div>
                            {{ $units->withQueryString()->links('pagination::bootstrap-5') }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
