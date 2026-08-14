<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceType extends Model
{
    protected $fillable = [
        'type_name',
        'is_consignment',
    ];

    protected $casts = [
        'is_consignment' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->whereNotNull('type_name');
    }

    public static function isConsignmentTypeId(mixed $typeId): bool
    {
        if ($typeId === null || $typeId === '') {
            return false;
        }

        return static::query()
            ->whereKey($typeId)
            ->where('is_consignment', true)
            ->exists();
    }
}
