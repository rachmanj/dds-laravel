<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdditionalDocumentType extends Model
{
    protected $fillable = [
        'type_name'
    ];

    /**
     * Get the documents for this type.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(AdditionalDocument::class, 'type_id');
    }

    public function scopeActive($query)
    {
        return $query->whereNotNull('type_name');
    }

    /**
     * Scope a query to order by name ascending.
     */
    public function scopeOrderByName($query)
    {
        return $query->orderBy('type_name', 'asc');
    }
}
