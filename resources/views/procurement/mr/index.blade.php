@extends('layouts.app')

@section('content')
    <div class="container-fluid px-0 px-lg-3">
        <header
            class="d-flex flex-wrap flex-md-nowrap align-items-start align-items-md-center justify-content-between gap-3 mb-4">
            <div>
                <h1 class="fw-bold mb-1">Permintaan Material</h1>
                <p class="text-muted mb-0">Pantau dan kelola seluruh permintaan material proyek.</p>
            </div>
            <div class="d-flex gap-2">
                @if (optional(auth()->user()->role)->role_name === 'operator')
                    <a href="{{ route('procurement.material-requests.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-lg me-2"></i>Tambah Permintaan
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
                <form action="{{ route('procurement.material-requests.index') }}" method="GET"
                    class="row g-3 align-items-end mb-4">
                    <div class="col-md-4">
                        <label for="search" class="form-label">Cari Permintaan</label>
                        <input type="search" id="search" name="search" value="{{ request('search') }}"
                            class="form-control" placeholder="Cari kode atau nama proyek">
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

                    <div class="col-md-2">
                        <label for="request_date" class="form-label">Tanggal</label>
                        <input type="date" id="request_date" name="request_date" value="{{ request('request_date') }}"
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
                        <a href="{{ route('procurement.material-requests.index') }}"
                            class="btn btn-outline-secondary w-100">
                            <i class="bi bi-arrow-counterclockwise me-2"></i>Reset
                        </a>
                    </div>
                </form>

                @php
                    $statusClasses = [
                        'submitted' => 'bg-info text-dark',
                        'approved' => 'bg-success',
                        'rejected' => 'bg-danger',
                    ];
                    $isPaginated = $requests instanceof Illuminate\Pagination\AbstractPaginator;
                    $isLengthAware = $requests instanceof Illuminate\Pagination\LengthAwarePaginator;
                @endphp

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col" style="width: 70px;">No.</th>
                                <th scope="col">Kode</th>
                                <th scope="col">Proyek</th>
                                <th scope="col">Pemohon</th>
                                <th scope="col">Tanggal</th>
                                <th scope="col">Status</th>
                                <th scope="col" class="text-center">Jumlah Item</th>
                                <th scope="col" class="text-end">Total Harga</th>
                                <th scope="col" class="text-end" style="width: 150px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($requests as $requestItem)
                                @php
                                    $statusLabel =
                                        $statuses[$requestItem->status] ?? ucfirst($requestItem->status ?? '');
                                    $badgeClass = $statusClasses[$requestItem->status] ?? 'bg-secondary';
                                    $requestDate = optional($requestItem->request_date)->format('d M Y');
                                @endphp
                                <tr>
                                    <td>
                                        {{ $isPaginated ? $requests->firstItem() + $loop->index : $loop->iteration }}
                                    </td>
                                    <td class="fw-semibold">
                                        <div>{{ $requestItem->code }}</div>
                                        <div class="text-muted small">ID #{{ $requestItem->id }}</div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ optional($requestItem->project)->name ?? '-' }}</div>
                                        <div class="text-muted small">
                                            {{ optional($requestItem->project)->code ?? 'Tidak ada kode' }}</div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">
                                            {{ optional($requestItem->requester)->name ?? 'Tidak diketahui' }}</div>
                                        <div class="text-muted small">{{ optional($requestItem->requester)->email ?? '-' }}
                                        </div>
                                    </td>
                                    <td>
                                        <div>{{ $requestDate ?? '-' }}</div>
                                        <div class="text-muted small">{{ $requestItem->created_at?->format('H:i') ?? '' }}
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge {{ $badgeClass }}">{{ $statusLabel }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="fw-semibold">{{ $requestItem->items_count ?? 0 }}</span>
                                    </td>
                                    <td class="text-end">
                                        <span class="fw-semibold">Rp
                                            {{ number_format((float) $requestItem->total_amount, 2, ',', '.') }}</span>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group" role="group" aria-label="Aksi Permintaan">
                                            <a href="{{ route('procurement.material-requests.show', $requestItem) }}"
                                                class="btn btn-sm btn-outline-secondary" title="Detail">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            {{-- <a href="{{ route('procurement.material-requests.edit', $requestItem) }}"
                                                class="btn btn-sm btn-outline-primary" title="Ubah">
                                                <i class="bi bi-pencil"></i>
                                            </a> --}}
                                            {{-- <form action="{{ route('procurement.material-requests.destroy', $requestItem) }}"
                                                method="POST" class="d-inline"
                                                onsubmit="return confirm('Hapus permintaan material ini?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form> --}}
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-5">
                                        <i class="bi bi-box-seam text-muted display-5 d-block mb-3"></i>
                                        <p class="mb-1 fw-semibold">Belum ada permintaan material</p>
                                        <p class="text-muted mb-3">Tambahkan permintaan baru untuk memulai proses
                                            pengadaan.</p>
                                        @if (optional(auth()->user()->role)->role_name === 'operator')
                                            <a href="{{ route('procurement.material-requests.create') }}"
                                                class="btn btn-primary">
                                                <i class="bi bi-plus-lg me-2"></i>Tambah Permintaan
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($isPaginated)
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3 pt-4">
                        @if ($isLengthAware)
                            <div class="text-muted small">
                                Menampilkan {{ $requests->firstItem() }} - {{ $requests->lastItem() }} dari
                                {{ $requests->total() }} permintaan
                            </div>
                        @else
                            <div class="text-muted small">
                                Total permintaan ditampilkan: {{ $requests->count() }}
                            </div>
                        @endif

                        {{ $requests->onEachSide(1)->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
