@php
    $order = $purchaseOrder ?? null;
    $defaultItems = $order
        ? $order->items->map(function ($item) {
            return [
                'material_id' => $item->material_id,
                'qty' => $item->qty,
                'price' => $item->price,
            ];
        })->toArray()
        : [];
    $oldItems = old('items', $defaultItems);
    if (empty($oldItems)) {
        $oldItems = [
            ['material_id' => null, 'qty' => null, 'price' => null],
        ];
    }
    $oldItems = array_values($oldItems);
    $nextIndex = count($oldItems);
    $defaultOrderDate = $order && $order->order_date
        ? $order->order_date->format('Y-m-d')
        : now()->format('Y-m-d');
    $defaultOrderDate = old('order_date', $defaultOrderDate);
    $currentStatus = old('status', $order->status ?? 'draft');
    $totalAmount = 0;
    $materialRequestDataset = collect($materialRequests ?? [])
        ->mapWithKeys(function ($requestOption) {
            $items = collect(optional($requestOption)->items ?? []);

            return [
                $requestOption->id => [
                    'id' => $requestOption->id,
                    'project_id' => $requestOption->project_id ?? null,
                    'items' => $items
                        ->map(function ($item) {
                            return [
                                'material_id' => $item->material_id ?? null,
                                'qty' => $item->qty !== null ? (float) $item->qty : null,
                                'remarks' => $item->remarks ?? null,
                            ];
                        })
                        ->values(),
                ],
            ];
        })
        ->toArray();
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

