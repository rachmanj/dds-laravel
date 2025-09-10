<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    protected $fillable = [
        'name',
        'project',
        'location_code',
        'transit_code',
        'akronim',
        'sap_code'
    ];

    public function scopeActive($query)
    {
        return $query->whereNotNull('name');
    }

    /**
     * Get the invoices for this department.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'cur_loc', 'location_code');
    }
}
