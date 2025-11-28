<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\MaterialRequest;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PurchaseOrderController extends Controller
{
    private const STATUS_OPTIONS = [
        'draft' => 'Draft',
        'approved' => 'Disetujui',
        'partial' => 'Sebagian Diterima',
        'received' => 'Sudah Diterima',
        'canceled' => 'Dibatalkan',
    ];

    private const APPROVAL_STATUSES = ['approved', 'partial', 'received'];

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
        return view('procurement.po.create', [
            'purchaseOrder' => null,
            'suppliers' => Supplier::orderBy('name')->get(['id', 'name', 'email']),
            'projects' => Project::orderBy('name')->get(['id', 'name', 'code']),
            'materials' => Material::with('unit')->orderBy('name')->get(),
            'materialRequests' => MaterialRequest::with([
                'items' => fn ($query) => $query->select('id', 'material_request_id', 'material_id', 'qty', 'remarks'),
            ])
                ->orderByDesc('request_date')
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
        unset($validated['items']);

        $status = $validated['status'];

        $attributes = [
            'code' => $validated['code'],
            'supplier_id' => $validated['supplier_id'],
            'project_id' => $validated['project_id'] ?? null,
            'material_request_id' => $validated['material_request_id'] ?? null,
            'order_date' => $validated['order_date'],
            'status' => $status,
            'total' => $this->calculateItemsTotal($items),
        ];

        if ($this->requiresApprovalMetadata($status)) {
            $attributes['approved_by'] = Auth::id();
            $attributes['approved_at'] = now();
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
                'items' => fn ($query) => $query->select('id', 'material_request_id', 'material_id', 'qty', 'remarks'),
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
        unset($validated['items']);

        $status = $validated['status'];

        $attributes = [
            'code' => $validated['code'],
            'supplier_id' => $validated['supplier_id'],
            'project_id' => $validated['project_id'] ?? null,
            'material_request_id' => $validated['material_request_id'] ?? null,
            'order_date' => $validated['order_date'],
            'status' => $status,
            'total' => $this->calculateItemsTotal($items),
        ];

        if ($this->requiresApprovalMetadata($status)) {
            $attributes['approved_by'] = $purchaseOrder->approved_by ?? Auth::id();
            $attributes['approved_at'] = $purchaseOrder->approved_at ?? now();
        } else {
            $attributes['approved_by'] = null;
            $attributes['approved_at'] = null;
        }

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
        $statuses = array_keys(self::STATUS_OPTIONS);

        $validator = Validator::make($request->all(), [
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('purchase_orders', 'code')->ignore($purchaseOrder?->getKey()),
            ],
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'material_request_id' => ['nullable', 'exists:material_requests,id'],
            'order_date' => ['required', 'date'],
            'status' => ['required', Rule::in($statuses)],
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
            ->filter(fn ($item) => !empty($item['material_id']) && $item['qty'] !== null)
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
        $total = collect($items)->sum(fn ($item) => $item['subtotal'] ?? 0);

        return round((float) $total, 2);
    }

    protected function requiresApprovalMetadata(string $status): bool
    {
        return in_array($status, self::APPROVAL_STATUSES, true);
    }
}
