<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryPartItoCancel extends Model
{
    public const STATUS_REQUESTED = 'requested';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'doc_entry',
        'ito_no',
        'project_id',
        'item_code',
        'unit_no',
        'reason',
        'status',
        'requested_by',
        'requested_at',
        'executed_at',
        'verified_at',
        'attempts',
        'sap_message',
    ];

    protected function casts(): array
    {
        return [
            'doc_entry' => 'integer',
            'requested_at' => 'datetime',
            'executed_at' => 'datetime',
            'verified_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public static function hasActiveRequestForDocEntry(int $docEntry): bool
    {
        return self::query()
            ->where('doc_entry', $docEntry)
            ->where('status', self::STATUS_REQUESTED)
            ->exists();
    }
}
