<?php

namespace App\Services\Inventory;

use App\Models\GoodsIssue;
use App\Models\GoodsIssueItem;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class StockMovementService
{
    private const RECEIPT_STOCK_STATUSES = ['completed'];

    private const ISSUE_STOCK_STATUSES = ['issued'];

    public function syncGoodsReceipt(GoodsReceipt $receipt, ?int $actorId = null): void
    {
        $receipt->load('purchaseOrder');

        $items = $receipt->items;

        if ($items->isEmpty() || ! $this->shouldAffectStockForReceipt($receipt)) {
            return;
        }

        $occurredAt = $this->resolveReceiptOccurredAt($receipt);

        foreach ($items as $item) {
            $netQuantity = round((float) $item->qty - (float) ($item->returned_qty ?? 0), 2);

            if ($netQuantity <= 0) {
                continue;
            }

            $stockAfter = $this->calculateStockAfterForReceiptItem($item, $receipt);
            $stockBefore = $stockAfter - $netQuantity;

            $this->storeMovement(
                materialId: (int) $item->material_id,
                projectId: $receipt->project_id ?? optional($receipt->purchaseOrder)->project_id,
                userId: $actorId ?? (int) $receipt->received_by,
                movementType: 'in',
                quantity: $netQuantity,
                stockBefore: $stockBefore,
                stockAfter: $stockAfter,
                occurredAt: $occurredAt,
                remarks: $item->remarks ?: $receipt->remarks,
                reference: $item,
            );
        }
    }

    public function purgeGoodsReceipt(GoodsReceipt $receipt): void
    {
        $itemIds = $receipt->items()->pluck('id');

        if ($itemIds->isEmpty()) {
            return;
        }

        StockMovement::query()
            ->where('reference_type', GoodsReceiptItem::class)
            ->whereIn('reference_id', $itemIds)
            ->delete();
    }

    public function syncGoodsIssue(GoodsIssue $issue, ?int $actorId = null): void
    {
        $items = $issue->items;

        if ($items->isEmpty() || ! $this->shouldAffectStockForIssue($issue)) {
            return;
        }

        $occurredAt = $this->resolveIssueOccurredAt($issue);

        foreach ($items as $item) {
            $quantity = round((float) $item->qty, 2);

            if ($quantity <= 0) {
                continue;
            }

            $stockAfter = $this->calculateStockAfterForIssueItem($item, $issue);
            $stockBefore = $stockAfter + $quantity;

            $this->storeMovement(
                materialId: (int) $item->material_id,
                projectId: $issue->project_id,
                userId: $actorId ?? (int) $issue->issued_by,
                movementType: 'out',
                quantity: $quantity,
                stockBefore: $stockBefore,
                stockAfter: $stockAfter,
                occurredAt: $occurredAt,
                remarks: $item->remarks ?: $issue->remarks,
                reference: $item,
            );
        }
    }

    public function purgeGoodsIssue(GoodsIssue $issue): void
    {
        $itemIds = $issue->items()->pluck('id');

        if ($itemIds->isEmpty()) {
            return;
        }

        StockMovement::query()
            ->where('reference_type', GoodsIssueItem::class)
            ->whereIn('reference_id', $itemIds)
            ->delete();
    }

    private function shouldAffectStockForReceipt(GoodsReceipt $receipt): bool
    {
        return in_array($receipt->status, self::RECEIPT_STOCK_STATUSES, true);
    }

    private function shouldAffectStockForIssue(GoodsIssue $issue): bool
    {
        return in_array($issue->status, self::ISSUE_STOCK_STATUSES, true);
    }

    private function resolveReceiptOccurredAt(GoodsReceipt $receipt): Carbon
    {
        $occurredAt = $receipt->received_date instanceof Carbon
            ? $receipt->received_date->copy()
            : Carbon::parse((string) $receipt->received_date);

        if ($receipt->created_at instanceof Carbon) {
            $occurredAt->setTimeFromTimeString($receipt->created_at->format('H:i:s'));
        }

        return $occurredAt;
    }

    private function resolveIssueOccurredAt(GoodsIssue $issue): Carbon
    {
        $occurredAt = $issue->issued_date instanceof Carbon
            ? $issue->issued_date->copy()
            : Carbon::parse((string) $issue->issued_date);

        if ($issue->created_at instanceof Carbon) {
            $occurredAt->setTimeFromTimeString($issue->created_at->format('H:i:s'));
        }

        return $occurredAt;
    }

    private function calculateStockAfterForReceiptItem(GoodsReceiptItem $item, GoodsReceipt $receipt): float
    {
        $date = $receipt->received_date instanceof Carbon
            ? $receipt->received_date->toDateString()
            : (string) $receipt->received_date;

        $totalReceived = GoodsReceiptItem::query()
            ->join('goods_receipts', 'goods_receipts.id', '=', 'goods_receipt_items.goods_receipt_id')
            ->where('goods_receipt_items.material_id', $item->material_id)
            ->whereIn('goods_receipts.status', self::RECEIPT_STOCK_STATUSES)
            ->where(function ($query) use ($item, $receipt, $date) {
                $query->where('goods_receipts.received_date', '<', $date)
                    ->orWhere(function ($inner) use ($item, $receipt, $date) {
                        $inner->where('goods_receipts.received_date', '=', $date)
                            ->where(function ($deep) use ($item, $receipt) {
                                if ($receipt->created_at instanceof Carbon) {
                                    $deep->where('goods_receipts.created_at', '<', $receipt->created_at)
                                        ->orWhere(function ($nested) use ($item, $receipt) {
                                            $nested->where('goods_receipts.created_at', '=', $receipt->created_at)
                                                ->where('goods_receipt_items.id', '<=', $item->id);
                                        });
                                } else {
                                    $deep->where('goods_receipt_items.id', '<=', $item->id);
                                }
                            });
                    });
            })
            ->sum(DB::raw('(goods_receipt_items.qty - goods_receipt_items.returned_qty)'));

        $totalIssued = GoodsIssueItem::query()
            ->join('goods_issues', 'goods_issues.id', '=', 'goods_issue_items.goods_issue_id')
            ->where('goods_issue_items.material_id', $item->material_id)
            ->whereIn('goods_issues.status', self::ISSUE_STOCK_STATUSES)
            ->where(function ($query) use ($receipt, $date) {
                $query->where('goods_issues.issued_date', '<', $date)
                    ->orWhere(function ($inner) use ($receipt, $date) {
                        $inner->where('goods_issues.issued_date', '=', $date);

                        if ($receipt->created_at instanceof Carbon) {
                            $inner->where('goods_issues.created_at', '<=', $receipt->created_at);
                        }
                    });
            })
            ->sum('goods_issue_items.qty');

        return (float) $totalReceived - (float) $totalIssued;
    }

    private function calculateStockAfterForIssueItem(GoodsIssueItem $item, GoodsIssue $issue): float
    {
        $date = $issue->issued_date instanceof Carbon
            ? $issue->issued_date->toDateString()
            : (string) $issue->issued_date;

        $totalReceived = GoodsReceiptItem::query()
            ->join('goods_receipts', 'goods_receipts.id', '=', 'goods_receipt_items.goods_receipt_id')
            ->where('goods_receipt_items.material_id', $item->material_id)
            ->whereIn('goods_receipts.status', self::RECEIPT_STOCK_STATUSES)
            ->where(function ($query) use ($issue, $date) {
                $query->where('goods_receipts.received_date', '<', $date)
                    ->orWhere(function ($inner) use ($issue, $date) {
                        $inner->where('goods_receipts.received_date', '=', $date);

                        if ($issue->created_at instanceof Carbon) {
                            $inner->where('goods_receipts.created_at', '<=', $issue->created_at);
                        }
                    });
            })
            ->sum(DB::raw('(goods_receipt_items.qty - goods_receipt_items.returned_qty)'));

        $totalIssued = GoodsIssueItem::query()
            ->join('goods_issues', 'goods_issues.id', '=', 'goods_issue_items.goods_issue_id')
            ->where('goods_issue_items.material_id', $item->material_id)
            ->whereIn('goods_issues.status', self::ISSUE_STOCK_STATUSES)
            ->where(function ($query) use ($item, $issue, $date) {
                $query->where('goods_issues.issued_date', '<', $date)
                    ->orWhere(function ($inner) use ($item, $issue, $date) {
                        $inner->where('goods_issues.issued_date', '=', $date)
                            ->where(function ($deep) use ($item, $issue) {
                                if ($issue->created_at instanceof Carbon) {
                                    $deep->where('goods_issues.created_at', '<', $issue->created_at)
                                        ->orWhere(function ($nested) use ($item, $issue) {
                                            $nested->where('goods_issues.created_at', '=', $issue->created_at)
                                                ->where('goods_issue_items.id', '<=', $item->id);
                                        });
                                } else {
                                    $deep->where('goods_issue_items.id', '<=', $item->id);
                                }
                            });
                    });
            })
            ->sum('goods_issue_items.qty');

        return (float) $totalReceived - (float) $totalIssued;
    }

    private function storeMovement(
        int $materialId,
        ?int $projectId,
        ?int $userId,
        string $movementType,
        float $quantity,
        float $stockBefore,
        float $stockAfter,
        Carbon $occurredAt,
        ?string $remarks,
        Model $reference,
    ): void {
        StockMovement::create([
            'material_id' => $materialId,
            'project_id' => $projectId,
            'created_by' => $userId,
            'movement_type' => $movementType,
            'reference_type' => $reference->getMorphClass(),
            'reference_id' => $reference->getKey(),
            'quantity' => round($quantity, 2),
            'stock_before' => round($stockBefore, 2),
            'stock_after' => round($stockAfter, 2),
            'occurred_at' => $occurredAt,
            'remarks' => $remarks,
        ]);
    }
}
