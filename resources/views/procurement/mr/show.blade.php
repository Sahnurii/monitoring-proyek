@extends('layouts.app')

@section('content')
    <div class="container-fluid px-0 px-lg-3">
        <header
            class="d-flex flex-wrap flex-md-nowrap align-items-start align-items-md-center justify-content-between gap-3 mb-4">
            <div>
                <h1 class="fw-bold mb-1">Detail Permintaan Material</h1>
                <p class="text-muted mb-0">Tinjau informasi permintaan beserta status dan daftar materialnya.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('procurement.material-requests.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Kembali
                </a>
                @php
                    $userRole = optional(auth()->user()->role)->role_name;
                    $canApprove = in_array($userRole, ['admin', 'manager']) && $materialRequest->status === 'submitted';
                @endphp
                @if ($canApprove)
                    <form action="{{ route('procurement.material-requests.approve', $materialRequest) }}" method="POST"
                        onsubmit="return confirm('Setujui permintaan material ini?')">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle me-2"></i>Approve
                        </button>
                    </form>

                    <form action="{{ route('procurement.material-requests.reject', $materialRequest) }}" method="POST"
                        onsubmit="return confirm('Tolak permintaan material ini?')">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-x-circle me-2"></i>Reject
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
                'submitted' => 'bg-info text-dark',
                'approved' => 'bg-success',
                'rejected' => 'bg-danger',
            ];
            $statusLabel = $statuses[$materialRequest->status] ?? ucfirst($materialRequest->status ?? '');
            $badgeClass = $statusClasses[$materialRequest->status] ?? 'bg-secondary';
            $requestDate = optional($materialRequest->request_date)->format('d M Y');
        @endphp

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4 p-lg-5">
                <dl class="row mb-0 g-4">
                    <div class="col-md-4">
                        <dt class="text-muted">Kode Permintaan</dt>
                        <dd class="fs-5 fw-semibold">{{ $materialRequest->code }}</dd>
                    </div>
                    <div class="col-md-4">
                        <dt class="text-muted">Proyek</dt>
                        <dd class="fs-5 fw-semibold">
                            {{ optional($materialRequest->project)->name ?? 'Tidak ada proyek' }}
                            <div class="text-muted small">{{ optional($materialRequest->project)->code ?? '-' }}</div>
                        </dd>
                    </div>
                    <div class="col-md-4">
                        <dt class="text-muted">Status</dt>
                        <dd>
                            <span class="badge {{ $badgeClass }} fs-6">{{ $statusLabel }}</span>
                        </dd>
                    </div>
                    <div class="col-md-4">
                        <dt class="text-muted">Total Permintaan</dt>
                        <dd class="fs-5 fw-semibold">
                            Rp {{ number_format((float) $materialRequest->total_amount, 2, ',', '.') }}
                        </dd>
                    </div>
                    <div class="col-md-4">
                        <dt class="text-muted">Tanggal Permintaan</dt>
                        <dd>
                            <div class="fs-5">{{ $requestDate ?? 'Belum ditentukan' }}</div>
                            <div class="text-muted small">{{ optional($materialRequest->created_at)->format('H:i') }} WITA
                            </div>
                        </dd>
                    </div>
                    <div class="col-md-4">
                        <dt class="text-muted">Pemohon</dt>
                        <dd>
                            <div class="fs-5 fw-semibold">
                                {{ optional($materialRequest->requester)->name ?? 'Tidak diketahui' }}</div>
                            <div class="text-muted small">{{ optional($materialRequest->requester)->email ?? '-' }}</div>
                        </dd>
                    </div>
                    <div class="col-md-4">
                        <dt class="text-muted">Disetujui Oleh</dt>
                        <dd>
                            @if ($materialRequest->approved_by)
                                <div class="fs-5 fw-semibold">
                                    {{ optional($materialRequest->approver)->name ?? 'Tidak diketahui' }}</div>
                                <div class="text-muted small">
                                    {{ optional($materialRequest->approved_at)->format('d M Y H:i') }} WITA
                                </div>
                            @else
                                <span class="text-muted">Belum disetujui</span>
                            @endif
                        </dd>
                    </div>
                    <div class="col-12">
                        <dt class="text-muted">Catatan</dt>
                        <dd class="fs-5">
                            @if ($materialRequest->notes)
                                {{ $materialRequest->notes }}
                            @else
                                <span class="text-muted">Tidak ada catatan tambahan.</span>
                            @endif
                        </dd>
                    </div>
                    <div class="col-md-6">
                        <dt class="text-muted">Dibuat</dt>
                        <dd>
                            <div>{{ optional($materialRequest->created_at)->format('d M Y') }}</div>
                            <div class="text-muted small">{{ optional($materialRequest->created_at)->format('H:i') }} WITA
                            </div>
                        </dd>
                    </div>
                    <div class="col-md-6">
                        <dt class="text-muted">Diperbarui Terakhir</dt>
                        <dd>
                            <div>{{ optional($materialRequest->updated_at)->format('d M Y') }}</div>
                            <div class="text-muted small">{{ optional($materialRequest->updated_at)->format('H:i') }} WITA
                            </div>
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h2 class="h5 mb-0">Daftar Material</h2>
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
                                <th style="width: 150px;" class="text-end">Harga Satuan</th>
                                <th style="width: 150px;" class="text-end">Total Harga</th>
                                <th>Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($materialRequest->items as $item)
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
                                    <td class="text-end">
                                        Rp {{ number_format((float) $item->unit_price, 2, ',', '.') }}
                                    </td>
                                    <td class="text-end fw-semibold">
                                        Rp {{ number_format((float) $item->total_price, 2, ',', '.') }}
                                    </td>
                                    <td>{{ $item->remarks ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <i class="bi bi-list-task text-muted display-5 d-block mb-3"></i>
                                        <p class="mb-1 fw-semibold">Tidak ada material tercatat</p>
                                        <p class="text-muted mb-0">Permintaan ini belum memiliki item material.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4 d-flex justify-content-end">
                    <div class="text-end">
                        <div class="text-muted small">Total Permintaan</div>
                        <div class="fs-5 fw-bold">Rp
                            {{ number_format((float) $materialRequest->total_amount, 2, ',', '.') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
