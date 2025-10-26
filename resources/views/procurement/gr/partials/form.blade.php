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
