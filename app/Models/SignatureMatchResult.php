<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SignatureMatchResult extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'additional_document_id',
        'specimen_id',
        'score',
        'verdict',
        'model',
        'raw_response',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:3',
            'created_at' => 'datetime',
        ];
    }

    public function additionalDocument(): BelongsTo
    {
        return $this->belongsTo(AdditionalDocument::class);
    }

    public function specimen(): BelongsTo
    {
        return $this->belongsTo(SignatureSpecimen::class, 'specimen_id');
    }
}
