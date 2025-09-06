<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DistributionDocument extends Model
{
    protected $fillable = [
        'distribution_id',
        'document_type',
        'document_id',
        'origin_cur_loc',
        'skip_verification',
        'sender_verified',
        'sender_verification_status',
        'sender_verification_notes',
        'receiver_verified',
        'receiver_verification_status',
        'receiver_verification_notes'
    ];

    protected $casts = [
        'sender_verified' => 'boolean',
        'receiver_verified' => 'boolean',
        'skip_verification' => 'boolean',
    ];

    // Relationships
    public function distribution(): BelongsTo
    {
        return $this->belongsTo(Distribution::class);
    }

    public function document(): MorphTo
    {
        return $this->morphTo();
    }

    // Scopes
    public function scopeByVerificationStatus($query, $status)
    {
        return $query->where('sender_verification_status', $status)
            ->orWhere('receiver_verification_status', $status);
    }

    public function scopeSenderVerified($query)
    {
        return $query->where('sender_verified', true);
    }

    public function scopeReceiverVerified($query)
    {
        return $query->where('receiver_verified', true);
    }

    public function scopeWithDiscrepancies($query)
    {
        return $query->where(function ($q) {
            $q->where('sender_verification_status', 'missing')
                ->orWhere('sender_verification_status', 'damaged')
                ->orWhere('receiver_verification_status', 'missing')
                ->orWhere('receiver_verification_status', 'damaged');
        });
    }

    // Verification methods
    public function markAsSenderVerified(string $status = 'verified', string $notes = null): bool
    {
        $this->update([
            'sender_verified' => true,
            'sender_verification_status' => $status,
            'sender_verification_notes' => $notes
        ]);

        return true;
    }

    public function markAsReceiverVerified(string $status = 'verified', string $notes = null): bool
    {
        $this->update([
            'receiver_verified' => true,
            'receiver_verification_status' => $status,
            'receiver_verification_notes' => $notes
        ]);

        return true;
    }

    // Accessors
    public function getHasDiscrepanciesAttribute(): bool
    {
        return in_array($this->sender_verification_status, ['missing', 'damaged']) ||
            in_array($this->receiver_verification_status, ['missing', 'damaged']);
    }

    public function getVerificationStatusBadgeClassAttribute(): string
    {
        if ($this->has_discrepancies) {
            return 'badge-danger';
        }

        if ($this->sender_verified && $this->receiver_verified) {
            return 'badge-success';
        }

        if ($this->sender_verified) {
            return 'badge-warning';
        }

        return 'badge-secondary';
    }

    public function getVerificationStatusDisplayAttribute(): string
    {
        if ($this->has_discrepancies) {
            return 'Has Discrepancies';
        }

        if ($this->sender_verified && $this->receiver_verified) {
            return 'Fully Verified';
        }

        if ($this->sender_verified) {
            return 'Sender Verified';
        }

        return 'Pending Verification';
    }
}
