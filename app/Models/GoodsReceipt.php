<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GoodsReceipt extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'purchase_order_id',
        'project_id',
        'supplier_id',
        'received_date',
        'status',
        'received_by',
        'verified_by',
        'verified_at',
        'remarks',
    ];

    protected $casts = [
        'received_date' => 'date',
        'verified_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'draft',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(GoodsReceiptItem::class);
    }

    public static function generateCode(): string
    {
        $prefix = 'GR-' . now()->format('Ym');

        $last = self::where('code', 'like', $prefix . '%')
            ->orderByDesc('code')
            ->first();

        $number = $last
            ? (int) substr($last->code, -4) + 1
            : 1;

        return $prefix . str_pad($number, 4, '0', STR_PAD_LEFT);
    }
}
