<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DistributionHistory extends Model
{
    protected $fillable = [
        'distribution_id',
        'user_id',
        'action',
        'action_type',
        'old_status',
        'new_status',
        'document_id',
        'document_type',
        'notes',
        'metadata',
        'action_performed_at'
    ];

    protected $casts = [
        'metadata' => 'array',
        'action_performed_at' => 'datetime'
    ];

    // Relationships
    public function distribution(): BelongsTo
    {
        return $this->belongsTo(Distribution::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    public function scopeByActionType($query, $actionType)
    {
        return $query->where('action_type', $actionType);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('action_performed_at', [$startDate, $endDate]);
    }

    public function scopeWorkflowTransitions($query)
    {
        return $query->where('action_type', 'workflow_transition');
    }

    public function scopeDocumentVerifications($query)
    {
        return $query->where('action_type', 'document_verification');
    }

    public function scopeDiscrepancyReports($query)
    {
        return $query->where('action_type', 'discrepancy_reported');
    }

    // Static methods for creating history entries
    public static function logWorkflowTransition(
        Distribution $distribution,
        User $user,
        string $oldStatus,
        string $newStatus,
        string $notes = null
    ): self {
        return self::create([
            'distribution_id' => $distribution->id,
            'user_id' => $user->id,
            'action' => "status_changed_from_{$oldStatus}_to_{$newStatus}",
            'action_type' => 'workflow_transition',
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'notes' => $notes,
            'action_performed_at' => now()
        ]);
    }

    public static function logDocumentVerification(
        Distribution $distribution,
        User $user,
        string $verificationType, // 'sender' or 'receiver'
        string $documentType,
        int $documentId,
        string $status,
        string $notes = null
    ): self {
        return self::create([
            'distribution_id' => $distribution->id,
            'user_id' => $user->id,
            'action' => "document_{$verificationType}_verification",
            'action_type' => 'document_verification',
            'document_type' => $documentType,
            'document_id' => $documentId,
            'metadata' => [
                'verification_type' => $verificationType,
                'status' => $status
            ],
            'notes' => $notes,
            'action_performed_at' => now()
        ]);
    }

    public static function logDiscrepancyReport(
        Distribution $distribution,
        User $user,
        string $documentType,
        int $documentId,
        string $discrepancyType, // 'missing' or 'damaged'
        string $notes = null
    ): self {
        return self::create([
            'distribution_id' => $distribution->id,
            'user_id' => $user->id,
            'action' => 'discrepancy_reported',
            'action_type' => 'discrepancy_reported',
            'document_type' => $documentType,
            'document_id' => $documentId,
            'metadata' => [
                'discrepancy_type' => $discrepancyType
            ],
            'notes' => $notes,
            'action_performed_at' => now()
        ]);
    }

    // Accessors
    public function getActionDisplayAttribute(): string
    {
        return ucwords(str_replace('_', ' ', $this->action));
    }

    public function getActionTypeDisplayAttribute(): string
    {
        return ucwords(str_replace('_', ' ', $this->action_type));
    }

    public function getTimeAgoAttribute(): string
    {
        return $this->action_performed_at->diffForHumans();
    }
}
