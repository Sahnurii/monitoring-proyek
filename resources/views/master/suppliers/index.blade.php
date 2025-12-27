@extends('layouts.app')

@section('content')
    <div class="container-fluid px-0 px-lg-3">
        <header
            class="d-flex flex-wrap flex-md-nowrap align-items-start align-items-md-center justify-content-between gap-3 mb-4">
            <div>
                <h1 class="fw-bold mb-1">Data Supplier</h1>
                <p class="text-muted mb-0">Kelola daftar pemasok beserta informasi kontak dan penilaiannya.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('suppliers.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-2"></i>Tambah Supplier
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
                <form action="{{ route('suppliers.index') }}" method="GET" class="row g-3 align-items-end mb-4">
                    <div class="col-lg-4 col-md-6">
                        <label for="search" class="form-label">Cari Supplier</label>
                        <input type="search" id="search" name="search" value="{{ request('search') }}"
                            class="form-control" placeholder="Cari nama, NPWP, email, atau telepon">
                    </div>

                    <div class="col-md-3 col-lg-2">
                        <label for="rating" class="form-label">Minimal Rating</label>
                        <select id="rating" name="rating" class="form-select">
                            <option value="">Semua Rating</option>
                            @foreach (range(1, 5) as $ratingOption)
                                <option value="{{ $ratingOption }}"
                                    @selected((string) $ratingOption === request('rating'))>
                                    {{ $ratingOption }}+
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3 col-lg-2">
                        <label for="per_page" class="form-label">Tampil</label>
                        <select id="per_page" name="per_page" class="form-select">
                            @foreach ([10, 25, 50, 100] as $perPageOption)
                                <option value="{{ $perPageOption }}"
                                    @selected((int) request('per_page', 10) === $perPageOption)>
                                    {{ $perPageOption }} data
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3 col-lg-2 d-flex gap-2">
                        <button type="submit" class="btn btn-outline-primary w-100">
                            <i class="bi bi-funnel me-2"></i>Terapkan
                        </button>
                        <a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary w-100">
                            <i class="bi bi-arrow-counterclockwise me-2"></i>Reset
                        </a>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col" style="width: 70px;">No.</th>
                                <th scope="col">Nama Supplier</th>
                                <th scope="col">NPWP</th>
                                <th scope="col">Kontak</th>
                                <th scope="col">Alamat</th>
                                <th scope="col" class="text-center">Rating</th>
                                <th scope="col">Dibuat</th>
                                <th scope="col">Diperbarui</th>
                                <th scope="col" class="text-end" style="width: 150px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $isLengthAware = $suppliers instanceof Illuminate\Pagination\LengthAwarePaginator;
                                $isPaginated = $suppliers instanceof Illuminate\Pagination\AbstractPaginator;
                            @endphp
                            @forelse ($suppliers as $supplier)
                                <tr>
                                    <td>
                                        {{ $isPaginated ? $suppliers->firstItem() + $loop->index : $loop->iteration }}
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ $supplier->name }}</div>
                                        <div class="text-muted small">ID #{{ $supplier->id }}</div>
                                    </td>
                                    <td>
                                        @if ($supplier->npwp)
                                            <span class="badge bg-light text-dark border">{{ $supplier->npwp }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="small">
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="bi bi-envelope text-muted"></i>
                                                <span>{{ $supplier->email ?? 'Tidak ada' }}</span>
                                            </div>
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="bi bi-telephone text-muted"></i>
                                                <span>{{ $supplier->phone ?? 'Tidak ada' }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="small text-truncate" style="max-width: 240px;">
                                            {{ $supplier->address ?? 'Belum diisi' }}
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        @php
                                            $ratingValue = (int) $supplier->rating;
                                            $clampedRating = max(0, min($ratingValue, 5));
                                        @endphp
                                        <div class="text-warning">
                                            @for ($i = 0; $i < $clampedRating; $i++)
                                                <i class="bi bi-star-fill"></i>
                                            @endfor
                                            @for ($i = $clampedRating; $i < 5; $i++)
                                                <i class="bi bi-star"></i>
                                            @endfor
                                        </div>
                                        <div class="text-muted small">{{ $ratingValue }}/5</div>
                                    </td>
                                    <td>
                                        <div>{{ optional($supplier->created_at)->format('d M Y') }}</div>
                                        <div class="text-muted small">
                                            {{ optional($supplier->created_at)->format('H:i') }} WITA
                                        </div>
                                    </td>
                                    <td>
                                        <div>{{ optional($supplier->updated_at)->format('d M Y') }}</div>
                                        <div class="text-muted small">
                                            {{ optional($supplier->updated_at)->format('H:i') }} WITA
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group" role="group" aria-label="Aksi Supplier">
                                            <a href="{{ route('suppliers.show', $supplier) }}"
                                                class="btn btn-sm btn-outline-secondary" title="Detail">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="{{ route('suppliers.edit', $supplier) }}"
                                                class="btn btn-sm btn-outline-primary" title="Ubah">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <form action="{{ route('suppliers.destroy', $supplier) }}" method="POST"
                                                class="d-inline"
                                                onsubmit="return confirm('Hapus supplier ini?');">
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
                                    <td colspan="9" class="text-center py-5">
                                        <i class="bi bi-people text-muted display-5 d-block mb-3"></i>
                                        <p class="mb-1 fw-semibold">Belum ada data supplier</p>
                                        <p class="text-muted mb-3">Mulai tambahkan pemasok untuk mengelola kebutuhan proyek
                                            Anda.</p>
                                        <a href="{{ route('suppliers.create') }}" class="btn btn-primary">
                                            <i class="bi bi-plus-lg me-2"></i>Tambah Supplier Pertama
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
                            <span class="fw-semibold">{{ $suppliers->count() }}</span>
                            @if ($isLengthAware ?? false)
                                dari
                                <span class="fw-semibold">{{ $suppliers->total() }}</span>
                                supplier
                            @else
                                supplier pada halaman ini
                            @endif
                        </div>
                        <div>
                            {{ $suppliers->withQueryString()->links('pagination::bootstrap-5') }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
