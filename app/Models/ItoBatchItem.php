<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItoBatchItem extends Model
{
    protected $fillable = [
        'batch_id',
        'page_number',
        'extracted_ito_no',
        'matched_document_id',
        'status',
        'confidence',
        'attached_path',
        'resolved_by',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'page_number' => 'integer',
            'confidence' => 'decimal:3',
            'resolved_at' => 'datetime',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ItoBatchImport::class, 'batch_id');
    }

    public function matchedDocument(): BelongsTo
    {
        return $this->belongsTo(AdditionalDocument::class, 'matched_document_id');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function needsReview(): bool
    {
        return in_array($this->status, ['not_found', 'ambiguous', 'low_confidence'], true);
    }
}
