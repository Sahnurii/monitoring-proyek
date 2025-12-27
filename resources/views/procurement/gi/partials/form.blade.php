@php
    $goodsIssue = $goodsIssue ?? null;
    $issueItems = [];
    if ($goodsIssue) {
        $issueItemsCollection = collect();
        if (method_exists($goodsIssue, 'items')) {
            $issueItemsCollection = $goodsIssue->relationLoaded('items')
                ? $goodsIssue->items
                : $goodsIssue->items()->get();
        } elseif (isset($goodsIssue->items) && is_iterable($goodsIssue->items)) {
            $issueItemsCollection = collect($goodsIssue->items);
        }

        $issueItems = $issueItemsCollection
            ->map(function ($item) {
                return [
                    'material_id' => $item->material_id ?? null,
                    'qty' => $item->qty ?? null,
                    'remarks' => $item->remarks ?? null,
                ];
            })
            ->toArray();
    }

    $oldItems = old('items', $issueItems);
    if (empty($oldItems)) {
        $oldItems = [['material_id' => null, 'qty' => null, 'remarks' => null]];
    }
    $oldItems = array_values($oldItems);
    $nextIndex = count($oldItems);

    $defaultIssuedDate =
        $goodsIssue && $goodsIssue->issued_date
            ? \Illuminate\Support\Carbon::parse($goodsIssue->issued_date)->format('Y-m-d')
            : now()->format('Y-m-d');
    $defaultIssuedDate = old('issued_date', $defaultIssuedDate);

    $currentStatus = old('status', $goodsIssue->status ?? 'draft');

    $submitLabel = $submitLabel ?? 'Simpan Goods Issue';
@endphp

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

