<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LogisticsInventorySnapshot extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'snapshot_date',
        'status',
        'row_count',
        'total_value',
        'error_message',
        'duration_ms',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'snapshot_date' => 'date',
            'row_count' => 'integer',
            'total_value' => 'decimal:2',
            'duration_ms' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function pivots(): HasMany
    {
        return $this->hasMany(LogisticsInventoryPivot::class, 'snapshot_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(LogisticsInventoryItem::class, 'snapshot_id');
    }
}
