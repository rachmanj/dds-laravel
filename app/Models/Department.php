<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
