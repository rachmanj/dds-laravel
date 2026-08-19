<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SignatureSpecimen extends Model
{
    protected $fillable = [
        'name',
        'nik',
        'department_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'signature_specimen_project', 'specimen_id', 'project_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(SignatureSpecimenImage::class, 'specimen_id');
    }

    public function matchResults(): HasMany
    {
        return $this->hasMany(SignatureMatchResult::class, 'specimen_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