<form action="{{ $action }}" method="POST" class="row g-4" data-gi-items-container
    data-next-index="{{ $nextIndex }}">
    @csrf
    @if (!empty($method))
        @method($method)
    @endif

    <div class="col-md-4">
        <label for="code" class="form-label">Kode Goods Issue</label>
        <input type="text" id="code" name="code" value="{{ old('code', $goodsIssue->code ?? '') }}"
            class="form-control @error('code') is-invalid @enderror" placeholder="Contoh: GI-001"
            @if ($goodsIssue) readonly @endif required>
        @error('code')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="project_id" class="form-label">Proyek</label>

        @if ($goodsIssue)
            <select id="project_id" class="form-select" disabled>
                <option>
                    {{ optional($projects->firstWhere('id', $goodsIssue->project_id))->code ?? '' }}
                    &mdash;
                    {{ optional($projects->firstWhere('id', $goodsIssue->project_id))->name ?? '' }}
                </option>
            </select>
            <input type="hidden" name="project_id" value="{{ $goodsIssue->project_id }}">
        @else
            <select id="project_id" name="project_id" class="form-select @error('project_id') is-invalid @enderror"
                required>
                <option value="">Pilih Proyek</option>
                @foreach ($projects as $project)
                    <option value="{{ $project->id }}" @selected((string) old('project_id') === (string) $project->id)>
                        {{ $project->code }} &mdash; {{ $project->name }}
                    </option>
                @endforeach
            </select>
        @endif

        @error('project_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>


    <div class="col-md-4">
        <label for="issued_date" class="form-label">Tanggal Pengeluaran</label>
        <input type="date" id="issued_date" name="issued_date" value="{{ $defaultIssuedDate }}"
            class="form-control @error('issued_date') is-invalid @enderror" required>
        @error('issued_date')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="status" class="form-label">Status</label>
        @if (auth()->user()->role->role_name === 'admin')
            <select name="status" class="form-select" required>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected($currentStatus === $value)>{{ $label }}</option>
                @endforeach
            </select>
        @else
            <input type="hidden" name="status" value="draft">
            <input type="text" class="form-control" value="Draft" disabled>
        @endif
        @error('status')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        @if (auth()->user()->role->role_name !== 'admin')
            <small class="text-muted">Status otomatis draft</small>
        @endif
    </div>


    <div class="col-md-8">
        <label for="remarks" class="form-label">Catatan</label>
        <textarea id="remarks" name="remarks" rows="3" class="form-control @error('remarks') is-invalid @enderror"
            placeholder="Tambahkan catatan pengeluaran" @if ($goodsIssue) readonly @endif>{{ old('remarks', $goodsIssue->remarks ?? '') }}</textarea>
        @error('remarks')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <label class="form-label">Daftar Material Dikeluarkan</label>
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 45%;">Material</th>
                                <th style="width: 20%;">Jumlah</th>
                                <th>Catatan Item</th>
                                <th style="width: 70px;" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody data-gi-items-body>
                            @foreach ($oldItems as $index => $item)
                                <tr>
                                    <td>
                                        <select name="items[{{ $index }}][material_id]" data-field="material_id"
                                            class="form-select @error('items.' . $index . '.material_id') is-invalid @enderror">
                                            <option value="">Pilih Material</option>
                                            @foreach ($materials as $material)
                                                <option value="{{ $material->id }}" @selected((string) ($item['material_id'] ?? '') === (string) $material->id)>
                                                    {{ $material->name }}
                                                    @if ($material->unit)
                                                        ({{ $material->unit->symbol ?? $material->unit->name }})
                                                    @endif
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('items.' . $index . '.material_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </td>
                                    <td>
                                        <div class="input-group">
                                            <input type="number" min="0" step="0.01"
                                                name="items[{{ $index }}][qty]" data-field="qty"
                                                value="{{ $item['qty'] ?? '' }}"
                                                class="form-control @error('items.' . $index . '.qty') is-invalid @enderror"
                                                placeholder="0.00">
                                            <span class="input-group-text">qty</span>
                                        </div>
                                        @error('items.' . $index . '.qty')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </td>
                                    <td>
                                        <input type="text" name="items[{{ $index }}][remarks]"
                                            data-field="remarks" value="{{ $item['remarks'] ?? '' }}"
                                            class="form-control" placeholder="Catatan tambahan">
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger" data-remove-item>
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <button type="button" class="btn btn-outline-primary mt-3" data-add-item>
                    <i class="bi bi-plus-lg me-2"></i>Tambah Baris Material
                </button>

                @error('items')
                    <div class="text-danger small mt-2">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    <div class="col-12 d-flex justify-content-end gap-2">
        <a href="{{ route('procurement.goods-issues.index') }}" class="btn btn-outline-secondary">Batal</a>
        <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
    </div>

    <template id="gi-item-row-template">
        <tr>
            <td>
                <select name="items[__INDEX__][material_id]" data-field="material_id" class="form-select">
                    <option value="">Pilih Material</option>
                    @foreach ($materials as $material)
                        <option value="{{ $material->id }}">
                            {{ $material->name }}
                            @if ($material->unit)
                                ({{ $material->unit->symbol ?? $material->unit->name }})
                            @endif
                        </option>
                    @endforeach
                </select>
            </td>
            <td>
                <div class="input-group">
                    <input type="number" min="0" step="0.01" name="items[__INDEX__][qty]"
                        data-field="qty" class="form-control" placeholder="0.00">
                    <span class="input-group-text">qty</span>
                </div>
            </td>
            <td>
                <input type="text" name="items[__INDEX__][remarks]" data-field="remarks" class="form-control"
                    placeholder="Catatan tambahan">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger" data-remove-item>
                    <i class="bi bi-x-lg"></i>
                </button>
            </td>
        </tr>
    </template>
</form>

@once
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                document.querySelectorAll('[data-gi-items-container]').forEach((container) => {
                    const tableBody = container.querySelector('[data-gi-items-body]');
                    const template = container.querySelector('#gi-item-row-template');
                    let itemIndex = Number(container.getAttribute('data-next-index')) || tableBody.children
                        .length;

                    const addButton = container.querySelector('[data-add-item]');
                    if (addButton) {
                        addButton.addEventListener('click', () => {
                            addRow();
                        });
                    }

                    tableBody.addEventListener('click', (event) => {
                        const removeButton = event.target.closest('[data-remove-item]');
                        if (!removeButton) {
                            return;
                        }

                        event.preventDefault();
                        const row = removeButton.closest('tr');
                        if (row) {
                            row.remove();
                        }

                        if (tableBody.children.length === 0) {
                            addRow();
                        }
                    });

                    function addRow(defaults = {}) {
                        if (!template) {
                            return;
                        }

                        const html = template.innerHTML.replace(/__INDEX__/g, itemIndex);
                        const wrapper = document.createElement('tbody');
                        wrapper.innerHTML = html.trim();
                        const row = wrapper.firstElementChild;

                        const materialField = row.querySelector('[data-field="material_id"]');
                        const qtyField = row.querySelector('[data-field="qty"]');
                        const remarksField = row.querySelector('[data-field="remarks"]');

                        if (defaults.material_id) {
                            materialField.value = String(defaults.material_id);
                        }

                        if (defaults.qty) {
                            qtyField.value = defaults.qty;
                        }

                        if (defaults.remarks) {
                            remarksField.value = defaults.remarks;
                        }

                        tableBody.appendChild(row);
                        itemIndex += 1;
                    }
                });
            });
        </script>
    @endpush
@endonce
