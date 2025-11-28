<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Project extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'client',
        'location',
        'start_date',
        'end_date',
        'budget',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'budget' => 'decimal:2',
    ];

    protected $attributes = [
        'status' => 'planned',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $project) {
            if (! $project->code) {
                $project->code = static::generateNextCode();
            }
        });
    }

    public static function generateNextCode(): string
    {
        $prefix = 'PRJ-';
        $startPosition = strlen($prefix) + 1;

        $maxNumber = DB::table((new static())->getTable())
            ->selectRaw('MAX(CAST(SUBSTRING(code, ?) AS UNSIGNED)) as max_number', [$startPosition])
            ->where('code', 'like', $prefix.'%')
            ->value('max_number');

        $nextNumber = ((int) $maxNumber) + 1;

        return sprintf('%s%03d', $prefix, $nextNumber);
    }
}
