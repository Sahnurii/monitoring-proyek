@extends('layouts.app')

@section('content')
    <div class="container-fluid px-0 px-lg-3">
        <header
            class="d-flex flex-wrap flex-md-nowrap align-items-start align-items-md-center justify-content-between gap-3 mb-4">
            <div>
                <h1 class="fw-bold mb-1">Ubah Proyek</h1>
                <p class="text-muted mb-0">Perbarui informasi proyek untuk memastikan data tetap akurat.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('projects.show', $project) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Kembali
                </a>
            </div>
        </header>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4 p-lg-5">
                @if ($errors->any())
                    <div class="alert alert-danger" role="alert">
                        <div class="fw-semibold mb-2">Terdapat kesalahan pada input Anda:</div>
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('projects.update', $project) }}" method="POST" class="row g-4">
                    @csrf
                    @method('PUT')

                    <div class="col-md-6">
                        <label for="code" class="form-label">Kode Proyek</label>
                        <input type="text" id="code" class="form-control" value="{{ $project->code }}" disabled>
                        <div class="form-text">Kode dibuat otomatis dan tidak dapat diubah.</div>
                    </div>

                    <div class="col-md-6">
                        <label for="name" class="form-label">Nama Proyek</label>
                        <input type="text" id="name" name="name" value="{{ old('name', $project->name) }}"
                            class="form-control @error('name') is-invalid @enderror" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="client" class="form-label">Klien</label>
                        <input type="text" id="client" name="client" value="{{ old('client', $project->client) }}"
                            class="form-control @error('client') is-invalid @enderror">
                        @error('client')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="location" class="form-label">Lokasi</label>
                        <input type="text" id="location" name="location"
                            value="{{ old('location', $project->location) }}"
                            class="form-control @error('location') is-invalid @enderror">
                        @error('location')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="start_date" class="form-label">Tanggal Mulai</label>
                        <input type="date" id="start_date" name="start_date"
                            value="{{ old('start_date', optional($project->start_date)->format('Y-m-d')) }}"
                            class="form-control @error('start_date') is-invalid @enderror">
                        @error('start_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="end_date" class="form-label">Tanggal Selesai</label>
                        <input type="date" id="end_date" name="end_date"
                            value="{{ old('end_date', optional($project->end_date)->format('Y-m-d')) }}"
                            class="form-control @error('end_date') is-invalid @enderror">
                        @error('end_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Biarkan kosong jika proyek masih berjalan.</div>
                    </div>

                    <div class="col-md-6">
                        <label for="budget" class="form-label">Anggaran</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" id="budget" name="budget" min="0" step="0.01"
                                value="{{ old('budget', $project->budget) }}"
                                class="form-control @error('budget') is-invalid @enderror">
                        </div>
                        @error('budget')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="status" class="form-label">Status Proyek</label>
                        <select id="status" name="status"
                            class="form-select @error('status') is-invalid @enderror" required>
                            @foreach ($statuses as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', $project->status) === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 d-flex justify-content-end gap-2">
                        <a href="{{ route('projects.show', $project) }}" class="btn btn-outline-secondary">Batal</a>
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
