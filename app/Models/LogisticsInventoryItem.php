<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogisticsInventoryItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'snapshot_id',
        'model_no',
        'unit_no',
        'item_code',
        'item_name',
        'category',
        'uom',
        'instock',
        'committed',
        'ordered',
        'currency',
        'last_price',
        'total_value',
        'whs_code',
        'whs_name',
        'project',
        'status',
        'last_mr_no',
        'last_mi_no',
    ];

    protected function casts(): array
    {
        return [
            'instock' => 'decimal:4',
            'committed' => 'decimal:4',
            'ordered' => 'decimal:4',
            'last_price' => 'decimal:4',
            'total_value' => 'decimal:2',
        ];
    }

    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(LogisticsInventorySnapshot::class, 'snapshot_id');
    }
}
