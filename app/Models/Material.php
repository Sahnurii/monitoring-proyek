<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Material extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'sku',
        'name',
        'unit_id',
        'min_stock',
        'unit_price',
    ];

    protected $casts = [
        'min_stock' => 'decimal:2',
        'unit_price' => 'decimal:2',
    ];

    protected $attributes = [
        'min_stock' => 0,
        'unit_price' => 0,
    ];

    protected static function booted(): void
    {
        static::creating(function (self $material) {
            if (! $material->sku) {
                $material->sku = static::generateNextSku();
            }
        });
    }

    public static function generateNextSku(): string
    {
        $prefix = 'MAT-';
        $startPosition = strlen($prefix) + 1;

        $maxNumber = DB::table((new static())->getTable())
            ->selectRaw('MAX(CAST(SUBSTRING(sku, ?) AS UNSIGNED)) as max_number', [$startPosition])
            ->where('sku', 'like', $prefix.'%')
            ->value('max_number');

        $nextNumber = ((int) $maxNumber) + 1;

        return sprintf('%s%03d', $prefix, $nextNumber);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
