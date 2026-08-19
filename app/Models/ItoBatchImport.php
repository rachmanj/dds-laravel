<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ItoBatchImport extends Model
{
    protected $fillable = [
        'filename',
        'stored_path',
        'total_pages',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'total_pages' => 'integer',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ItoBatchItem::class, 'batch_id');
    }

    public function matchedCount(): int
    {
        return $this->items()->where('status', 'matched')->count();
    }

    public function reviewNeededCount(): int
    {
        return $this->items()->whereIn('status', ['not_found', 'ambiguous', 'low_confidence'])->count();
    }
}
