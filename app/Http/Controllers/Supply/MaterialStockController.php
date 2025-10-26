<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Models\GoodsIssueItem;
use App\Models\GoodsReceiptItem;
use App\Models\Material;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MaterialStockController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = $this->resolvePerPage($request->integer('per_page'));

        $receiptTotals = GoodsReceiptItem::query()
            ->select(
                'goods_receipt_items.material_id',
                DB::raw('SUM(goods_receipt_items.qty - goods_receipt_items.returned_qty) as total_received'),
            )
            ->join('goods_receipts', 'goods_receipt_items.goods_receipt_id', '=', 'goods_receipts.id')
            ->whereIn('goods_receipts.status', ['in_progress', 'completed'])
            ->groupBy('goods_receipt_items.material_id');

        $issueTotals = GoodsIssueItem::query()
            ->select(
                'goods_issue_items.material_id',
                DB::raw('SUM(goods_issue_items.qty) as total_issued'),
            )
            ->join('goods_issues', 'goods_issue_items.goods_issue_id', '=', 'goods_issues.id')
            ->where('goods_issues.status', '=', 'issued')
            ->groupBy('goods_issue_items.material_id');

        $currentStockExpression = 'COALESCE(receipt_totals.total_received, 0) - COALESCE(issue_totals.total_issued, 0)';
        $minStockExpression = 'COALESCE(materials.min_stock, 0)';

        $materialsQuery = Material::query()
            ->with('unit')
            ->select('materials.*')
            ->selectRaw('COALESCE(receipt_totals.total_received, 0) as total_received')
            ->selectRaw('COALESCE(issue_totals.total_issued, 0) as total_issued')
            ->selectRaw("{$currentStockExpression} as current_stock")
            ->leftJoinSub($receiptTotals, 'receipt_totals', 'receipt_totals.material_id', '=', 'materials.id')
            ->leftJoinSub($issueTotals, 'issue_totals', 'issue_totals.material_id', '=', 'materials.id');

        if ($search = trim((string) $request->input('search'))) {
            $materialsQuery->where(function ($query) use ($search) {
                $query->where('materials.name', 'like', "%{$search}%")
                    ->orWhere('materials.sku', 'like', "%{$search}%");
            });
        }

        if ($unitId = $request->input('unit_id')) {
            $materialsQuery->where('materials.unit_id', $unitId);
        }

        $stockStatus = (string) $request->input('stock_status');
        if ($stockStatus === 'critical') {
            $materialsQuery->whereRaw("{$currentStockExpression} <= 0");
        } elseif ($stockStatus === 'warning') {
            $materialsQuery
                ->whereRaw("{$currentStockExpression} > 0")
                ->whereRaw('COALESCE(materials.min_stock, 0) > 0')
                ->whereRaw("{$currentStockExpression} < {$minStockExpression}");
        } elseif ($stockStatus === 'safe') {
            $materialsQuery->whereRaw("{$currentStockExpression} >= {$minStockExpression}");
        }

        $materialsPaginator = (clone $materialsQuery)
            ->orderBy('materials.name')
            ->paginate($perPage)
            ->withQueryString();

        $materialsPaginator->getCollection()->transform(function (Material $material) {
            return $this->attachStockMeta($material);
        });

        $materialsCollection = (clone $materialsQuery)
            ->orderBy('materials.name')
            ->get()
            ->map(fn (Material $material) => $this->attachStockMeta($material));

        return view('supply.material-stock.index', [
            'title' => 'Stok Material',
            'user' => Auth::user(),
            'materials' => $materialsPaginator,
            'units' => Unit::orderBy('name')->get(['id', 'name', 'symbol']),
            'filters' => [
                'search' => $request->input('search'),
                'unit_id' => $request->input('unit_id'),
                'stock_status' => $stockStatus,
                'per_page' => $perPage,
            ],
            'summary' => [
                'total_materials' => $materialsCollection->count(),
                'total_stock' => $materialsCollection->sum(fn (Material $material) => (float) $material->current_stock),
                'safe' => $materialsCollection->where('stock_status', 'safe')->count(),
                'warning' => $materialsCollection->where('stock_status', 'warning')->count(),
                'critical' => $materialsCollection->where('stock_status', 'critical')->count(),
            ],
        ]);
    }

    private function resolvePerPage(?int $perPage): int
    {
        $perPage = $perPage ?? 10;

        return max(5, min($perPage, 100));
    }

    private function attachStockMeta(Material $material): Material
    {
        $currentStock = (float) ($material->current_stock ?? 0);
        $minStock = (float) ($material->min_stock ?? 0);

        if ($currentStock <= 0) {
            $material->stock_status = 'critical';
            $material->stock_status_label = 'Kosong';
            $material->stock_status_variant = 'danger';
        } elseif ($minStock > 0 && $currentStock < $minStock) {
            $material->stock_status = 'warning';
            $material->stock_status_label = 'Perlu Restock';
            $material->stock_status_variant = 'warning';
        } else {
            $material->stock_status = 'safe';
            $material->stock_status_label = 'Aman';
            $material->stock_status_variant = 'success';
        }

        return $material;
    }
}
