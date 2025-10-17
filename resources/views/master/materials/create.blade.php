@extends('layouts.app')

@section('content')
    <div class="container-fluid px-0 px-lg-3">
        <header
            class="d-flex flex-wrap flex-md-nowrap align-items-start align-items-md-center justify-content-between gap-3 mb-4">
            <div>
                <h1 class="fw-bold mb-1">Tambah Material</h1>
                <p class="text-muted mb-0">Lengkapi informasi material baru untuk kebutuhan proyek.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('materials.index') }}" class="btn btn-outline-secondary">
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

                @if ($units->isEmpty())
                    <div class="alert alert-warning" role="alert">
                        Belum ada data satuan. Tambahkan satuan terlebih dahulu sebelum membuat material baru.
                    </div>
                @endif

                <form action="{{ route('materials.store') }}" method="POST" class="row g-4">
                    @csrf

                    <div class="col-md-6">
                        <label for="sku" class="form-label">SKU Material</label>
                        <input type="text" id="sku" name="sku" value="{{ old('sku') }}"
                            class="form-control @error('sku') is-invalid @enderror" placeholder="Contoh: MAT-001" required>
                        @error('sku')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="name" class="form-label">Nama Material</label>
                        <input type="text" id="name" name="name" value="{{ old('name') }}"
                            class="form-control @error('name') is-invalid @enderror" placeholder="Nama material" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="unit_id" class="form-label">Satuan</label>
                        <select id="unit_id" name="unit_id"
                            class="form-select @error('unit_id') is-invalid @enderror" required>
                            <option value="" disabled {{ old('unit_id') ? '' : 'selected' }}>Pilih satuan</option>
                            @foreach ($units as $unit)
                                <option value="{{ $unit->id }}" @selected(old('unit_id') == $unit->id)>
                                    {{ $unit->name }} ({{ $unit->symbol }})
                                </option>
                            @endforeach
                        </select>
                        @error('unit_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="min_stock" class="form-label">Stok Minimal</label>
                        <div class="input-group">
                            <input type="number" id="min_stock" name="min_stock" min="0" step="0.01"
                                value="{{ old('min_stock', '0') }}"
                                class="form-control @error('min_stock') is-invalid @enderror" placeholder="0">
                            <span class="input-group-text">Satuan</span>
                        </div>
                        @error('min_stock')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Isi 0 jika tidak memiliki batas stok minimal.</div>
                    </div>

                    <div class="col-12 d-flex justify-content-end gap-2">
                        <a href="{{ route('materials.index') }}" class="btn btn-outline-secondary">Batal</a>
                        <button type="submit" class="btn btn-primary">Simpan Material</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
