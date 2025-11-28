@php
    $requestModel = $materialRequest ?? null;
    $isEdit = $requestModel && $requestModel->exists;
    $defaultItems = $requestModel
        ? $requestModel->items->map(function ($item) {
            return [
                'material_id' => $item->material_id,
                'qty' => $item->qty,
                'unit_price' => $item->unit_price,
                'total_price' => $item->total_price,
                'remarks' => $item->remarks,
            ];
        })->toArray()
        : [];
    $oldItems = old('items', $defaultItems);
    if (empty($oldItems)) {
        $oldItems = [
            ['material_id' => null, 'qty' => null, 'unit_price' => null, 'total_price' => null, 'remarks' => null],
        ];
    }
    $oldItems = array_values($oldItems);
    $nextIndex = count($oldItems);
    $defaultRequestDate = $requestModel && $requestModel->request_date
        ? $requestModel->request_date->format('Y-m-d')
        : now()->format('Y-m-d');
    $materialPrices = $materials
        ->mapWithKeys(fn($material) => [$material->id => (float) $material->unit_price])
        ->toArray();
    $initialTotalAmount = collect($oldItems)
        ->sum(fn($item) => (float) ($item['total_price'] ?? 0));
    $formattedTotalAmount = number_format($initialTotalAmount, 2, '.', '');
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

