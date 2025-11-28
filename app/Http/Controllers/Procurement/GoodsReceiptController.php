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

class GoodsReceiptController extends Controller
{
    private const STATUS_OPTIONS = [
        'draft' => 'Draft',
        'in_progress' => 'Sedang Diproses',
        'completed' => 'Selesai',
        'returned' => 'Diretur',
    ];

    public function __construct(private StockMovementService $stockMovements)
    {
    }

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
            'purchaseOrders' => PurchaseOrder::with([
                'items' => fn ($query) => $query->select('id', 'purchase_order_id', 'material_id', 'qty'),
            ])
                ->orderByDesc('order_date')
                ->orderBy('code')
                ->get(['id', 'code', 'project_id', 'supplier_id']),
            'projects' => Project::orderBy('name')->get(['id', 'name', 'code']),
            'suppliers' => Supplier::orderBy('name')->get(['id', 'name', 'email']),
            'materials' => Material::with('unit')->orderBy('name')->get(),
            'statuses' => self::STATUS_OPTIONS,
            'verifiers' => User::orderBy('name')->get(['id', 'name', 'email']),
            'title' => 'Buat Goods Receipt',
            'user' => Auth::user(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateGoodsReceipt($request);

        $items = $validated['items'];
        unset($validated['items']);

        $attributes = [
            'code' => $validated['code'],
            'purchase_order_id' => $validated['purchase_order_id'] ?? null,
            'project_id' => $validated['project_id'] ?? null,
            'supplier_id' => $validated['supplier_id'] ?? null,
            'received_date' => $validated['received_date'],
            'status' => $validated['status'],
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
            $createdItems = $receipt->items()->createMany($items);
            $receipt->setRelation('items', collect($createdItems));

            $this->stockMovements->syncGoodsReceipt($receipt, $actorId);

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
        $goodsReceipt->load(['items.material.unit']);

        return view('procurement.gr.edit', [
            'goodsReceipt' => $goodsReceipt,
            'purchaseOrders' => PurchaseOrder::with([
                'items' => fn ($query) => $query->select('id', 'purchase_order_id', 'material_id', 'qty'),
            ])
                ->orderByDesc('order_date')
                ->orderBy('code')
                ->get(['id', 'code', 'project_id', 'supplier_id']),
            'projects' => Project::orderBy('name')->get(['id', 'name', 'code']),
            'suppliers' => Supplier::orderBy('name')->get(['id', 'name', 'email']),
            'materials' => Material::with('unit')->orderBy('name')->get(),
            'statuses' => self::STATUS_OPTIONS,
            'verifiers' => User::orderBy('name')->get(['id', 'name', 'email']),
            'title' => 'Ubah Goods Receipt',
            'user' => Auth::user(),
        ]);
    }

    public function update(Request $request, GoodsReceipt $goodsReceipt): RedirectResponse
    {
        $validated = $this->validateGoodsReceipt($request, $goodsReceipt);

        $items = $validated['items'];
        unset($validated['items']);

        $attributes = [
            'code' => $validated['code'],
            'purchase_order_id' => $validated['purchase_order_id'] ?? null,
            'project_id' => $validated['project_id'] ?? null,
            'supplier_id' => $validated['supplier_id'] ?? null,
            'received_date' => $validated['received_date'],
            'status' => $validated['status'],
            'remarks' => $validated['remarks'] ?? null,
        ];

        if (!empty($validated['verified_by'])) {
            $attributes['verified_by'] = $validated['verified_by'];
            $attributes['verified_at'] = $validated['verified_at'] ?? $goodsReceipt->verified_at ?? now();
        } else {
            $attributes['verified_by'] = null;
            $attributes['verified_at'] = null;
        }

        $actorId = Auth::id();

        DB::transaction(function () use ($goodsReceipt, $attributes, $items, $actorId) {
            $this->stockMovements->purgeGoodsReceipt($goodsReceipt);

            $goodsReceipt->update($attributes);
            $goodsReceipt->items()->delete();
            $createdItems = $goodsReceipt->items()->createMany($items);
            $goodsReceipt->setRelation('items', collect($createdItems));

            $this->stockMovements->syncGoodsReceipt($goodsReceipt, $actorId);
        });

        return redirect()
            ->route('procurement.goods-receipts.show', $goodsReceipt)
            ->with('success', 'Goods receipt berhasil diperbarui.');
    }

    public function destroy(GoodsReceipt $goodsReceipt): RedirectResponse
    {
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
        $statuses = array_keys(self::STATUS_OPTIONS);

        $validator = Validator::make($request->all(), [
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('goods_receipts', 'code')->ignore($goodsReceipt?->getKey()),
            ],
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
            ->filter(fn ($item) => !empty($item['material_id']) && !empty($item['qty']))
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
}
