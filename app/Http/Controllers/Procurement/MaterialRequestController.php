<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Models\MaterialRequest;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MaterialRequestController extends Controller
{
    private const STATUS_OPTIONS = [
        'draft' => 'Draft',
        'submitted' => 'Diajukan',
        'approved' => 'Disetujui',
        'rejected' => 'Ditolak',
    ];

    public function index(Request $request)
    {
        $requestsQuery = MaterialRequest::query()
            ->with(['project', 'requester', 'approver'])
            ->withCount('items');

        if ($search = trim((string) $request->input('search'))) {
            $requestsQuery->where(function ($query) use ($search) {
                $query->where('code', 'like', "%{$search}%")
                    ->orWhereHas('project', function ($projectQuery) use ($search) {
                        $projectQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    });
            });
        }

        if ($status = (string) $request->input('status')) {
            $requestsQuery->where('status', $status);
        }

        if ($projectId = $request->input('project_id')) {
            $requestsQuery->where('project_id', $projectId);
        }

        if ($requestDate = $request->input('request_date')) {
            $requestsQuery->whereDate('request_date', $requestDate);
        }

        $perPage = $request->integer('per_page', 10) ?: 10;
        $perPage = max(min($perPage, 100), 1);

        $requests = $requestsQuery
            ->orderByDesc('request_date')
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();

        return view('procurement.mr.index', [
            'requests' => $requests,
            'projects' => Project::orderBy('name')->get(['id', 'name', 'code']),
            'statuses' => self::STATUS_OPTIONS,
            'title' => 'Permintaan Material',
            'user' => Auth::user(),
        ]);
    }

    public function create()
    {
        return view('procurement.mr.create', [
            'projects' => Project::orderBy('name')->get(['id', 'name', 'code']),
            'materials' => Material::with('unit')->orderBy('name')->get(),
            'statuses' => self::STATUS_OPTIONS,
            'title' => 'Buat Permintaan Material',
            'user' => Auth::user(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateMaterialRequest($request);

        $items = $validated['items'];
        $totalAmount = $validated['total_amount'];
        unset($validated['items'], $validated['total_amount']);

        $status = $validated['status'];

        $attributes = [
            'project_id' => $validated['project_id'],
            'request_date' => $validated['request_date'],
            'status' => $status,
            'notes' => $validated['notes'] ?? null,
            'requested_by' => Auth::id(),
            'total_amount' => $totalAmount,
        ];

        if ($status === 'approved') {
            $attributes['approved_by'] = Auth::id();
            $attributes['approved_at'] = now();
        }

        $materialRequest = DB::transaction(function () use ($attributes, $items) {
            $requestModel = MaterialRequest::create($attributes);
            $requestModel->items()->createMany($items);

            return $requestModel;
        });

        return redirect()
            ->route('procurement.material-requests.show', $materialRequest)
            ->with('success', 'Permintaan material berhasil ditambahkan.');
    }

    public function show(MaterialRequest $materialRequest)
    {
        $materialRequest->load(['project', 'requester', 'approver', 'items.material.unit']);

        return view('procurement.mr.show', [
            'materialRequest' => $materialRequest,
            'statuses' => self::STATUS_OPTIONS,
            'title' => 'Detail Permintaan Material',
            'user' => Auth::user(),
        ]);
    }

    public function edit(MaterialRequest $materialRequest)
    {
        $materialRequest->load(['items.material.unit']);

        return view('procurement.mr.edit', [
            'materialRequest' => $materialRequest,
            'projects' => Project::orderBy('name')->get(['id', 'name', 'code']),
            'materials' => Material::with('unit')->orderBy('name')->get(),
            'statuses' => self::STATUS_OPTIONS,
            'title' => 'Ubah Permintaan Material',
            'user' => Auth::user(),
        ]);
    }

    public function update(Request $request, MaterialRequest $materialRequest)
    {
        $validated = $this->validateMaterialRequest($request, $materialRequest);

        $items = $validated['items'];
        $totalAmount = $validated['total_amount'];
        unset($validated['items'], $validated['total_amount']);

        $status = $validated['status'];

        $attributes = [
            'project_id' => $validated['project_id'],
            'request_date' => $validated['request_date'],
            'status' => $status,
            'notes' => $validated['notes'] ?? null,
            'total_amount' => $totalAmount,
        ];

        if ($status === 'approved') {
            if ($materialRequest->status !== 'approved') {
                $attributes['approved_by'] = Auth::id();
                $attributes['approved_at'] = now();
            } else {
                $attributes['approved_by'] = $materialRequest->approved_by ?? Auth::id();
                $attributes['approved_at'] = $materialRequest->approved_at ?? now();
            }
        } else {
            $attributes['approved_by'] = null;
            $attributes['approved_at'] = null;
        }

        DB::transaction(function () use ($materialRequest, $attributes, $items) {
            $materialRequest->update($attributes);
            $materialRequest->items()->delete();
            $materialRequest->items()->createMany($items);
        });

        return redirect()
            ->route('procurement.material-requests.show', $materialRequest)
            ->with('success', 'Permintaan material berhasil diperbarui.');
    }

    public function destroy(MaterialRequest $materialRequest)
    {
        DB::transaction(function () use ($materialRequest) {
            $materialRequest->purchaseOrders()->update(['material_request_id' => null]);
            $materialRequest->delete();
        });

        return redirect()
            ->route('procurement.material-requests.index')
            ->with('success', 'Permintaan material berhasil dihapus.');
    }

    protected function validateMaterialRequest(Request $request, ?MaterialRequest $materialRequest = null): array
    {
        $statuses = array_keys(self::STATUS_OPTIONS);

        $validator = Validator::make($request->all(), [
            'project_id' => ['required', 'exists:projects,id'],
            'request_date' => ['required', 'date'],
            'status' => ['required', Rule::in($statuses)],
            'notes' => ['nullable', 'string'],
            'items' => ['nullable', 'array'],
            'items.*.material_id' => ['nullable', 'exists:materials,id'],
            'items.*.qty' => ['nullable', 'numeric', 'min:0.01'],
            // 'items.*.price' => ['nullable', 'numeric', 'min:0'],
            'items.*.remarks' => ['nullable', 'string', 'max:255'],
        ]);

        $data = $validator->validate();

        $items = $this->sanitizeItems($data['items'] ?? []);

        if (empty($items)) {
            throw ValidationException::withMessages([
                'items' => 'Tambahkan minimal satu material.',
            ]);
        }

        [$pricedItems, $totalAmount] = $this->applyPricingToItems($items);

        $data['items'] = $pricedItems;
        $data['total_amount'] = $totalAmount;

        return $data;
    }

    protected function sanitizeItems(array $items): array
    {
        return collect($items)
            ->map(function ($item) {
                return [
                    'material_id' => $item['material_id'] ?? null,
                    'qty' => isset($item['qty']) ? (float) $item['qty'] : null,
                    // 'price' => isset($item['price']) ? (float) $item['price'] : null,
                    'remarks' => $item['remarks'] ?? null,
                ];
            })
            ->filter(fn($item) => !empty($item['material_id']) && !empty($item['qty']))
            ->map(fn($item) => [
                'material_id' => $item['material_id'],
                'qty' => round((float) $item['qty'], 2),
                // 'price' => isset($item['price']) ? round((float) $item['price'], 2) : null,
                'remarks' => $item['remarks'] ?? null,
            ])
            ->values()
            ->all();
    }

    protected function applyPricingToItems(array $items): array
    {
        if (empty($items)) {
            return [[], '0.00'];
        }

        $materialIds = collect($items)->pluck('material_id')->unique()->all();
        $materials = Material::query()
            ->whereIn('id', $materialIds)
            ->get(['id', 'unit_price'])
            ->keyBy('id');

        $totalAmount = 0;

        $pricedItems = collect($items)
            ->map(function ($item) use ($materials, &$totalAmount) {
                $material = $materials->get($item['material_id']);
                $unitPrice = $material?->unit_price ?? 0;
                $totalPrice = round($unitPrice * $item['qty'], 2);
                $totalAmount += $totalPrice;

                return array_merge($item, [
                    'unit_price' => number_format((float) $unitPrice, 2, '.', ''),
                    'total_price' => number_format($totalPrice, 2, '.', ''),
                ]);
            })
            ->all();

        return [$pricedItems, number_format($totalAmount, 2, '.', '')];
    }
}
