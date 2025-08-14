<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DistributionType extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    // Relationships
    public function distributions()
    {
        return $this->hasMany(Distribution::class, 'type_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCode($query, $code)
    {
        return $query->where('code', $code);
    }

    // Accessors
    public function getDisplayNameAttribute()
    {
        return "{$this->name} ({$this->code})";
    }
}