<form action="{{ $action }}" method="POST" class="row g-4" data-items-container data-next-index="{{ $nextIndex }}"
    data-material-prices='@json($materialPrices)'>
    @csrf
    @if ($method ?? false)
        @method($method)
    @endif

    <div class="col-md-4">
        <label for="code" class="form-label">Kode Permintaan</label>
        @if ($isEdit)
            <input type="text" id="code" class="form-control" value="{{ $requestModel->code }}" disabled>
            <div class="form-text">Kode dibuat otomatis dan tidak dapat diubah.</div>
        @else
            <input type="text" id="code" class="form-control"
                value="Akan dihasilkan otomatis saat penyimpanan" disabled>
            <div class="form-text">Kode mengikuti urutan otomatis dengan awalan MRQ-.</div>
        @endif
    </div>

    <div class="col-md-4">
        <label for="project_id" class="form-label">Proyek</label>
        <select id="project_id" name="project_id"
            class="form-select @error('project_id') is-invalid @enderror" required>
            <option value="">Pilih Proyek</option>
            @foreach ($projects as $project)
                <option value="{{ $project->id }}"
                    @selected((string) old('project_id', $requestModel->project_id ?? '') === (string) $project->id)>
                    {{ $project->code }} &mdash; {{ $project->name }}
                </option>
            @endforeach
        </select>
        @error('project_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="request_date" class="form-label">Tanggal Permintaan</label>
        <input type="date" id="request_date" name="request_date"
            value="{{ old('request_date', $defaultRequestDate) }}"
            class="form-control @error('request_date') is-invalid @enderror" required>
        @error('request_date')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="status" class="form-label">Status</label>
        <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" required>
            @foreach ($statuses as $value => $label)
                <option value="{{ $value }}"
                    @selected((string) old('status', $requestModel->status ?? 'draft') === (string) $value)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        @error('status')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-8">
        <label for="notes" class="form-label">Catatan</label>
        <textarea id="notes" name="notes" rows="3" class="form-control @error('notes') is-invalid @enderror"
            placeholder="Tambahkan catatan atau kebutuhan khusus">{{ old('notes', $requestModel->notes ?? '') }}</textarea>
        @error('notes')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <label class="form-label">Daftar Material</label>
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 30%;">Material</th>
                                <th style="width: 15%;">Jumlah</th>
                                <th style="width: 17%;">Harga Satuan</th>
                                <th style="width: 18%;">Total Harga</th>
                                <th>Catatan Item</th>
                                <th style="width: 70px;" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody data-items-body>
                            @foreach ($oldItems as $index => $item)
                                <tr>
                                    <td>
                                        <select name="items[{{ $index }}][material_id]" data-field="material_id"
                                            class="form-select @error('items.' . $index . '.material_id') is-invalid @enderror">
                                            <option value="">Pilih Material</option>
                                            @foreach ($materials as $material)
                                                <option value="{{ $material->id }}"
                                                    data-unit-symbol="{{ $material->unit->symbol ?? $material->unit->name ?? '' }}"
                                                    data-unit-price="{{ number_format((float) $material->unit_price, 2, '.', '') }}"
                                                    @selected((string) ($item['material_id'] ?? '') === (string) $material->id)>
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
                                            <span class="input-group-text" data-qty-suffix>qty</span>
                                        </div>
                                        @error('items.' . $index . '.qty')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </td>
                                    <td>
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input type="number" min="0" step="0.01"
                                                name="items[{{ $index }}][unit_price]" data-field="unit_price"
                                                value="{{ number_format((float) ($item['unit_price'] ?? 0), 2, '.', '') }}"
                                                class="form-control text-end" placeholder="0.00" readonly>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input type="number" min="0" step="0.01"
                                                name="items[{{ $index }}][total_price]" data-field="total_price"
                                                value="{{ number_format((float) ($item['total_price'] ?? 0), 2, '.', '') }}"
                                                class="form-control text-end" placeholder="0.00" readonly>
                                        </div>
                                    </td>
                                    <td>
                                        <input type="text" name="items[{{ $index }}][remarks]" data-field="remarks"
                                            value="{{ $item['remarks'] ?? '' }}" class="form-control"
                                            placeholder="Catatan tambahan">
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

                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mt-3">
                    <button type="button" class="btn btn-outline-primary" data-add-item>
                        <i class="bi bi-plus-lg me-2"></i>Tambah Baris Material
                    </button>
                    <div class="ms-md-auto text-md-end">
                        <div class="text-muted small">Total Permintaan</div>
                        <div class="fs-5 fw-bold" data-total-display>
                            Rp {{ number_format((float) $initialTotalAmount, 2, ',', '.') }}
                        </div>
                        <input type="hidden" data-total-input value="{{ $formattedTotalAmount }}">
                    </div>
                </div>

                @error('items')
                    <div class="text-danger small mt-2">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    <div class="col-12 d-flex justify-content-end gap-2">
        <a href="{{ route('procurement.material-requests.index') }}" class="btn btn-outline-secondary">Batal</a>
        <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
    </div>

    <template id="mr-item-row-template">
        <tr>
            <td>
                <select name="items[__INDEX__][material_id]" data-field="material_id" class="form-select">
                    <option value="">Pilih Material</option>
                    @foreach ($materials as $material)
                        <option value="{{ $material->id }}"
                            data-unit-symbol="{{ $material->unit->symbol ?? $material->unit->name ?? '' }}"
                            data-unit-price="{{ number_format((float) $material->unit_price, 2, '.', '') }}">
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
                    <input type="number" min="0" step="0.01" name="items[__INDEX__][qty]" data-field="qty"
                        class="form-control" placeholder="0.00">
                    <span class="input-group-text" data-qty-suffix>qty</span>
                </div>
            </td>
            <td>
                <div class="input-group">
                    <span class="input-group-text">Rp</span>
                    <input type="number" min="0" step="0.01" name="items[__INDEX__][unit_price]" data-field="unit_price"
                        class="form-control text-end" placeholder="0.00" readonly>
                </div>
            </td>
            <td>
                <div class="input-group">
                    <span class="input-group-text">Rp</span>
                    <input type="number" min="0" step="0.01" name="items[__INDEX__][total_price]" data-field="total_price"
                        class="form-control text-end" placeholder="0.00" readonly>
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
                document.querySelectorAll('[data-items-container]').forEach((container) => {
                    const tableBody = container.querySelector('[data-items-body]');
                    const template = container.querySelector('#mr-item-row-template');
                    const addButton = container.querySelector('[data-add-item]');
                    const totalDisplay = container.querySelector('[data-total-display]');
                    const totalInput = container.querySelector('[data-total-input]');
                    const priceMap = JSON.parse(container.getAttribute('data-material-prices') || '{}');
                    let itemIndex = Number(container.getAttribute('data-next-index')) || tableBody.children.length;

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

                        updateSummary();
                    });

                    tableBody.addEventListener('change', (event) => {
                        if (!event.target.matches('[data-field="material_id"]')) {
                            return;
                        }

                        const row = event.target.closest('tr');
                        if (row) {
                            delete row.dataset.hasStoredPricing;
                        }
                        updateRow(row);
                        updateSummary();
                    });

                    tableBody.addEventListener('input', (event) => {
                        if (!event.target.matches('[data-field="qty"]')) {
                            return;
                        }

                        const row = event.target.closest('tr');
                        if (row) {
                            delete row.dataset.hasStoredPricing;
                        }
                        updateRow(row);
                        updateSummary();
                    });

                    Array.from(tableBody.querySelectorAll('tr')).forEach((row) => {
                        const unitPriceField = row.querySelector('[data-field="unit_price"]');
                        if (unitPriceField && unitPriceField.value) {
                            row.dataset.hasStoredPricing = '1';
                        }

                        updateRow(row);
                    });
                    updateSummary();

                    function addRow(defaults = {}) {
                        if (!template) {
                            return;
                        }

                        const html = template.innerHTML.replace(/__INDEX__/g, itemIndex);
                        const wrapper = document.createElement('tbody');
                        wrapper.innerHTML = html.trim();
                        const row = wrapper.firstElementChild;

                        tableBody.appendChild(row);
                        itemIndex += 1;

                        applyDefaults(row, defaults);
                        updateRow(row);
                        updateSummary();
                    }

                    function applyDefaults(row, defaults) {
                        const materialField = row.querySelector('[data-field="material_id"]');
                        const qtyField = row.querySelector('[data-field="qty"]');
                        const remarksField = row.querySelector('[data-field="remarks"]');
                        const unitPriceField = row.querySelector('[data-field="unit_price"]');
                        const totalPriceField = row.querySelector('[data-field="total_price"]');

                        if (materialField && defaults.material_id !== undefined && defaults.material_id !== null) {
                            materialField.value = String(defaults.material_id);
                        }

                        if (qtyField && defaults.qty !== undefined && defaults.qty !== null) {
                            qtyField.value = defaults.qty;
                        }

                        if (remarksField && defaults.remarks !== undefined && defaults.remarks !== null) {
                            remarksField.value = defaults.remarks;
                        }

                        if (unitPriceField && defaults.unit_price !== undefined && defaults.unit_price !== null) {
                            unitPriceField.value = parseFloat(defaults.unit_price).toFixed(2);
                        }

                        if (totalPriceField && defaults.total_price !== undefined && defaults.total_price !== null) {
                            totalPriceField.value = parseFloat(defaults.total_price).toFixed(2);
                        }

                        if (defaults.unit_price !== undefined || defaults.total_price !== undefined) {
                            row.dataset.hasStoredPricing = '1';
                        }
                    }

                    function updateRow(row) {
                        if (!row) {
                            return;
                        }

                        const materialField = row.querySelector('[data-field="material_id"]');
                        const qtyField = row.querySelector('[data-field="qty"]');
                        const unitPriceField = row.querySelector('[data-field="unit_price"]');
                        const totalPriceField = row.querySelector('[data-field="total_price"]');
                        const qtySuffix = row.querySelector('[data-qty-suffix]');
                        const hasStoredPricing = row.dataset.hasStoredPricing === '1';

                        const materialId = materialField ? materialField.value : null;
                        const selectedOption = materialField?.options[materialField.selectedIndex];

                        if (hasStoredPricing) {
                            if (unitPriceField) {
                                const storedUnit = parseFloat(unitPriceField.value || '0');
                                unitPriceField.value = (Number.isFinite(storedUnit) ? storedUnit : 0).toFixed(2);
                            }

                            if (totalPriceField) {
                                const storedTotal = parseFloat(totalPriceField.value || '0');
                                totalPriceField.value = (Number.isFinite(storedTotal) ? storedTotal : 0).toFixed(2);
                            }

                            if (qtySuffix) {
                                const symbol = selectedOption?.dataset.unitSymbol || 'qty';
                                qtySuffix.textContent = symbol || 'qty';
                            }

                            return;
                        }

                        let unitPrice = 0;

                        if (materialId && priceMap[materialId] !== undefined) {
                            unitPrice = parseFloat(priceMap[materialId]);
                        } else if (selectedOption?.dataset.unitPrice) {
                            unitPrice = parseFloat(selectedOption.dataset.unitPrice);
                        }
                        const qty = qtyField ? parseFloat(qtyField.value) || 0 : 0;
                        const totalPrice = qty * unitPrice;

                        if (unitPriceField) {
                            unitPriceField.value = unitPrice.toFixed(2);
                        }

                        if (totalPriceField) {
                            totalPriceField.value = totalPrice.toFixed(2);
                        }

                        if (qtySuffix) {
                            const symbol = selectedOption?.dataset.unitSymbol || 'qty';
                            qtySuffix.textContent = symbol || 'qty';
                        }
                    }

                    function updateSummary() {
                        const total = Array.from(tableBody.querySelectorAll('[data-field="total_price"]'))
                            .reduce((sum, input) => sum + (parseFloat(input.value) || 0), 0);

                        if (totalDisplay) {
                            totalDisplay.textContent = formatCurrency(total);
                        }

                        if (totalInput) {
                            totalInput.value = total.toFixed(2);
                        }
                    }

                    function formatCurrency(amount) {
                        return `Rp ${amount.toLocaleString('id-ID', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2,
                        })}`;
                    }

                    if (tableBody.children.length === 0) {
                        addRow();
                    }
                });
            });
        </script>
    @endpush
@endonce
