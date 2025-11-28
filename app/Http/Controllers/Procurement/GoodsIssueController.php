<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use App\Models\GoodsIssue;
use App\Models\Material;
use App\Models\Project;
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

class GoodsIssueController extends Controller
{
    private const STATUS_OPTIONS = [
        'draft' => 'Draft',
        'issued' => 'Dikeluarkan',
        'returned' => 'Dikembalikan',
    ];

    public function __construct(private StockMovementService $stockMovements)
    {
    }

    public function index(Request $request): View
    {
        $issuesQuery = GoodsIssue::query()
            ->with(['project', 'issuer'])
            ->withCount('items');

        if ($search = trim((string) $request->input('search'))) {
            $issuesQuery->where(function ($query) use ($search) {
                $query->where('code', 'like', "%{$search}%")
                    ->orWhereHas('project', function ($projectQuery) use ($search) {
                        $projectQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    })
                    ->orWhereHas('issuer', function ($issuerQuery) use ($search) {
                        $issuerQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if ($status = (string) $request->input('status')) {
            $issuesQuery->where('status', $status);
        }

        if ($projectId = $request->input('project_id')) {
            $issuesQuery->where('project_id', $projectId);
        }

        if ($issuedBy = $request->input('issued_by')) {
            $issuesQuery->where('issued_by', $issuedBy);
        }

        if ($issuedDate = $request->input('issued_date')) {
            $issuesQuery->whereDate('issued_date', $issuedDate);
        }

        $perPage = $request->integer('per_page', 10) ?: 10;
        $perPage = max(min($perPage, 100), 1);

        $issues = $issuesQuery
            ->orderByDesc('issued_date')
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();

        return view('procurement.gi.index', [
            'issues' => $issues,
            'projects' => Project::orderBy('name')->get(['id', 'name', 'code']),
            'issuers' => User::orderBy('name')->get(['id', 'name', 'email']),
            'statuses' => self::STATUS_OPTIONS,
            'title' => 'Goods Issue',
            'user' => Auth::user(),
        ]);
    }

    public function create(): View
    {
        return view('procurement.gi.create', [
            'projects' => Project::orderBy('name')->get(['id', 'name', 'code']),
            'materials' => Material::with('unit')->orderBy('name')->get(),
            'statuses' => self::STATUS_OPTIONS,
            'title' => 'Buat Goods Issue',
            'user' => Auth::user(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateGoodsIssue($request);

        $items = $validated['items'];
        unset($validated['items']);

        $attributes = [
            'code' => $validated['code'],
            'project_id' => $validated['project_id'],
            'issued_date' => $validated['issued_date'],
            'status' => $validated['status'],
            'issued_by' => Auth::id(),
            'remarks' => $validated['remarks'] ?? null,
        ];

        $actorId = Auth::id();

        $goodsIssue = DB::transaction(function () use ($attributes, $items, $actorId) {
            $issue = GoodsIssue::create($attributes);
            $createdItems = $issue->items()->createMany($items);
            $issue->setRelation('items', collect($createdItems));

            $this->stockMovements->syncGoodsIssue($issue, $actorId);

            return $issue;
        });

        return redirect()
            ->route('procurement.goods-issues.show', $goodsIssue)
            ->with('success', 'Goods issue berhasil ditambahkan.');
    }

    public function show(GoodsIssue $goodsIssue): View
    {
        $goodsIssue->load(['project', 'issuer', 'items.material.unit']);

        return view('procurement.gi.show', [
            'goodsIssue' => $goodsIssue,
            'statuses' => self::STATUS_OPTIONS,
            'title' => 'Detail Goods Issue',
            'user' => Auth::user(),
        ]);
    }

    public function edit(GoodsIssue $goodsIssue): View
    {
        $goodsIssue->load(['items.material.unit']);

        return view('procurement.gi.edit', [
            'goodsIssue' => $goodsIssue,
            'projects' => Project::orderBy('name')->get(['id', 'name', 'code']),
            'materials' => Material::with('unit')->orderBy('name')->get(),
            'statuses' => self::STATUS_OPTIONS,
            'title' => 'Ubah Goods Issue',
            'user' => Auth::user(),
        ]);
    }

    public function update(Request $request, GoodsIssue $goodsIssue): RedirectResponse
    {
        $validated = $this->validateGoodsIssue($request, $goodsIssue);

        $items = $validated['items'];
        unset($validated['items']);

        $attributes = [
            'code' => $validated['code'],
            'project_id' => $validated['project_id'],
            'issued_date' => $validated['issued_date'],
            'status' => $validated['status'],
            'remarks' => $validated['remarks'] ?? null,
        ];

        $actorId = Auth::id();

        DB::transaction(function () use ($goodsIssue, $attributes, $items, $actorId) {
            $this->stockMovements->purgeGoodsIssue($goodsIssue);

            $goodsIssue->update($attributes);
            $goodsIssue->items()->delete();
            $createdItems = $goodsIssue->items()->createMany($items);
            $goodsIssue->setRelation('items', collect($createdItems));

            $this->stockMovements->syncGoodsIssue($goodsIssue, $actorId);
        });

        return redirect()
            ->route('procurement.goods-issues.show', $goodsIssue)
            ->with('success', 'Goods issue berhasil diperbarui.');
    }

    public function destroy(GoodsIssue $goodsIssue): RedirectResponse
    {
        DB::transaction(function () use ($goodsIssue) {
            $this->stockMovements->purgeGoodsIssue($goodsIssue);
            $goodsIssue->items()->delete();
            $goodsIssue->delete();
        });

        return redirect()
            ->route('procurement.goods-issues.index')
            ->with('success', 'Goods issue berhasil dihapus.');
    }

    protected function validateGoodsIssue(Request $request, ?GoodsIssue $goodsIssue = null): array
    {
        $statuses = array_keys(self::STATUS_OPTIONS);

        $validator = Validator::make($request->all(), [
            'code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('goods_issues', 'code')->ignore($goodsIssue?->id),
            ],
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'issued_date' => ['required', 'date'],
            'status' => ['required', 'string', Rule::in($statuses)],
            'remarks' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.material_id' => ['required', 'integer', 'exists:materials,id'],
            'items.*.qty' => ['required', 'numeric', 'gt:0'],
            'items.*.remarks' => ['nullable', 'string', 'max:255'],
        ], [
            'items.required' => 'Tambahkan minimal satu material.',
            'items.min' => 'Tambahkan minimal satu material.',
        ]);

        $itemsInput = $request->input('items', []);
        if (is_array($itemsInput)) {
            $nonEmptyItems = array_filter($itemsInput, function ($item) {
                $materialId = $item['material_id'] ?? null;
                $qty = $item['qty'] ?? null;

                return $materialId !== null && $materialId !== '' && $qty !== null && $qty !== '';
            });

            if (empty($nonEmptyItems)) {
                $validator->after(function ($validator) {
                    $validator->errors()->add('items', 'Tambahkan minimal satu material.');
                });
            }
        }

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $validated = $validator->validated();

        $validated['items'] = collect($validated['items'])
            ->map(function ($item) {
                return [
                    'material_id' => $item['material_id'],
                    'qty' => $item['qty'],
                    'remarks' => $item['remarks'] ?? null,
                ];
            })
            ->values()
            ->all();

        return $validated;
    }
}
