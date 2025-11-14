<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SapLog extends Model
{
    protected $fillable = [
        'invoice_id',
        'action',
        'request_payload',
        'response_payload',
        'status',
        'error_message',
        'attempt_count',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'response_payload' => 'array',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
