<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryPartSpbItem extends Model
{
    protected $fillable = [
        'delivery_part_spb_id',
        'part_number',
        'description',
        'qty',
        'uom',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'decimal:4',
        ];
    }

    public function spb(): BelongsTo
    {
        return $this->belongsTo(DeliveryPartSpb::class, 'delivery_part_spb_id');
    }
}
