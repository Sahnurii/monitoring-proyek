@php
    $receipt = $goodsReceipt ?? null;
    $defaultItems = [];
    if ($receipt) {
        $receiptItems = collect();
        if (method_exists($receipt, 'items')) {
            $receiptItems = $receipt->relationLoaded('items')
                ? $receipt->items
                : $receipt->items()->get();
        } elseif (isset($receipt->items) && is_iterable($receipt->items)) {
            $receiptItems = collect($receipt->items);
        }

        $defaultItems = $receiptItems->map(function ($item) {
            return [
                'material_id' => $item->material_id ?? null,
                'qty' => $item->qty ?? null,
                'returned_qty' => $item->returned_qty ?? 0,
                'purchase_order_item_id' => $item->purchase_order_item_id ?? null,
                'remarks' => $item->remarks ?? null,
            ];
        })->toArray();
    }
    $oldItems = old('items', $defaultItems);
    if (empty($oldItems)) {
        $oldItems = [
            ['material_id' => null, 'qty' => null, 'returned_qty' => 0, 'remarks' => null],
        ];
    }
    $oldItems = array_values($oldItems);
    $nextIndex = count($oldItems);

    $defaultReceivedDate = $receipt && $receipt->received_date
        ? \Illuminate\Support\Carbon::parse($receipt->received_date)->format('Y-m-d')
        : now()->format('Y-m-d');
    $defaultReceivedDate = old('received_date', $defaultReceivedDate);

    $currentStatus = old('status', optional($receipt)->status ?? 'draft');
    $purchaseOrder = optional($receipt)->purchaseOrder;
    $selectedPurchaseOrderId = old('purchase_order_id', optional($receipt)->purchase_order_id);
    $selectedProjectId = old(
        'project_id',
        optional($receipt)->project_id ?? optional($purchaseOrder)->project_id,
    );
    $selectedSupplierId = old(
        'supplier_id',
        optional($receipt)->supplier_id ?? optional($purchaseOrder)->supplier_id,
    );

    $verifierOptions = collect($verifiers ?? []);
    $selectedVerifierId = old('verified_by', $receipt->verified_by ?? null);
    $defaultVerifiedAt = $receipt && $receipt->verified_at
        ? \Illuminate\Support\Carbon::parse($receipt->verified_at)->format('Y-m-d\TH:i')
        : null;
    $defaultVerifiedAt = old('verified_at', $defaultVerifiedAt);

    $submitLabel = $submitLabel ?? 'Simpan Goods Receipt';

    $purchaseOrderDataset = collect($purchaseOrders ?? [])
        ->mapWithKeys(function ($order) {
            $items = collect(optional($order)->items ?? []);

            return [
                $order->id => [
                    'id' => $order->id,
                    'project_id' => $order->project_id ?? null,
                    'supplier_id' => $order->supplier_id ?? null,
                    'items' => $items
                        ->map(function ($item) {
                            return [
                                'id' => $item->id ?? null,
                                'material_id' => $item->material_id ?? null,
                                'qty' => $item->qty !== null ? (float) $item->qty : null,
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

<form action="{{ $action }}" method="POST" class="row g-4" data-gr-items-container
    data-next-index="{{ $nextIndex }}">
    @csrf
    @if (!empty($method))
        @method($method)
    @endif

    <div class="col-md-4">
        <label for="code" class="form-label">Kode Goods Receipt</label>
        <input type="text" id="code" name="code" value="{{ old('code', $receipt->code ?? '') }}"
            class="form-control @error('code') is-invalid @enderror" placeholder="Contoh: GR-001" required>
        @error('code')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="purchase_order_id" class="form-label">Purchase Order</label>
        <select id="purchase_order_id" name="purchase_order_id"
            class="form-select @error('purchase_order_id') is-invalid @enderror">
            <option value="">Tidak Terhubung</option>
            @foreach ($purchaseOrders as $order)
                <option value="{{ $order->id }}"
                    @selected((string) $selectedPurchaseOrderId === (string) $order->id)>
                    {{ $order->code }}
                </option>
            @endforeach
        </select>
        @error('purchase_order_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="received_date" class="form-label">Tanggal Penerimaan</label>
        <input type="date" id="received_date" name="received_date" value="{{ $defaultReceivedDate }}"
            class="form-control @error('received_date') is-invalid @enderror" required>
        @error('received_date')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="project_id" class="form-label">Proyek</label>
        <select id="project_id" name="project_id" class="form-select @error('project_id') is-invalid @enderror">
            <option value="">Tanpa Proyek</option>
            @foreach ($projects as $project)
                <option value="{{ $project->id }}"
                    @selected((string) $selectedProjectId === (string) $project->id)>
                    {{ $project->code }} &mdash; {{ $project->name }}
                </option>
            @endforeach
        </select>
        @error('project_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="supplier_id" class="form-label">Pemasok</label>
        <select id="supplier_id" name="supplier_id"
            class="form-select @error('supplier_id') is-invalid @enderror">
            <option value="">Tanpa Pemasok</option>
            @foreach ($suppliers as $supplier)
                <option value="{{ $supplier->id }}"
                    @selected((string) $selectedSupplierId === (string) $supplier->id)>
                    {{ $supplier->name }}
                    @if (!empty($supplier->email))
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
        <label for="status" class="form-label">Status</label>
        <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" required>
            @foreach ($statuses as $value => $label)
                <option value="{{ $value }}"
                    @selected((string) $currentStatus === (string) $value)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        @error('status')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="verified_by" class="form-label">Diverifikasi Oleh</label>
        <select id="verified_by" name="verified_by"
            class="form-select @error('verified_by') is-invalid @enderror">
            <option value="">Belum Diverifikasi</option>
            @foreach ($verifierOptions as $verifier)
                @php
                    $verifierId = is_object($verifier) ? $verifier->id : ($verifier['id'] ?? null);
                    $verifierName = is_object($verifier) ? $verifier->name : ($verifier['name'] ?? $verifierId);
                    $verifierEmail = is_object($verifier) ? $verifier->email : ($verifier['email'] ?? null);
                @endphp
                <option value="{{ $verifierId }}"
                    @selected($verifierId !== null && (string) $selectedVerifierId === (string) $verifierId)>
                    {{ $verifierName }}
                    @if ($verifierEmail)
                        &mdash; {{ $verifierEmail }}
                    @endif
                </option>
            @endforeach
        </select>
        @error('verified_by')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4">
        <label for="verified_at" class="form-label">Tanggal Verifikasi</label>
        <input type="datetime-local" id="verified_at" name="verified_at" value="{{ $defaultVerifiedAt }}"
            class="form-control @error('verified_at') is-invalid @enderror">
        @error('verified_at')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <label for="remarks" class="form-label">Catatan</label>
        <textarea id="remarks" name="remarks" rows="3" class="form-control @error('remarks') is-invalid @enderror"
            placeholder="Tambahkan catatan penerimaan">{{ old('remarks', $receipt->remarks ?? '') }}</textarea>
        @error('remarks')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-12">
        <label class="form-label">Daftar Item Diterima</label>
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 35%;">Material</th>
                                <th style="width: 20%;">Jumlah Diterima</th>
                                <th style="width: 20%;">Jumlah Retur</th>
                                <th>Catatan Item</th>
                                <th style="width: 70px;" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody data-items-body>
                            @foreach ($oldItems as $index => $item)
                                <tr data-row>
                                    <td>
                                        <select name="items[{{ $index }}][material_id]" data-field="material_id"
                                            class="form-select @error('items.' . $index . '.material_id') is-invalid @enderror">
                                            <option value="">Pilih Material</option>
                                        @foreach ($materials as $material)
                                            <option value="{{ $material->id }}"
                                                @selected((string) ($item['material_id'] ?? '') === (string) $material->id)>
                                                {{ $material->name }}
                                                @if ($material->unit)
                                                    ({{ $material->unit->symbol ?? $material->unit->name }})
                                                @endif
                                            </option>
                                        @endforeach
                                        </select>
                                        <input type="hidden" name="items[{{ $index }}][purchase_order_item_id]"
                                            data-field="purchase_order_item_id"
                                            value="{{ old('items.' . $index . '.purchase_order_item_id', $item['purchase_order_item_id'] ?? '') }}">
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
                                        <div class="input-group">
                                            <input type="number" min="0" step="0.01"
                                                name="items[{{ $index }}][returned_qty]" data-field="returned_qty"
                                                value="{{ $item['returned_qty'] ?? 0 }}"
                                                class="form-control @error('items.' . $index . '.returned_qty') is-invalid @enderror"
                                                placeholder="0.00">
                                            <span class="input-group-text">qty</span>
                                        </div>
                                        @error('items.' . $index . '.returned_qty')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
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

                <button type="button" class="btn btn-outline-primary mt-3" data-add-item>
                    <i class="bi bi-plus-lg me-2"></i>Tambah Baris Item
                </button>

                @error('items')
                    <div class="text-danger small mt-2">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    <div class="col-12 d-flex justify-content-end gap-2">
        <a href="{{ route('procurement.goods-receipts.index') }}" class="btn btn-outline-secondary">Batal</a>
        <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
    </div>

    <template id="gr-item-row-template">
        <tr data-row>
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
                <input type="hidden" name="items[__INDEX__][purchase_order_item_id]" data-field="purchase_order_item_id"
                    value="">
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
                    <input type="number" min="0" step="0.01" name="items[__INDEX__][returned_qty]"
                        data-field="returned_qty" value="0" class="form-control" placeholder="0.00">
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
                const purchaseOrdersData = @json($purchaseOrderDataset);

                document.querySelectorAll('[data-gr-items-container]').forEach((container) => {
                    const tableBody = container.querySelector('[data-items-body]');
                    if (!tableBody) {
                        return;
                    }

                    const template = container.querySelector('#gr-item-row-template');
                    const purchaseOrderSelect = container.querySelector('#purchase_order_id');
                    const projectSelect = container.querySelector('#project_id');
                    const supplierSelect = container.querySelector('#supplier_id');
                    let nextIndex = Number(container.getAttribute('data-next-index')) || tableBody.children.length;
                    let previousOrderId = purchaseOrderSelect ? purchaseOrderSelect.value : '';

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

                    const addRow = (defaults = {}) => {
                        if (!template) {
                            return null;
                        }

                        const html = template.innerHTML.replace(/__INDEX__/g, nextIndex);
                        const wrapper = document.createElement('tbody');
                        wrapper.innerHTML = html.trim();
                        const row = wrapper.firstElementChild;

                        if (!row) {
                            return null;
                        }

                        const materialField = row.querySelector('[data-field="material_id"]');
                        const qtyField = row.querySelector('[data-field="qty"]');
                        const returnedField = row.querySelector('[data-field="returned_qty"]');
                        const remarksField = row.querySelector('[data-field="remarks"]');
                        const poItemField = row.querySelector('[data-field="purchase_order_item_id"]');

                        if (defaults.material_id !== undefined && defaults.material_id !== null && materialField) {
                            materialField.value = String(defaults.material_id);
                        }

                        if (defaults.qty !== undefined && defaults.qty !== null && qtyField) {
                            qtyField.value = defaults.qty;
                        }

                        if (defaults.returned_qty !== undefined && defaults.returned_qty !== null && returnedField) {
                            returnedField.value = defaults.returned_qty;
                        }

                        if (defaults.remarks !== undefined && defaults.remarks !== null && remarksField) {
                            remarksField.value = defaults.remarks;
                        }

                        if (defaults.purchase_order_item_id !== undefined && defaults.purchase_order_item_id !== null && poItemField) {
                            poItemField.value = String(defaults.purchase_order_item_id);
                        }

                        tableBody.appendChild(row);
                        updateNextIndex(nextIndex + 1);

                        return row;
                    };

                    const clearItems = () => {
                        tableBody.innerHTML = '';
                        updateNextIndex(0);
                    };

                    const hasFilledItems = () => {
                        return Array.from(tableBody.querySelectorAll('tr')).some((row) => {
                            const materialField = row.querySelector('[data-field="material_id"]');
                            const qtyField = row.querySelector('[data-field="qty"]');
                            const returnedField = row.querySelector('[data-field="returned_qty"]');
                            const remarksField = row.querySelector('[data-field="remarks"]');
                            const poItemField = row.querySelector('[data-field="purchase_order_item_id"]');

                            const hasMaterial = materialField && materialField.value;
                            const hasQty = qtyField && qtyField.value && parseFloat(qtyField.value) > 0;
                            const hasReturned = returnedField && returnedField.value && parseFloat(returnedField.value) > 0;
                            const hasRemarks = remarksField && remarksField.value.trim() !== '';
                            const hasPoItem = poItemField && poItemField.value;

                            return hasMaterial || hasQty || hasReturned || hasRemarks || hasPoItem;
                        });
                    };

                    const populateFromOrder = (order) => {
                        if (!order) {
                            return;
                        }

                        setSelectValue(projectSelect, order.project_id ?? '');
                        setSelectValue(supplierSelect, order.supplier_id ?? '');

                        const items = Array.isArray(order.items) ? order.items : [];

                        clearItems();

                        if (items.length === 0) {
                            addRow();
                            return;
                        }

                        items.forEach((item) => {
                            addRow({
                                material_id: item.material_id ?? null,
                                qty: item.qty ?? null,
                                returned_qty: 0,
                                purchase_order_item_id: item.id ?? null,
                            });
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
                        }

                        if (tableBody.children.length === 0) {
                            addRow();
                        }
                    });

                    const addButton = container.querySelector('[data-add-item]');
                    if (addButton) {
                        addButton.addEventListener('click', () => {
                            addRow();
                        });
                    }

                    if (purchaseOrderSelect) {
                        purchaseOrderSelect.addEventListener('change', () => {
                            const selectedId = purchaseOrderSelect.value;

                            if (!selectedId) {
                                previousOrderId = '';
                                return;
                            }

                            const order = purchaseOrdersData[selectedId];
                            if (!order) {
                                previousOrderId = selectedId;
                                return;
                            }

                            if (hasFilledItems()) {
                                const confirmReplace = window.confirm(
                                    'Mengambil item dari purchase order akan menggantikan daftar item saat ini. Lanjutkan?'
                                );

                                if (!confirmReplace) {
                                    purchaseOrderSelect.value = previousOrderId;
                                    return;
                                }
                            }

                            populateFromOrder(order);
                            previousOrderId = selectedId;
                        });

                        if (purchaseOrderSelect.value && !hasFilledItems()) {
                            const initialOrder = purchaseOrdersData[purchaseOrderSelect.value];
                            if (initialOrder) {
                                populateFromOrder(initialOrder);
                                previousOrderId = purchaseOrderSelect.value;
                            }
                        }
                    }

                    if (tableBody.children.length === 0) {
                        addRow();
                    }
                });
            });
        </script>
    @endpush
@endonce
