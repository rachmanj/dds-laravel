<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogisticsInventoryPivot extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'snapshot_id',
        'project',
        'category',
        'sum_instock',
        'sum_value',
    ];

    protected function casts(): array
    {
        return [
            'sum_instock' => 'decimal:4',
            'sum_value' => 'decimal:2',
        ];
    }

    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(LogisticsInventorySnapshot::class, 'snapshot_id');
    }
}
