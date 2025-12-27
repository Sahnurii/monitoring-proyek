@extends('layouts.app')

@section('content')
    <div class="container-fluid px-0 px-lg-3">
        <header
            class="d-flex flex-wrap flex-md-nowrap align-items-start align-items-md-center justify-content-between gap-3 mb-4">
            <div>
                <h1 class="fw-bold mb-1">Pengeluaran Barang</h1>
                <p class="text-muted mb-0">Kelola pencatatan barang yang keluar dari gudang untuk proyek.</p>
            </div>
            <div class="d-flex gap-2">
                @if (optional(auth()->user()->role)->role_name === 'operator')
                    <a href="{{ route('procurement.goods-issues.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-lg me-2"></i>Tambah Goods Issue
                    </a>
                @endif
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
                <form action="{{ route('procurement.goods-issues.index') }}" method="GET"
                    class="row g-3 align-items-end mb-4">
                    <div class="col-md-4">
                        <label for="search" class="form-label">Cari Goods Issue</label>
                        <input type="search" id="search" name="search" value="{{ request('search') }}"
                            class="form-control" placeholder="Cari kode, proyek, atau petugas">
                    </div>

                    <div class="col-md-3">
                        <label for="status" class="form-label">Status</label>
                        <select id="status" name="status" class="form-select">
                            <option value="">Semua Status</option>
                            @foreach ($statuses as $value => $label)
                                <option value="{{ $value }}" @selected((string) $value === request('status'))>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="project_id" class="form-label">Proyek</label>
                        <select id="project_id" name="project_id" class="form-select">
                            <option value="">Semua Proyek</option>
                            @foreach ($projects as $project)
                                <option value="{{ $project->id }}" @selected((string) $project->id === request('project_id'))>
                                    {{ $project->code }} &mdash; {{ $project->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="issued_by" class="form-label">Petugas</label>
                        <select id="issued_by" name="issued_by" class="form-select">
                            <option value="">Semua Petugas</option>
                            @foreach ($issuers as $issuer)
                                <option value="{{ $issuer->id }}" @selected((string) $issuer->id === request('issued_by'))>
                                    {{ $issuer->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label for="issued_date" class="form-label">Tanggal</label>
                        <input type="date" id="issued_date" name="issued_date" value="{{ request('issued_date') }}"
                            class="form-control">
                    </div>

                    <div class="col-md-2">
                        <label for="per_page" class="form-label">Per Halaman</label>
                        <select id="per_page" name="per_page" class="form-select">
                            @foreach ([10, 25, 50, 100] as $size)
                                <option value="{{ $size }}" @selected((string) $size === (string) request('per_page', 10))>
                                    {{ $size }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4 col-lg-3 d-flex gap-2">
                        <button type="submit" class="btn btn-outline-primary w-100">
                            <i class="bi bi-funnel me-2"></i>Terapkan
                        </button>
                        <a href="{{ route('procurement.goods-issues.index') }}" class="btn btn-outline-secondary w-100">
                            <i class="bi bi-arrow-counterclockwise me-2"></i>Reset
                        </a>
                    </div>
                </form>

                @php
                    $statusClasses = [
                        'draft' => 'bg-secondary',
                        'issued' => 'bg-info text-dark',
                        'returned' => 'bg-warning text-dark',
                    ];
                    $issuesPaginator = $issues instanceof Illuminate\Pagination\AbstractPaginator ? $issues : null;
                    $issuesLengthAware = $issues instanceof Illuminate\Pagination\LengthAwarePaginator ? $issues : null;
                @endphp

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col" style="width: 70px;">No.</th>
                                <th scope="col">Kode</th>
                                <th scope="col">Proyek</th>
                                <th scope="col">Petugas</th>
                                <th scope="col">Tanggal</th>
                                <th scope="col">Status</th>
                                <th scope="col" class="text-center">Item</th>
                                <th scope="col" class="text-end" style="width: 150px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($issues as $issue)
                                @php
                                    $statusLabel = $statuses[$issue->status] ?? ucfirst($issue->status ?? '');
                                    $badgeClass = $statusClasses[$issue->status] ?? 'bg-secondary';
                                    $issuedDate = optional($issue->issued_date)->format('d M Y');
                                @endphp
                                <tr>
                                    <td>
                                        @if ($issuesPaginator)
                                            {{ $issuesPaginator->firstItem() + $loop->index }}
                                        @else
                                            {{ $loop->iteration }}
                                        @endif
                                    </td>
                                    <td class="fw-semibold">
                                        <div>{{ $issue->code }}</div>
                                        <div class="text-muted small">ID #{{ $issue->id }}</div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ optional($issue->project)->name ?? '-' }}</div>
                                        <div class="text-muted small">{{ optional($issue->project)->code ?? '-' }}</div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ optional($issue->issuer)->name ?? 'Tidak diketahui' }}
                                        </div>
                                        <div class="text-muted small">{{ optional($issue->issuer)->email ?? '-' }}</div>
                                    </td>
                                    <td>
                                        <div>{{ $issuedDate ?? '-' }}</div>
                                        <div class="text-muted small">{{ $issue->created_at?->format('H:i') ?? '' }}</div>
                                    </td>
                                    <td>
                                        <span class="badge {{ $badgeClass }}">{{ $statusLabel }}</span>
                                    </td>
                                    <td class="text-center fw-semibold">
                                        {{ $issue->items_count ?? 0 }}
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group" role="group" aria-label="Aksi Goods Issue">
                                            <a href="{{ route('procurement.goods-issues.show', $issue) }}"
                                                class="btn btn-sm btn-outline-secondary" title="Detail">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            @if (optional(auth()->user()->role)->role_name === 'admin')
                                                <a href="{{ route('procurement.goods-issues.edit', $issue) }}"
                                                    class="btn btn-sm btn-outline-primary" title="Ubah">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <form action="{{ route('procurement.goods-issues.destroy', $issue) }}"
                                                    method="POST" class="d-inline"
                                                    onsubmit="return confirm('Hapus goods issue ini?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                                        title="Hapus">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-5">
                                        <i class="bi bi-box-arrow-up text-muted display-5 d-block mb-3"></i>
                                        <p class="mb-1 fw-semibold">Belum ada pengeluaran barang</p>
                                        <p class="text-muted mb-0">Catat goods issue baru untuk mulai melacak stok.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($issuesPaginator && $issuesLengthAware)
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-4">
                        <div class="text-muted small">
                            Menampilkan
                            <span class="fw-semibold">{{ $issuesPaginator->firstItem() }} &ndash;
                                {{ $issuesPaginator->lastItem() }}</span>
                            dari <span class="fw-semibold">{{ $issuesLengthAware->total() }}</span> goods issue
                        </div>
                        {{ $issuesLengthAware->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
