<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class MaterialRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'project_id',
        'requested_by',
        'request_date',
        'status',
        'notes',
        'approved_by',
        'approved_at',
        'total_amount',
    ];

    protected $casts = [
        'request_date' => 'date',
        'approved_at' => 'datetime',
        'total_amount' => 'decimal:2',
    ];

    protected $attributes = [
        'status' => 'draft',
        'total_amount' => 0,
    ];

    protected static function booted(): void
    {
        static::creating(function (self $materialRequest) {
            if (! $materialRequest->code) {
                $materialRequest->code = static::generateNextCode();
            }
        });
    }

    public static function generateNextCode(): string
    {
        $prefix = 'MRQ-';
        $startPosition = strlen($prefix) + 1;

        $maxNumber = DB::table((new static())->getTable())
            ->selectRaw('MAX(CAST(SUBSTRING(code, ?) AS UNSIGNED)) as max_number', [$startPosition])
            ->where('code', 'like', $prefix.'%')
            ->value('max_number');

        $nextNumber = ((int) $maxNumber) + 1;

        return sprintf('%s%03d', $prefix, $nextNumber);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(MaterialRequestItem::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }
}
