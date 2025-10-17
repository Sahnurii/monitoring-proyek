@extends('layouts.app')

@section('content')
    <div class="container-fluid px-0 px-lg-3">
        <header
            class="d-flex flex-wrap flex-md-nowrap align-items-start align-items-md-center justify-content-between gap-3 mb-4">
            <div>
                <h1 class="fw-bold mb-1">Data Material</h1>
                <p class="text-muted mb-0">Kelola master data material beserta satuan dan stok minimalnya.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('materials.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-2"></i>Tambah Material
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
                <form action="{{ route('materials.index') }}" method="GET" class="row g-3 align-items-end mb-4">
                    <div class="col-md-4">
                        <label for="search" class="form-label">Cari Material</label>
                        <input type="search" id="search" name="search" value="{{ request('search') }}"
                            class="form-control" placeholder="Cari berdasarkan SKU atau nama">
                    </div>

                    <div class="col-md-3">
                        <label for="unit_id" class="form-label">Satuan</label>
                        <select id="unit_id" name="unit_id" class="form-select">
                            <option value="">Semua Satuan</option>
                            @foreach ($units ?? collect() as $unit)
                                <option value="{{ $unit->id }}" @selected((string) $unit->id === request('unit_id'))>
                                    {{ $unit->name }} ({{ $unit->symbol }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="stock_status" class="form-label">Status Stok</label>
                        <select id="stock_status" name="stock_status" class="form-select">
                            <option value="">Semua Status</option>
                            <option value="safe" @selected(request('stock_status') === 'safe')>Di atas stok minimal</option>
                            <option value="low" @selected(request('stock_status') === 'low')>Mendekati stok minimal</option>
                            <option value="critical" @selected(request('stock_status') === 'critical')>Di bawah stok minimal
                            </option>
                        </select>
                    </div>

                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-outline-primary w-100">
                            <i class="bi bi-funnel me-2"></i>Terapkan
                        </button>
                        <a href="{{ route('materials.index') }}" class="btn btn-outline-secondary w-100">
                            <i class="bi bi-arrow-counterclockwise me-2"></i>Reset
                        </a>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col" style="width: 70px;">#</th>
                                <th scope="col">SKU</th>
                                <th scope="col">Nama Material</th>
                                <th scope="col">Satuan</th>
                                <th scope="col" class="text-center">Stok Minimal</th>
                                <th scope="col">Dibuat</th>
                                <th scope="col">Diperbarui</th>
                                <th scope="col" class="text-end" style="width: 150px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $isLengthAware = $materials instanceof Illuminate\Pagination\LengthAwarePaginator;
                                $isPaginated = $materials instanceof Illuminate\Pagination\AbstractPaginator;
                            @endphp
                            @forelse ($materials as $material)
                                <tr>
                                    <td>
                                        {{ $isPaginated ? $materials->firstItem() + $loop->index : $loop->iteration }}
                                    </td>
                                    <td class="fw-semibold">{{ $material->sku }}</td>
                                    <td>
                                        <div class="fw-semibold">{{ $material->name }}</div>
                                        <div class="text-muted small">ID #{{ $material->id }}</div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            {{ optional($material->unit)->name ?? 'Tidak diketahui' }}
                                        </span>
                                        <div class="text-muted small">
                                            {{ optional($material->unit)->symbol }}
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="fw-semibold">
                                            {{ number_format($material->min_stock ?? 0, 2, ',', '.') }}
                                        </span>
                                    </td>
                                    <td>
                                        <div>{{ optional($material->created_at)->format('d M Y') }}</div>
                                        <div class="text-muted small">
                                            {{ optional($material->created_at)->format('H:i') }} WIB
                                        </div>
                                    </td>
                                    <td>
                                        <div>{{ optional($material->updated_at)->format('d M Y') }}</div>
                                        <div class="text-muted small">
                                            {{ optional($material->updated_at)->format('H:i') }} WIB
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group" role="group" aria-label="Aksi Material">
                                            <a href="{{ route('materials.show', $material) }}"
                                                class="btn btn-sm btn-outline-secondary" title="Detail">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="{{ route('materials.edit', $material) }}"
                                                class="btn btn-sm btn-outline-primary" title="Ubah">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <form action="{{ route('materials.destroy', $material) }}" method="POST"
                                                class="d-inline" onsubmit="return confirm('Hapus material ini?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                                    title="Hapus">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-5">
                                        <i class="bi bi-archive text-muted display-5 d-block mb-3"></i>
                                        <p class="mb-1 fw-semibold">Belum ada data material</p>
                                        <p class="text-muted mb-3">Mulai tambahkan material baru untuk kebutuhan proyek Anda.
                                        </p>
                                        <a href="{{ route('materials.create') }}" class="btn btn-primary">
                                            <i class="bi bi-plus-lg me-2"></i>Tambah Material Pertama
                                        </a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($isPaginated ?? false)
                    <div
                        class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3 pt-4">
                        <div class="text-muted small">
                            Menampilkan
                            <span class="fw-semibold">{{ $materials->count() }}</span>
                            @if ($isLengthAware ?? false)
                                dari
                                <span class="fw-semibold">{{ $materials->total() }}</span>
                                material
                            @else
                                material pada halaman ini
                            @endif
                        </div>
                        <div>
                            {{ $materials->withQueryString()->links() }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
