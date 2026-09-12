<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentLocationOverride extends Model
{
    protected $fillable = [
        'document_type',
        'document_id',
        'from_loc',
        'to_loc',
        'reason',
        'overridden_by',
    ];

    public function overriddenBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'overridden_by');
    }
}
