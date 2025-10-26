<?php

namespace App\Http\Controllers;

use App\Models\GoodsIssue;
use App\Models\GoodsIssueItem;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\Material;
use App\Models\MaterialRequest;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function admin(): View
    {
        $projectStatusCounts = $this->statusCounts(Project::class);
        $materialRequestStatusCounts = $this->statusCounts(MaterialRequest::class);
        $purchaseOrderStatusCounts = $this->statusCounts(PurchaseOrder::class);
        $goodsReceiptStatusCounts = $this->statusCounts(GoodsReceipt::class);
        $goodsIssueStatusCounts = $this->statusCounts(GoodsIssue::class);

        $totals = [
            'projects' => array_sum($projectStatusCounts),
            'active_projects' => ($projectStatusCounts['planned'] ?? 0) + ($projectStatusCounts['ongoing'] ?? 0),
            'completed_projects' => $projectStatusCounts['done'] ?? 0,
            'suppliers' => Supplier::count(),
            'materials' => Material::count(),
            'pending_requests' => $materialRequestStatusCounts['submitted'] ?? 0,
            'open_purchase_orders' => ($purchaseOrderStatusCounts['draft'] ?? 0)
                + ($purchaseOrderStatusCounts['approved'] ?? 0)
                + ($purchaseOrderStatusCounts['partial'] ?? 0),
            'purchase_order_total' => PurchaseOrder::sum('total'),
        ];

        $recentMaterialRequests = MaterialRequest::query()
            ->with(['project:id,name', 'requester:id,name'])
            ->select(['id', 'code', 'project_id', 'requested_by', 'status', 'request_date', 'created_at'])
            ->orderByDesc(DB::raw('COALESCE(request_date, created_at)'))
            ->limit(6)
            ->get();

        $recentPurchaseOrders = PurchaseOrder::query()
            ->with(['project:id,name', 'supplier:id,name'])
            ->select(['id', 'code', 'project_id', 'supplier_id', 'status', 'order_date', 'total', 'created_at'])
            ->orderByDesc(DB::raw('COALESCE(order_date, created_at)'))
            ->limit(6)
            ->get();

        $recentGoodsReceipts = GoodsReceipt::query()
            ->with(['project:id,name', 'supplier:id,name'])
            ->select(['id', 'code', 'project_id', 'supplier_id', 'status', 'received_date', 'created_at'])
            ->orderByDesc(DB::raw('COALESCE(received_date, created_at)'))
            ->limit(6)
            ->get();

        $stockSnapshot = $this->buildStockSnapshot();

        return $this->render('Admin', [
            'summaryCards' => $this->buildSummaryCards($totals),
            'projectStatusCounts' => $projectStatusCounts,
            'materialRequestStatusCounts' => $materialRequestStatusCounts,
            'purchaseOrderStatusCounts' => $purchaseOrderStatusCounts,
            'goodsReceiptStatusCounts' => $goodsReceiptStatusCounts,
            'goodsIssueStatusCounts' => $goodsIssueStatusCounts,
            'recentMaterialRequests' => $recentMaterialRequests,
            'recentPurchaseOrders' => $recentPurchaseOrders,
            'recentGoodsReceipts' => $recentGoodsReceipts,
            'lowStockMaterials' => $stockSnapshot['alerts'],
            'stockSummary' => $stockSnapshot['summary'],
            'totals' => $totals,
        ]);
    }

    public function manager(): View
    {
        return $this->render('Manager');
    }

    public function operator(): View
    {
        return $this->render('Operator');
    }

    protected function render(string $title, array $data = []): View
    {
        return view('dashboard.index', array_merge([
            'title' => $title,
            'user' => Auth::user(),
        ], $data));
    }

    /**
     * @param class-string<Model> $model
     */
    private function statusCounts(string $model, string $column = 'status'): array
    {
        return $model::query()
            ->select($column, DB::raw('COUNT(*) as total'))
            ->groupBy($column)
            ->pluck('total', $column)
            ->map(fn ($count) => (int) $count)
            ->toArray();
    }

    private function buildSummaryCards(array $totals): array
    {
        return [
            [
                'icon' => 'kanban',
                'variant' => 'primary',
                'label' => 'Proyek Aktif',
                'value' => $totals['active_projects'] ?? 0,
                'hint' => ($totals['projects'] ?? 0) . ' total proyek',
            ],
            [
                'icon' => 'clipboard-data',
                'variant' => 'info',
                'label' => 'Permintaan Pending',
                'value' => $totals['pending_requests'] ?? 0,
                'hint' => 'Menunggu persetujuan admin',
            ],
            [
                'icon' => 'file-earmark-text',
                'variant' => 'warning',
                'label' => 'PO Terbuka',
                'value' => $totals['open_purchase_orders'] ?? 0,
                'hint' => 'Total nilai ~ ' . $this->formatCurrency($totals['purchase_order_total'] ?? 0),
            ],
            [
                'icon' => 'people',
                'variant' => 'success',
                'label' => 'Supplier Aktif',
                'value' => $totals['suppliers'] ?? 0,
                'hint' => ($totals['materials'] ?? 0) . ' material terdaftar',
            ],
        ];
    }

    private function buildStockSnapshot(): array
    {
        $receiptTotals = GoodsReceiptItem::query()
            ->select('goods_receipt_items.material_id')
            ->selectRaw('SUM(goods_receipt_items.qty - goods_receipt_items.returned_qty) as total_received')
            ->join('goods_receipts', 'goods_receipts.id', '=', 'goods_receipt_items.goods_receipt_id')
            ->whereIn('goods_receipts.status', ['in_progress', 'completed'])
            ->groupBy('goods_receipt_items.material_id');

        $issueTotals = GoodsIssueItem::query()
            ->select('goods_issue_items.material_id')
            ->selectRaw('SUM(goods_issue_items.qty) as total_issued')
            ->join('goods_issues', 'goods_issues.id', '=', 'goods_issue_items.goods_issue_id')
            ->where('goods_issues.status', '=', 'issued')
            ->groupBy('goods_issue_items.material_id');

        $materials = Material::query()
            ->with('unit:id,name,symbol')
            ->select('materials.id', 'materials.name', 'materials.sku', 'materials.min_stock')
            ->selectRaw('COALESCE(receipt_totals.total_received, 0) as total_received')
            ->selectRaw('COALESCE(issue_totals.total_issued, 0) as total_issued')
            ->selectRaw('COALESCE(receipt_totals.total_received, 0) - COALESCE(issue_totals.total_issued, 0) as current_stock')
            ->leftJoinSub($receiptTotals, 'receipt_totals', 'receipt_totals.material_id', '=', 'materials.id')
            ->leftJoinSub($issueTotals, 'issue_totals', 'issue_totals.material_id', '=', 'materials.id')
            ->orderBy('materials.name')
            ->get()
            ->map(function (Material $material) {
                $current = (float) ($material->current_stock ?? 0);
                $min = (float) ($material->min_stock ?? 0);

                if ($current <= 0) {
                    $material->stock_status = 'critical';
                    $material->stock_status_label = 'Kosong';
                } elseif ($min > 0 && $current < $min) {
                    $material->stock_status = 'warning';
                    $material->stock_status_label = 'Butuh restock';
                } else {
                    $material->stock_status = 'safe';
                    $material->stock_status_label = 'Aman';
                }

                $material->stock_gap = $min - $current;
                $material->unit_label = optional($material->unit)->symbol ?? optional($material->unit)->name;

                return $material;
            });

        $summary = [
            'total_materials' => $materials->count(),
            'total_stock' => $materials->sum(fn (Material $material) => max(0, (float) $material->current_stock)),
            'safe' => $materials->where('stock_status', 'safe')->count(),
            'warning' => $materials->where('stock_status', 'warning')->count(),
            'critical' => $materials->where('stock_status', 'critical')->count(),
        ];

        $alerts = $materials
            ->filter(fn (Material $material) => in_array($material->stock_status, ['warning', 'critical'], true))
            ->sortBy(fn (Material $material) => $material->stock_gap)
            ->take(5)
            ->values();

        return [
            'summary' => $summary,
            'alerts' => $alerts,
        ];
    }

    private function formatCurrency(float|int|string $value): string
    {
        $amount = (float) $value;

        if ($amount === 0.0) {
            return 'Rp0';
        }

        return 'Rp' . number_format($amount, 0, ',', '.');
    }
}
