<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use App\Models\GoodsReceipt;
use App\Models\Material;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Inventory\StockMovementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;

class GoodsReceiptController extends Controller
{
    private const STATUS_OPTIONS = [
        'draft' => 'Draft',
        'in_progress' => 'Sedang Diproses',
        'completed' => 'Selesai',
        'returned' => 'Diretur',
    ];

    public function __construct(private StockMovementService $stockMovements) {}

    public function index(Request $request): View
    {
        $receiptsQuery = GoodsReceipt::query()
            ->with([
                'purchaseOrder.supplier',
                'purchaseOrder.project',
                'project',
                'supplier',
                'receiver',
            ])
            ->withCount('items');

        if ($search = trim((string) $request->input('search'))) {
            $receiptsQuery->where(function ($query) use ($search) {
                $query->where('code', 'like', "%{$search}%")
                    ->orWhereHas('supplier', function ($supplierQuery) use ($search) {
                        $supplierQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('purchaseOrder.supplier', function ($supplierQuery) use ($search) {
                        $supplierQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('project', function ($projectQuery) use ($search) {
                        $projectQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    })
                    ->orWhereHas('purchaseOrder.project', function ($projectQuery) use ($search) {
                        $projectQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    });
            });
        }

        if ($status = (string) $request->input('status')) {
            $receiptsQuery->where('status', $status);
        }

        if ($purchaseOrderId = $request->input('purchase_order_id')) {
            $receiptsQuery->where('purchase_order_id', $purchaseOrderId);
        }

        if ($projectId = $request->input('project_id')) {
            $receiptsQuery->where(function ($query) use ($projectId) {
                $query->where('project_id', $projectId)
                    ->orWhereHas('purchaseOrder', function ($poQuery) use ($projectId) {
                        $poQuery->where('project_id', $projectId);
                    });
            });
        }

        if ($supplierId = $request->input('supplier_id')) {
            $receiptsQuery->where(function ($query) use ($supplierId) {
                $query->where('supplier_id', $supplierId)
                    ->orWhereHas('purchaseOrder', function ($poQuery) use ($supplierId) {
                        $poQuery->where('supplier_id', $supplierId);
                    });
            });
        }

        if ($receivedBy = $request->input('received_by')) {
            $receiptsQuery->where('received_by', $receivedBy);
        }

        if ($receivedDate = $request->input('received_date')) {
            $receiptsQuery->whereDate('received_date', $receivedDate);
        }

        $perPage = $request->integer('per_page', 10) ?: 10;
        $perPage = max(min($perPage, 100), 1);

        $receipts = $receiptsQuery
            ->orderByDesc('received_date')
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();

        return view('procurement.gr.index', [
            'receipts' => $receipts,
            'statuses' => self::STATUS_OPTIONS,
            'purchaseOrders' => PurchaseOrder::orderByDesc('order_date')->orderBy('code')->get(['id', 'code']),
            'projects' => Project::orderBy('name')->get(['id', 'name', 'code']),
            'suppliers' => Supplier::orderBy('name')->get(['id', 'name', 'email']),
            'receivers' => User::orderBy('name')->get(['id', 'name', 'email']),
            'title' => 'Goods Receipt',
            'user' => Auth::user(),
        ]);
    }

    public function create(): View
    {
        return view('procurement.gr.create', [
            'goodsReceipt' => new GoodsReceipt(),
            'purchaseOrders' => PurchaseOrder::query()
                ->whereIn('status', ['approved', 'partial'])
                ->with([
                    'items' => fn($q) => $q->select('id', 'purchase_order_id', 'material_id', 'qty'),
                ])
                ->orderByDesc('approved_at')
                ->orderBy('code')
                ->get(['id', 'code', 'project_id', 'supplier_id']),

            'projects' => Project::orderBy('name')->get(['id', 'name', 'code']),
            'suppliers' => Supplier::orderBy('name')->get(['id', 'name', 'email']),
            'materials' => Material::with('unit')->orderBy('name')->get(),

            'statuses' => [
                'draft' => 'Draft',
            ],

            'verifiers' => User::orderBy('name')->get(['id', 'name', 'email']),
            'title' => 'Buat Goods Receipt',
            'user' => Auth::user(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {

        $validated = $this->validateGoodsReceipt($request);

        if (!empty($validated['purchase_order_id'])) {
            $po = PurchaseOrder::findOrFail($validated['purchase_order_id']);

            if (!in_array($po->status, ['approved', 'partial'], true)) {
                return back()
                    ->withInput()
                    ->with('error', 'Goods Receipt hanya bisa dibuat dari PO yang disetujui.');
            }
        }

        $items = $validated['items'];
        unset($validated['items']);

        if (!empty($validated['purchase_order_id'])) {
            $this->validateAgainstPurchaseOrder(
                $items,
                (int) $validated['purchase_order_id']
            );
        }

        $attributes = [
            'code' => GoodsReceipt::generateCode(),
            'purchase_order_id' => $po->id,
            'project_id' => $po->project_id,
            'supplier_id' => $po->supplier_id,
            'received_date' => $validated['received_date'],
            'status' => 'draft',
            'received_by' => Auth::id(),
            'remarks' => $validated['remarks'] ?? null,
        ];

        if (!empty($validated['verified_by'])) {
            $attributes['verified_by'] = $validated['verified_by'];
            $attributes['verified_at'] = $validated['verified_at'] ?? now();
        } else {
            $attributes['verified_by'] = null;
            $attributes['verified_at'] = null;
        }

        $actorId = Auth::id();

        $goodsReceipt = DB::transaction(function () use ($attributes, $items, $actorId) {
            $receipt = GoodsReceipt::create($attributes);
            $receipt->items()->createMany($items);
            return $receipt;
        });

        return redirect()
            ->route('procurement.goods-receipts.show', $goodsReceipt)
            ->with('success', 'Goods receipt berhasil ditambahkan.');
    }

    public function show(GoodsReceipt $goodsReceipt): View
    {
        $goodsReceipt->load([
            'purchaseOrder.supplier',
            'purchaseOrder.project',
            'project',
            'supplier',
            'receiver',
            'verifier',
            'items.material.unit',
            'items.purchaseOrderItem',
        ]);

        return view('procurement.gr.show', [
            'goodsReceipt' => $goodsReceipt,
            'statuses' => self::STATUS_OPTIONS,
            'title' => 'Detail Goods Receipt',
            'user' => Auth::user(),
        ]);
    }

    public function edit(GoodsReceipt $goodsReceipt): View
    {
        if ($goodsReceipt->status === 'completed') {
            abort(403, 'Goods Receipt yang sudah selesai tidak dapat diubah.');
        }

        $allowedTransitions = [
            'draft' => ['draft', 'in_progress'],
            'in_progress' => ['in_progress', 'completed'],
        ];

        $allowedStatuses = collect(self::STATUS_OPTIONS)
            ->only($allowedTransitions[$goodsReceipt->status] ?? [])
            ->toArray();


        return view('procurement.gr.edit', [
            'goodsReceipt' => $goodsReceipt->load(['items.material.unit']),
            'purchaseOrders' => PurchaseOrder::whereIn('status', ['approved', 'partial'])->get(),
            'projects' => Project::orderBy('name')->get(['id', 'name', 'code']),
            'suppliers' => Supplier::orderBy('name')->get(['id', 'name', 'email']),
            'materials' => Material::with('unit')->orderBy('name')->get(),
            'statuses' => $allowedStatuses,
            'verifiers' => User::orderBy('name')->get(['id', 'name', 'email']),
            'title' => 'Ubah Goods Receipt',
            'user' => Auth::user(),
        ]);
    }

    public function update(Request $request, GoodsReceipt $goodsReceipt): RedirectResponse
    {
        $validated = $this->validateGoodsReceipt($request, $goodsReceipt);

        // Normalisasi ke integer untuk perbandingan
        $requestPoId = !empty($validated['purchase_order_id'])
            ? (int) $validated['purchase_order_id']
            : null;

        $existingPoId = $goodsReceipt->purchase_order_id
            ? (int) $goodsReceipt->purchase_order_id
            : null;

        if ($requestPoId !== $existingPoId) {
            return back()->with('error', 'Purchase Order tidak dapat diubah.');
        }

        $items = $validated['items'];
        unset($validated['items']);

        if (!empty($validated['purchase_order_id'])) {
            $this->validateAgainstPurchaseOrder(
                $items,
                (int) $validated['purchase_order_id'],
                $goodsReceipt->id
            );
        }

        $oldStatus = $goodsReceipt->status;
        $newStatus = $validated['status'];

        $attributes = [
            'received_date' => $validated['received_date'],
            'status' => $newStatus,
            'remarks' => $validated['remarks'] ?? null,
        ];

        if ($newStatus === 'completed') {
            if (!empty($validated['verified_by'])) {
                $attributes['verified_by'] = $validated['verified_by'];
                $attributes['verified_at'] = $validated['verified_at'] ?? now();
            } elseif (empty($goodsReceipt->verified_by)) {
                $attributes['verified_by'] = Auth::id();
                $attributes['verified_at'] = now();
            }
        } else {
            if (!empty($validated['verified_by'])) {
                $attributes['verified_by'] = $validated['verified_by'];
                $attributes['verified_at'] = $validated['verified_at'] ?? now();
            } else {
                $attributes['verified_by'] = null;
                $attributes['verified_at'] = null;
            }
        }

        $actorId = Auth::id();

        DB::transaction(function () use (
            $goodsReceipt,
            $attributes,
            $items,
            $actorId,
            $oldStatus,
            $newStatus
        ) {
            if ($oldStatus === 'completed') {
                $this->stockMovements->purgeGoodsReceipt($goodsReceipt);
            }

            $goodsReceipt->update($attributes);
            $goodsReceipt->items()->delete();
            $goodsReceipt->items()->createMany($items);

            if ($newStatus === 'completed') {
                $this->stockMovements->syncGoodsReceipt($goodsReceipt, $actorId);
            }

            if (
                $goodsReceipt->purchase_order_id &&
                $newStatus === 'completed'
            ) {
                $this->syncPurchaseOrderStatus($goodsReceipt->purchaseOrder);
            }
        });

        return redirect()
            ->route('procurement.goods-receipts.show', $goodsReceipt)
            ->with('success', 'Goods receipt berhasil diperbarui.');
    }

    public function destroy(GoodsReceipt $goodsReceipt): RedirectResponse
    {
        if ($goodsReceipt->status === 'completed') {
            return back()->with('error', 'Goods Receipt yang sudah selesai tidak dapat dihapus.');
        }

        DB::transaction(function () use ($goodsReceipt) {
            $this->stockMovements->purgeGoodsReceipt($goodsReceipt);
            $goodsReceipt->items()->delete();
            $goodsReceipt->delete();
        });

        return redirect()
            ->route('procurement.goods-receipts.index')
            ->with('success', 'Goods receipt berhasil dihapus.');
    }

    protected function validateGoodsReceipt(Request $request, ?GoodsReceipt $goodsReceipt = null): array
    {
        if ($goodsReceipt) {
            $allowedTransitions = [
                'draft' => ['draft', 'in_progress'],
                'in_progress' => ['in_progress', 'completed'],
            ];

            $statuses = $allowedTransitions[$goodsReceipt->status] ?? [];
        } else {
            $statuses = ['draft'];
        }

        $validator = Validator::make($request->all(), [
            'purchase_order_id' => ['nullable', 'exists:purchase_orders,id'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'received_date' => ['required', 'date'],
            'status' => ['required', Rule::in($statuses)],
            'verified_by' => ['nullable', 'exists:users,id'],
            'verified_at' => ['nullable', 'date'],
            'remarks' => ['nullable', 'string'],
            'items' => ['nullable', 'array'],
            'items.*.material_id' => ['nullable', 'exists:materials,id'],
            'items.*.qty' => ['nullable', 'numeric', 'min:0.01'],
            'items.*.returned_qty' => ['nullable', 'numeric', 'min:0'],
            'items.*.purchase_order_item_id' => ['nullable', 'exists:purchase_order_items,id'],
            'items.*.remarks' => ['nullable', 'string', 'max:255'],
        ]);

        $data = $validator->validate();

        $items = $this->sanitizeItems($data['items'] ?? []);

        if (empty($items)) {
            throw ValidationException::withMessages([
                'items' => 'Tambahkan minimal satu item penerimaan.',
            ]);
        }

        $data['items'] = $items;

        return $data;
    }

    protected function sanitizeItems(array $items): array
    {
        return collect($items)
            ->map(function ($item) {
                return [
                    'material_id' => $item['material_id'] ?? null,
                    'qty' => isset($item['qty']) ? (float) $item['qty'] : null,
                    'returned_qty' => isset($item['returned_qty']) ? (float) $item['returned_qty'] : 0,
                    'remarks' => $item['remarks'] ?? null,
                    'purchase_order_item_id' => $item['purchase_order_item_id'] ?? null,
                ];
            })
            ->filter(fn($item) => !empty($item['material_id']) && !empty($item['qty']))
            ->map(function ($item) {
                $qty = round((float) $item['qty'], 2);
                $returnedQty = round((float) max($item['returned_qty'] ?? 0, 0), 2);

                if ($returnedQty > $qty) {
                    $returnedQty = $qty;
                }

                return [
                    'material_id' => $item['material_id'],
                    'qty' => $qty,
                    'returned_qty' => $returnedQty,
                    'remarks' => $item['remarks'] ?? null,
                    'purchase_order_item_id' => $item['purchase_order_item_id'] ?? null,
                ];
            })
            ->values()
            ->all();
    }

    protected function validateAgainstPurchaseOrder(
        array $items,
        int $poId,
        ?int $ignoreGoodsReceiptId = null
    ): void {
        $po = PurchaseOrder::with('items')->find($poId);

        if (!$po) {
            throw ValidationException::withMessages([
                'purchase_order_id' => 'Purchase Order tidak ditemukan.',
            ]);
        }

        // Buat map dari PO items berdasarkan purchase_order_item_id
        $poItemsMap = collect($po->items)->keyBy('id');

        foreach ($items as $index => $item) {

            if (empty($item['purchase_order_item_id'])) {
                continue;
            }

            $poItemId = $item['purchase_order_item_id'];
            $requestedQty = (float) $item['qty'];
            $materialId = $item['material_id'];

            $poItem = $poItemsMap->get($poItemId);

            if (!$poItem) {
                throw ValidationException::withMessages([
                    "items.$index.material_id" => "Material tidak terdapat dalam Purchase Order.",
                ]);
            }

            if ((int) $poItem->material_id !== (int) $materialId) {
                throw ValidationException::withMessages([
                    "items.$index.material_id" => "Material tidak sesuai dengan Purchase Order item.",
                ]);
            }

            $query = DB::table('goods_receipt_items')
                ->join('goods_receipts', 'goods_receipt_items.goods_receipt_id', '=', 'goods_receipts.id')
                ->where('goods_receipts.purchase_order_id', $poId)
                ->whereIn('goods_receipts.status', ['draft', 'in_progress', 'completed'])
                ->where('goods_receipt_items.purchase_order_item_id', $poItemId);

            if ($ignoreGoodsReceiptId) {
                $query->where('goods_receipts.id', '!=', $ignoreGoodsReceiptId);
            }

            $grTotals = $query->selectRaw('
            SUM(goods_receipt_items.qty) as total_received,
            SUM(goods_receipt_items.returned_qty) as total_returned
            ')->first();

            $totalReceived = (float) ($grTotals->total_received ?? 0);
            $totalReturned = (float) ($grTotals->total_returned ?? 0);

            $effectiveReceived = max($totalReceived - $totalReturned, 0);

            $remainingQty = round($poItem->qty - $effectiveReceived, 2);

            if ($requestedQty > $remainingQty) {
                $material = Material::find($materialId);
                $materialName = $material ? $material->name : "Material ID {$materialId}";

                throw ValidationException::withMessages([
                    "items.$index.qty" =>
                    "Jumlah {$materialName} melebihi sisa PO. " .
                        "Sisa: {$remainingQty}, " .
                        "PO: {$poItem->qty}, " .
                        "Diterima: {$totalReceived}, " .
                        "Retur: {$totalReturned}, " .
                        "Efektif: {$effectiveReceived}",
                ]);
            }

            foreach ($items as $index => $item) {
                $materialId = $item['material_id'];

                if (!empty($item['purchase_order_item_id'])) {
                    continue;
                }

                $poHasMaterial = $poItemsMap->contains(function ($poItem) use ($materialId) {
                    return (int) $poItem->material_id === (int) $materialId;
                });

                if (!$poHasMaterial) {
                    $material = Material::find($materialId);
                    $materialName = $material ? $material->name : "Material ID {$materialId}";

                    throw ValidationException::withMessages([
                        "items.$index.material_id" =>
                        "{$materialName} tidak terdapat dalam Purchase Order yang dipilih.",
                    ]);
                }
            }

        }
    }

    protected function syncPurchaseOrderStatus(PurchaseOrder $po): void
    {
        $poItems = $po->items;
        $allReceived = true;

        foreach ($poItems as $item) {
            $grTotals = DB::table('goods_receipt_items')
                ->join('goods_receipts', 'goods_receipt_items.goods_receipt_id', '=', 'goods_receipts.id')
                ->where('goods_receipts.purchase_order_id', $po->id)
                ->where('goods_receipts.status', 'completed')
                ->where('goods_receipt_items.purchase_order_item_id', $item->id)
                ->selectRaw('SUM(goods_receipt_items.qty) as total_received, SUM(goods_receipt_items.returned_qty) as total_returned')
                ->first();

            $effectiveReceived = max(
                (float) ($grTotals->total_received ?? 0) - (float) ($grTotals->total_returned ?? 0),
                0
            );

            $remainingQty = max($item->qty - $effectiveReceived, 0);

            if ($remainingQty > 0) {
                $allReceived = false;
                break;
            }
        }

        $po->update([
            'status' => $allReceived ? 'received' : 'partial',
            'received_at' => $allReceived ? now() : null,
        ]);
    }

}
