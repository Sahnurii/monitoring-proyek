<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Material extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'sku',
        'name',
        'unit_id',
        'min_stock',
    ];

    protected $casts = [
        'min_stock' => 'decimal:2',
    ];

    protected $attributes = [
        'min_stock' => 0,
    ];

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }
}