<form action="{{ $action }}" method="POST" class="row g-4" data-po-items-container data-next-index="{{ $nextIndex }}">
    @csrf
    @if (!empty($method))
        @method($method)
    @endif

    <div class="col-md-4">
        <label for="code" class="form-label">Kode Purchase Order</label>
        <input type="text" id="code" name="code" value="{{ old('code', $order->code ?? '') }}"
            class="form-control @error('code') is-invalid @enderror" placeholder="Contoh: PO-001" required>
        @error('code')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="supplier_id" class="form-label">Pemasok</label>
        <select id="supplier_id" name="supplier_id"
            class="form-select @error('supplier_id') is-invalid @enderror" required>
            <option value="">Pilih Pemasok</option>
            @foreach ($suppliers as $supplier)
                <option value="{{ $supplier->id }}"
                    @selected((string) old('supplier_id', $order->supplier_id ?? '') === (string) $supplier->id)>
                    {{ $supplier->name }}
                    @if ($supplier->email)
                        &mdash; {{ $supplier->email }}
                    @endif
                </option>
            @endforeach
        </select>
        @error('supplier_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="project_id" class="form-label">Proyek</label>
        <select id="project_id" name="project_id" class="form-select @error('project_id') is-invalid @enderror">
            <option value="">Tanpa Proyek</option>
            @foreach ($projects as $project)
                <option value="{{ $project->id }}"
                    @selected((string) old('project_id', $order->project_id ?? '') === (string) $project->id)>
                    {{ $project->code }} &mdash; {{ $project->name }}
                </option>
            @endforeach
        </select>
        @error('project_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="material_request_id" class="form-label">Permintaan Material</label>
        <select id="material_request_id" name="material_request_id"
            class="form-select @error('material_request_id') is-invalid @enderror">
            <option value="">Tidak Terhubung</option>
            @foreach ($materialRequests as $requestOption)
                <option value="{{ $requestOption->id }}"
                    @selected((string) old('material_request_id', $order->material_request_id ?? '') === (string) $requestOption->id)>
                    {{ $requestOption->code }}
                </option>
            @endforeach
        </select>
        @error('material_request_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="order_date" class="form-label">Tanggal Pemesanan</label>
        <input type="date" id="order_date" name="order_date" value="{{ $defaultOrderDate }}"
            class="form-control @error('order_date') is-invalid @enderror" required>
        @error('order_date')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="status" class="form-label">Status</label>
        <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" required>
            @foreach ($statuses as $value => $label)
                <option value="{{ $value }}" @selected((string) $currentStatus === (string) $value)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        @error('status')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <label class="form-label">Item Purchase Order</label>
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 35%;">Material</th>
                                <th style="width: 20%;">Jumlah</th>
                                <th style="width: 20%;">Harga Satuan</th>
                                <th style="width: 18%;" class="text-end">Subtotal</th>
                                <th style="width: 70px;" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody data-items-body>
                            @foreach ($oldItems as $index => $item)
                                @php
                                    $materialId = $item['material_id'] ?? null;
                                    $qtyValue = $item['qty'] ?? null;
                                    $priceValue = $item['price'] ?? null;
                                    $qtyNumber = $qtyValue !== null ? (float) $qtyValue : 0;
                                    $priceNumber = $priceValue !== null ? (float) $priceValue : 0;
                                    $subtotalValue = $qtyNumber * $priceNumber;
                                    $totalAmount += $subtotalValue;
                                @endphp
                                <tr data-row data-subtotal="{{ number_format($subtotalValue, 2, '.', '') }}">
                                    <td>
                                        <select name="items[{{ $index }}][material_id]" data-field="material_id"
                                            class="form-select @error('items.' . $index . '.material_id') is-invalid @enderror">
                                            <option value="">Pilih Material</option>
                                            @foreach ($materials as $material)
                                                <option value="{{ $material->id }}"
                                                    @selected((string) ($materialId ?? '') === (string) $material->id)>
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
                                                value="{{ old('items.' . $index . '.qty', $qtyValue) }}"
                                                class="form-control @error('items.' . $index . '.qty') is-invalid @enderror"
                                                placeholder="0.00">
                                            <span class="input-group-text">qty</span>
                                        </div>
                                        @error('items.' . $index . '.qty')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </td>
                                    <td>
                                        <div class="input-group">
                                            <span class="input-group-text">Rp</span>
                                            <input type="number" min="0" step="0.01"
                                                name="items[{{ $index }}][price]" data-field="price"
                                                value="{{ old('items.' . $index . '.price', $priceValue) }}"
                                                class="form-control @error('items.' . $index . '.price') is-invalid @enderror"
                                                placeholder="0.00">
                                        </div>
                                        @error('items.' . $index . '.price')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </td>
                                    <td class="text-end">
                                        <span class="fw-semibold" data-subtotal-display>
                                            Rp {{ number_format($subtotalValue, 2, ',', '.') }}
                                        </span>
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
                    <i class="bi bi-plus-lg me-2"></i>Tambah Baris Item
                </button>

                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mt-4">
                    <div class="text-muted">Total Purchase Order</div>
                    <div class="fs-5 fw-bold" data-total-amount>
                        Rp {{ number_format($totalAmount, 2, ',', '.') }}
                    </div>
                </div>

                @error('items')
                    <div class="text-danger small mt-2">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    <div class="col-12 d-flex justify-content-end gap-2">
        <a href="{{ route('procurement.purchase-orders.index') }}" class="btn btn-outline-secondary">Batal</a>
        <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
    </div>

    <template id="po-item-row-template">
        <tr data-row data-subtotal="0">
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
                    <input type="number" min="0" step="0.01" name="items[__INDEX__][qty]" data-field="qty"
                        class="form-control" placeholder="0.00">
                    <span class="input-group-text">qty</span>
                </div>
            </td>
            <td>
                <div class="input-group">
                    <span class="input-group-text">Rp</span>
                    <input type="number" min="0" step="0.01" name="items[__INDEX__][price]" data-field="price"
                        class="form-control" placeholder="0.00">
                </div>
            </td>
            <td class="text-end">
                <span class="fw-semibold" data-subtotal-display>Rp 0,00</span>
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
                const materialRequestsData = @json($materialRequestDataset);

                document.querySelectorAll('[data-po-items-container]').forEach((container) => {
                    const tableBody = container.querySelector('[data-items-body]');
                    const template = container.querySelector('#po-item-row-template');
                    const totalElement = container.querySelector('[data-total-amount]');
                    let nextIndex = Number(container.getAttribute('data-next-index')) || tableBody.children.length;
                    const materialRequestSelect = container.querySelector('#material_request_id');
                    const projectSelect = container.querySelector('#project_id');

                    if (!tableBody) {
                        return;
                    }

                    const currencyFormatter = new Intl.NumberFormat('id-ID', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2,
                    });

                    const formatCurrency = (value) => `Rp ${currencyFormatter.format(value ?? 0)}`;

                    const updateNextIndex = (value) => {
                        nextIndex = value;
                        container.setAttribute('data-next-index', String(nextIndex));
                    };

                    const setSelectValue = (select, value) => {
                        if (!select) {
                            return;
                        }

                        const normalized = value === null || value === undefined || value === '' ? '' : String(value);
                        if (select.value !== normalized) {
                            select.value = normalized;
                            select.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                    };

                    const recalcRow = (row) => {
                        const qtyField = row.querySelector('[data-field="qty"]');
                        const priceField = row.querySelector('[data-field="price"]');
                        const subtotalDisplay = row.querySelector('[data-subtotal-display]');

                        const qty = parseFloat(qtyField?.value ?? '0');
                        const price = parseFloat(priceField?.value ?? '0');
                        const subtotal = (Number.isFinite(qty) ? qty : 0) * (Number.isFinite(price) ? price : 0);

                        row.dataset.subtotal = subtotal.toFixed(2);

                        if (subtotalDisplay) {
                            subtotalDisplay.textContent = formatCurrency(subtotal);
                        }
                    };

                    const recalcTotals = () => {
                        let total = 0;
                        tableBody.querySelectorAll('tr').forEach((row) => {
                            const subtotal = parseFloat(row.dataset.subtotal ?? '0');
                            if (!Number.isNaN(subtotal)) {
                                total += subtotal;
                            }
                        });

                        if (totalElement) {
                            totalElement.textContent = formatCurrency(total);
                        }
                    };

                    const addRow = (defaults = {}) => {
                        if (!template) {
                            return;
                        }

                        const html = template.innerHTML.replace(/__INDEX__/g, nextIndex);
                        const wrapper = document.createElement('tbody');
                        wrapper.innerHTML = html.trim();
                        const row = wrapper.firstElementChild;

                        if (!row) {
                            return;
                        }

                        const materialField = row.querySelector('[data-field="material_id"]');
                        const qtyField = row.querySelector('[data-field="qty"]');
                        const priceField = row.querySelector('[data-field="price"]');

                        if (defaults.material_id !== undefined && defaults.material_id !== null && materialField) {
                            materialField.value = String(defaults.material_id);
                        }

                        if (defaults.qty !== undefined && defaults.qty !== null && qtyField) {
                            qtyField.value = defaults.qty;
                        }

                        if (defaults.price !== undefined && defaults.price !== null && priceField) {
                            priceField.value = defaults.price;
                        }

                        tableBody.appendChild(row);
                        updateNextIndex(nextIndex + 1);

                        recalcRow(row);
                        recalcTotals();

                        return row;
                    };

                    const clearItems = () => {
                        tableBody.innerHTML = '';
                        updateNextIndex(0);
                    };

                    const populateFromMaterialRequest = (request) => {
                        if (!request) {
                            return;
                        }

                        const items = Array.isArray(request.items) ? request.items : [];

                        clearItems();

                        if (items.length === 0) {
                            addRow();
                            return;
                        }

                        items.forEach((item) => {
                            addRow({
                                material_id: item.material_id ?? null,
                                qty: item.qty ?? null,
                                price: null,
                            });
                        });
                    };

                    const hasFilledItems = () => {
                        return Array.from(tableBody.querySelectorAll('tr')).some((row) => {
                            const materialField = row.querySelector('[data-field="material_id"]');
                            const qtyField = row.querySelector('[data-field="qty"]');
                            const priceField = row.querySelector('[data-field="price"]');

                            return (materialField && materialField.value) ||
                                (qtyField && qtyField.value) ||
                                (priceField && priceField.value);
                        });
                    };

                    tableBody.addEventListener('click', (event) => {
                        const removeButton = event.target.closest('[data-remove-item]');
                        if (!removeButton) {
                            return;
                        }

                        event.preventDefault();
                        const row = removeButton.closest('tr');
                        if (row) {
                            row.remove();
                            recalcTotals();
                        }

                        if (tableBody.children.length === 0) {
                            addRow();
                        }
                    });

                    tableBody.addEventListener('input', (event) => {
                        if (!event.target.matches('[data-field="qty"], [data-field="price"]')) {
                            return;
                        }

                        const row = event.target.closest('tr');
                        if (!row) {
                            return;
                        }

                        recalcRow(row);
                        recalcTotals();
                    });

                    const addButton = container.querySelector('[data-add-item]');
                    if (addButton) {
                        addButton.addEventListener('click', () => {
                            addRow();
                        });
                    }

                    if (materialRequestSelect) {
                        materialRequestSelect.addEventListener('change', () => {
                            const selectedId = materialRequestSelect.value;
                            if (!selectedId) {
                                return;
                            }

                            const request = materialRequestsData[selectedId];
                            if (!request) {
                                return;
                            }

                            setSelectValue(projectSelect, request.project_id ?? '');

                            if (Array.isArray(request.items) && request.items.length > 0) {
                                if (hasFilledItems()) {
                                    const confirmReplace = window.confirm(
                                        'Mengambil item dari permintaan material akan menggantikan daftar item saat ini. Lanjutkan?'
                                    );

                                    if (!confirmReplace) {
                                        return;
                                    }
                                }

                                populateFromMaterialRequest(request);
                            }
                        });

                        if (materialRequestSelect.value && !hasFilledItems()) {
                            const initialRequest = materialRequestsData[materialRequestSelect.value];
                            if (initialRequest) {
                                setSelectValue(projectSelect, initialRequest.project_id ?? '');
                                populateFromMaterialRequest(initialRequest);
                            }
                        }
                    }

                    tableBody.querySelectorAll('tr').forEach((row) => {
                        recalcRow(row);
                    });
                    recalcTotals();
                });
            });
        </script>
    @endpush
@endonce
