<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceType extends Model
{
    protected $fillable = [
        'type_name'
    ];

    public function scopeActive($query)
    {
        return $query->whereNotNull('type_name');
    }
}
