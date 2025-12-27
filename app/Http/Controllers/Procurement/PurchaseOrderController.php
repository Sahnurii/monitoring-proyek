<?php

namespace App\Http\Controllers\Procurement;

use App\Models\Project;
use App\Models\Material;
use App\Models\Supplier;
use Illuminate\View\View;
use Illuminate\Http\Request;
use App\Models\PurchaseOrder;
use App\Models\MaterialRequest;
use Illuminate\Validation\Rule;
use App\Models\PurchaseOrderItem;
use Illuminate\Support\Facades\DB;
use App\Models\MaterialRequestItem;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PurchaseOrderController extends Controller
{
    private const STATUS_OPTIONS = [
        'draft' => 'Draft',
        'approved' => 'Disetujui',
        'partial' => 'Sebagian Diterima',
        'received' => 'Sudah Diterima',
        'canceled' => 'Dibatalkan',
    ];


    public function index(Request $request): View
    {
        $ordersQuery = PurchaseOrder::query()
            ->with(['supplier', 'project', 'materialRequest', 'approver'])
            ->withCount('items');

        if ($search = trim((string) $request->input('search'))) {
            $ordersQuery->where(function ($query) use ($search) {
                $query->where('code', 'like', "%{$search}%")
                    ->orWhereHas('supplier', function ($supplierQuery) use ($search) {
                        $supplierQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('project', function ($projectQuery) use ($search) {
                        $projectQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    });
            });
        }

        if ($status = (string) $request->input('status')) {
            $ordersQuery->where('status', $status);
        }

        if ($supplierId = $request->input('supplier_id')) {
            $ordersQuery->where('supplier_id', $supplierId);
        }

        if ($projectId = $request->input('project_id')) {
            $ordersQuery->where('project_id', $projectId);
        }

        if ($materialRequestId = $request->input('material_request_id')) {
            $ordersQuery->where('material_request_id', $materialRequestId);
        }

        if ($orderDate = $request->input('order_date')) {
            $ordersQuery->whereDate('order_date', $orderDate);
        }

        $perPage = $request->integer('per_page', 10) ?: 10;
        $perPage = max(min($perPage, 100), 1);

        $orders = $ordersQuery
            ->orderByDesc('order_date')
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();

        return view('procurement.po.index', [
            'orders' => $orders,
            'statuses' => self::STATUS_OPTIONS,
            'suppliers' => Supplier::orderBy('name')->get(['id', 'name', 'email']),
            'projects' => Project::orderBy('name')->get(['id', 'name', 'code']),
            'materialRequests' => MaterialRequest::orderByDesc('request_date')->orderBy('code')->get(['id', 'code']),
            'title' => 'Purchase Order',
            'user' => Auth::user(),
        ]);
    }

    public function create(): View
    {
        $activeProjects = Project::query()
            ->whereIn('status', ['planned', 'ongoing'])
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return view('procurement.po.create', [
            'purchaseOrder' => null,
            'suppliers' => Supplier::orderBy('name')->get(['id', 'name', 'email']),
            'projects' => $activeProjects,
            'materials' => Material::with('unit')->orderBy('name')->get(),
            'materialRequests' => MaterialRequest::where('status', 'approved')
                ->whereHas('items', function ($q) {
                    $q->whereRaw('
            material_request_items.qty >
            COALESCE((
                SELECT SUM(poi.qty)
                FROM purchase_order_items poi
                JOIN purchase_orders po ON po.id = poi.purchase_order_id
                WHERE po.material_request_id = material_request_items.material_request_id
                  AND poi.material_id = material_request_items.material_id
                  AND po.status IN ("draft", "approved", "partial", "received")
            ), 0)
        ');
                })
                ->with([
                    'items' => function ($query) {
                        $query->select('id', 'material_request_id', 'material_id', 'qty', 'remarks');
                    }
                ])
                ->orderByDesc('approved_at')
                ->orderBy('code')
                ->get(['id', 'code', 'project_id']),

            'statuses' => self::STATUS_OPTIONS,
            'title' => 'Buat Purchase Order',
            'user' => Auth::user(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatePurchaseOrder($request);

        $items = $validated['items'];

        $this->validateAgainstMaterialRequest(
            $items,
            $validated['material_request_id'] ?? null
        );

        unset($validated['items']);


        $code = PurchaseOrder::generateCode();

        $attributes = [
            'code' => $code,
            'supplier_id' => $validated['supplier_id'],
            'project_id' => $validated['project_id'] ?? null,
            'material_request_id' => $validated['material_request_id'] ?? null,
            'order_date' => $validated['order_date'],
            'status' => 'draft',
            'total' => $this->calculateItemsTotal($items),
        ];

        if ($request->material_request_id) {
            foreach ($request->items as $index => $item) {
                $mrItem = MaterialRequestItem::where('material_request_id', $request->material_request_id)
                    ->where('material_id', $item['material_id'])
                    ->firstOrFail();

                if ($request->material_request_id) {
                    foreach ($request->items as $index => $item) {

                        $mrItem = MaterialRequestItem::where('material_request_id', $request->material_request_id)
                            ->where('material_id', $item['material_id'])
                            ->firstOrFail();

                        $orderedQty = PurchaseOrderItem::whereHas('order', function ($q) use ($request) {
                            $q->where('material_request_id', $request->material_request_id)
                                ->whereIn('status', ['draft', 'approved', 'partial', 'received']);
                        })
                            ->where('material_id', $item['material_id'])
                            ->sum('qty');

                        $remainingQty = $mrItem->qty - $orderedQty;

                        if ($item['qty'] > $remainingQty) {
                            throw ValidationException::withMessages([
                                "items.$index.qty" =>
                                "Jumlah melebihi sisa permintaan material ({$remainingQty}).",
                            ]);
                        }
                    }
                }
            }
        }

        $purchaseOrder = DB::transaction(function () use ($attributes, $items) {
            $order = PurchaseOrder::create($attributes);
            $order->items()->createMany($items);

            return $order;
        });

        return redirect()
            ->route('procurement.purchase-orders.show', $purchaseOrder)
            ->with('success', 'Purchase order berhasil ditambahkan.');
    }

    public function show(PurchaseOrder $purchaseOrder): View
    {
        $purchaseOrder->load(['supplier', 'project', 'materialRequest', 'approver', 'items.material.unit']);

        return view('procurement.po.show', [
            'purchaseOrder' => $purchaseOrder,
            'statuses' => self::STATUS_OPTIONS,
            'title' => 'Detail Purchase Order',
            'user' => Auth::user(),
        ]);
    }

    public function edit(PurchaseOrder $purchaseOrder): View
    {
        $purchaseOrder->load(['items.material.unit']);

        return view('procurement.po.edit', [
            'purchaseOrder' => $purchaseOrder,
            'suppliers' => Supplier::orderBy('name')->get(['id', 'name', 'email']),
            'projects' => Project::orderBy('name')->get(['id', 'name', 'code']),
            'materials' => Material::with('unit')->orderBy('name')->get(),
            'materialRequests' => MaterialRequest::with([
                'items' => fn($query) => $query->select('id', 'material_request_id', 'material_id', 'qty', 'remarks'),
            ])
                ->orderByDesc('request_date')
                ->orderBy('code')
                ->get(['id', 'code', 'project_id']),
            'statuses' => self::STATUS_OPTIONS,
            'title' => 'Ubah Purchase Order',
            'user' => Auth::user(),
        ]);
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $validated = $this->validatePurchaseOrder($request, $purchaseOrder);

        $items = $validated['items'];

        $this->validateAgainstMaterialRequest(
            $items,
            $validated['material_request_id'] ?? null
        );

        unset($validated['items']);


        if ($purchaseOrder->status !== 'draft') {
            return back()->with('error', 'PO tidak dapat diubah.');
        }

        $attributes = [
            'code' => $validated['code'],
            'supplier_id' => $validated['supplier_id'],
            'project_id' => $validated['project_id'] ?? null,
            'material_request_id' => $validated['material_request_id'] ?? null,
            'order_date' => $validated['order_date'],
            'status' => 'draft',
            'total' => $this->calculateItemsTotal($items),
        ];

        DB::transaction(function () use ($purchaseOrder, $attributes, $items) {
            $purchaseOrder->update($attributes);
            $purchaseOrder->items()->delete();
            $purchaseOrder->items()->createMany($items);
        });

        return redirect()
            ->route('procurement.purchase-orders.show', $purchaseOrder)
            ->with('success', 'Purchase order berhasil diperbarui.');
    }

    public function destroy(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $purchaseOrder->delete();

        return redirect()
            ->route('procurement.purchase-orders.index')
            ->with('success', 'Purchase order berhasil dihapus.');
    }

    protected function validatePurchaseOrder(Request $request, ?PurchaseOrder $purchaseOrder = null): array
    {

        $validator = Validator::make($request->all(), [
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'material_request_id' => ['nullable', 'exists:material_requests,id'],
            'order_date' => ['required', 'date'],
            'items' => ['nullable', 'array'],
            'items.*.material_id' => ['nullable', 'exists:materials,id'],
            'items.*.qty' => ['nullable', 'numeric', 'min:0.01'],
            'items.*.price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $data = $validator->validate();

        $items = $this->sanitizeItems($data['items'] ?? []);

        if (empty($items)) {
            throw ValidationException::withMessages([
                'items' => 'Tambahkan minimal satu item material.',
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
                    'price' => isset($item['price']) ? (float) $item['price'] : null,
                ];
            })
            ->filter(fn($item) => !empty($item['material_id']) && $item['qty'] !== null)
            ->map(function ($item) {
                $qty = round((float) $item['qty'], 2);

                if ($qty <= 0) {
                    return null;
                }

                $price = round((float) ($item['price'] ?? 0), 2);
                if ($price < 0) {
                    $price = 0;
                }

                $subtotal = round($qty * $price, 2);

                return [
                    'material_id' => $item['material_id'],
                    'qty' => $qty,
                    'price' => $price,
                    'subtotal' => $subtotal,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    protected function calculateItemsTotal(array $items): float
    {
        $total = collect($items)->sum(fn($item) => $item['subtotal'] ?? 0);

        return round((float) $total, 2);
    }

    public function markOrdered(PurchaseOrder $purchaseOrder)
    {
        if ($purchaseOrder->status !== 'draft') {
            return back()->with('error', 'Status PO tidak valid.');
        }

        $purchaseOrder->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Purchase Order berhasil dikonfirmasi.');
    }

    protected function validateAgainstMaterialRequest(
        array $items,
        ?int $materialRequestId
    ): void {
        if (!$materialRequestId) {
            return;
        }

        $materialRequest = MaterialRequest::with('items')->find($materialRequestId);

        if (!$materialRequest) {
            return;
        }

        $mrItems = collect($materialRequest->items)
            ->keyBy('material_id');

        foreach ($items as $item) {
            $materialId = $item['material_id'];
            $qty = $item['qty'];

            if (!$mrItems->has($materialId)) {
                throw ValidationException::withMessages([
                    'items' => 'Material tidak terdapat dalam Material Request.',
                ]);
            }

            $mrQty = (float) $mrItems[$materialId]->qty;

            if ($qty > $mrQty) {
                throw ValidationException::withMessages([
                    'items' => "Qty material melebihi Material Request (maks: {$mrQty}).",
                ]);
            }
        }
    }

    public function updateStatusFromGoodsReceipt(PurchaseOrder $purchaseOrder): void
    {
        $totalPoQty = $purchaseOrder->items()->sum('qty');

        $totalReceivedQty = DB::table('goods_receipt_items')
            ->join('goods_receipts', 'goods_receipt_items.goods_receipt_id', '=', 'goods_receipts.id')
            ->where('goods_receipts.purchase_order_id', $purchaseOrder->id)
            ->where('goods_receipts.status', 'completed')
            ->sum('goods_receipt_items.qty');

        if ($totalReceivedQty >= $totalPoQty) {
            $purchaseOrder->update([
                'status' => 'received',
                'received_at' => now(),
            ]);
        } elseif ($totalReceivedQty > 0) {
            $purchaseOrder->update([
                'status' => 'partial',
                'received_at' => null,
            ]);
        } else {
            $purchaseOrder->update([
                'status' => 'approved',
                'received_at' => null,
            ]);
        }
    }
}
