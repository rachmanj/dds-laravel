<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Distribution extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'distribution_number',
        'type_id',
        'origin_department_id',
        'destination_department_id',
        'document_type',
        'created_by',
        'status',
        'sender_verified_at',
        'sent_at',
        'received_at',
        'receiver_verified_at',
        'sender_verified_by',
        'sender_verification_notes',
        'receiver_verified_by',
        'receiver_verification_notes',
        'has_discrepancies',
        'notes',
        'year',
        'sequence'
    ];

    protected $casts = [
        'sender_verified_at' => 'datetime',
        'sent_at' => 'datetime',
        'received_at' => 'datetime',
        'receiver_verified_at' => 'datetime',
        'has_discrepancies' => 'boolean',
        'year' => 'integer',
        'sequence' => 'integer'
    ];

    // Relationships
    public function type(): BelongsTo
    {
        return $this->belongsTo(DistributionType::class, 'type_id');
    }

    public function originDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'origin_department_id');
    }

    public function destinationDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'destination_department_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function senderVerifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_verified_by');
    }

    public function receiverVerifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_verified_by');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(DistributionDocument::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(DistributionHistory::class);
    }

    // Polymorphic relationships for documents
    public function invoices(): MorphToMany
    {
        return $this->morphedByMany(Invoice::class, 'document', 'distribution_documents', 'distribution_id', 'document_id');
    }

    public function additionalDocuments(): MorphToMany
    {
        return $this->morphedByMany(AdditionalDocument::class, 'document', 'distribution_documents', 'distribution_id', 'document_id');
    }

    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByDepartment($query, $departmentId)
    {
        return $query->where(function ($q) use ($departmentId) {
            $q->where('origin_department_id', $departmentId)
                ->orWhere('destination_department_id', $departmentId);
        });
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('created_by', $userId);
    }

    public function scopeByYear($query, $year)
    {
        return $query->where('year', $year);
    }

    public function scopeByType($query, $typeId)
    {
        return $query->where('type_id', $typeId);
    }

    public function scopeByYearAndDepartment($query, $year, $departmentId)
    {
        return $query->where('year', $year)
            ->where('origin_department_id', $departmentId);
    }

    /**
     * Get next sequence number for a department/year combination
     * This method finds the next available sequence number
     */
    public static function getNextSequence(int $year, int $departmentId): int
    {
        // Get all existing sequences for this department/year
        $existingSequences = static::where('year', $year)
            ->where('origin_department_id', $departmentId)
            ->pluck('sequence')
            ->sort()
            ->values();

        if ($existingSequences->isEmpty()) {
            return 1; // First sequence
        }

        // Find the first gap in the sequence, or use the next number after the highest
        $expectedSequence = 1;

        foreach ($existingSequences as $existingSequence) {
            if ($existingSequence !== $expectedSequence) {
                // Found a gap, use this sequence number
                return $expectedSequence;
            }
            $expectedSequence++;
        }

        // No gaps found, use the next number after the highest
        return $existingSequences->max() + 1;
    }

    /**
     * Validate distribution number format
     */
    public static function isValidDistributionNumberFormat(string $distributionNumber): bool
    {
        // Format: YY/LOCATION/DDS/0001
        $pattern = '/^\d{2}\/[A-Z0-9]+\/DDS\/\d{4}$/';
        return preg_match($pattern, $distributionNumber) === 1;
    }

    /**
     * Get all distribution numbers for a specific year
     */
    public static function getDistributionNumbersByYear(int $year): array
    {
        return static::where('year', $year)
            ->pluck('distribution_number')
            ->toArray();
    }

    /**
     * Get distribution numbers by department and year
     */
    public static function getDistributionNumbersByDepartmentAndYear(int $departmentId, int $year): array
    {
        return static::where('origin_department_id', $departmentId)
            ->where('year', $year)
            ->orderBy('sequence')
            ->pluck('distribution_number')
            ->toArray();
    }

    // Workflow methods
    public function canVerifyBySender(): bool
    {
        return $this->status === 'draft';
    }

    public function canSend(): bool
    {
        return $this->status === 'verified_by_sender';
    }

    public function canReceive(): bool
    {
        // Only users of the destination department can receive a distribution when it's sent
        if ($this->status !== 'sent') {
            return false;
        }

        // Check if current user belongs to destination department
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        // Admin and superadmin can always receive distributions
        if (array_intersect($user->roles->pluck('name')->toArray(), ['superadmin', 'admin'])) {
            return true;
        }

        // Regular users can only receive if they belong to destination department
        return $user->department && $user->department->id === $this->destination_department_id;
    }

    public function canVerifyByReceiver(): bool
    {
        return $this->status === 'received';
    }

    public function canComplete(): bool
    {
        return $this->status === 'verified_by_receiver';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    // Status transition methods
    public function markAsVerifiedBySender(User $user, string $notes = null): bool
    {
        if (!$this->canVerifyBySender()) {
            return false;
        }

        $this->update([
            'status' => 'verified_by_sender',
            'sender_verified_at' => now(),
            'sender_verified_by' => $user->id,
            'sender_verification_notes' => $notes
        ]);

        return true;
    }

    public function markAsSent(): bool
    {
        if (!$this->canSend()) {
            return false;
        }

        $this->update([
            'status' => 'sent',
            'sent_at' => now()
        ]);

        return true;
    }

    public function markAsReceived(): bool
    {
        if (!$this->canReceive()) {
            return false;
        }

        $this->update([
            'status' => 'received',
            'received_at' => now()
        ]);

        return true;
    }

    public function markAsVerifiedByReceiver(User $user, string $notes = null, bool $hasDiscrepancies = false): bool
    {
        if (!$this->canVerifyByReceiver()) {
            return false;
        }

        $this->update([
            'status' => 'verified_by_receiver',
            'receiver_verified_at' => now(),
            'receiver_verified_by' => $user->id,
            'receiver_verification_notes' => $notes,
            'has_discrepancies' => $hasDiscrepancies
        ]);

        return true;
    }

    public function markAsCompleted(): bool
    {
        if (!$this->canComplete()) {
            return false;
        }

        $this->update([
            'status' => 'completed'
        ]);

        return true;
    }

    // Accessors
    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'badge-secondary',
            'verified_by_sender' => 'badge-info',
            'sent' => 'badge-warning',
            'received' => 'badge-primary',
            'verified_by_receiver' => 'badge-success',
            'completed' => 'badge-success',
            default => 'badge-secondary'
        };
    }

    public function getStatusDisplayAttribute(): string
    {
        return ucwords(str_replace('_', ' ', $this->status));
    }

    public function getWorkflowProgressAttribute(): int
    {
        return match ($this->status) {
            'draft' => 0,
            'verified_by_sender' => 20,
            'sent' => 40,
            'received' => 60,
            'verified_by_receiver' => 80,
            'completed' => 100,
            default => 0
        };
    }
}
