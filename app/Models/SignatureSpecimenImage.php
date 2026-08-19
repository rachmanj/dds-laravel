<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SignatureSpecimenImage extends Model
{
    protected $fillable = [
        'specimen_id',
        'path',
    ];

    public function specimen(): BelongsTo
    {
        return $this->belongsTo(SignatureSpecimen::class, 'specimen_id');
    }
}
