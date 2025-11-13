<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Carbon\Carbon;

class AdditionalDocument extends Model
{
    protected $fillable = [
        'type_id',
        'document_number',
        'document_date',
        'po_no',
        'vendor_code',
        'project',
        'receive_date',
        'created_by',
        'attachment',
        'remarks',
        'flag',
        'status',
        'distribution_status',
        'cur_loc',
        'ito_creator',
        'grpo_no',
        'origin_wh',
        'destination_wh',
        'batch_no',
    ];

    protected $casts = [
        'document_date' => 'date',
        'receive_date' => 'date',
    ];

    /**
     * Get the document type that owns the document.
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(AdditionalDocumentType::class, 'type_id');
    }

    /**
     * Get the user that created the document.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user's department location code.
     */
    public function getCreatorLocationCodeAttribute()
    {
        return $this->creator ? $this->creator->department_location_code : null;
    }

    /**
     * Scope a query to only include documents for a specific location.
     */
    public function scopeForLocation($query, $locationCode)
    {
        return $query->where('cur_loc', $locationCode);
    }

    /**
     * Scope a query to only include documents created by a specific user.
     */
    public function scopeCreatedBy($query, $userId)
    {
        return $query->where('created_by', $userId);
    }

    /**
     * Scope a query to only include active documents.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'open');
    }

    /**
     * Check if the document can be edited by the given user.
     */
    public function canBeEditedBy(User $user): bool
    {
        // Check if user has edit permission
        if (!$user->can('edit-additional-documents')) {
            return false;
        }

        // Admin and superadmin can edit any document
        if ($user->hasRole(['admin', 'superadmin'])) {
            return true;
        }

        // Accounting users can edit ANY document (not just in their department)
        if ($user->hasRole('accounting')) {
            return true;
        }

        // Other users with edit permission can edit documents in their department
        $userLocationCode = $user->department_location_code;
        if ($userLocationCode && $this->cur_loc === $userLocationCode) {
            return true;
        }

        // Fallback: users can edit their own documents
        return $this->created_by === $user->id;
    }

    /**
     * Check if the document can be deleted by the given user.
     */
    public function canBeDeletedBy(User $user): bool
    {
        // Admin, superadmin, and accounting can delete any document
        if ($user->hasAnyRole(['admin', 'superadmin', 'accounting'])) {
            return true;
        }

        // Regular users can only delete their own documents
        return $this->created_by === $user->id;
    }

    /**
     * Scope a query to only include documents available for distribution.
     * 
     * Documents are NOT available if they are:
     * - 'in_transit' (currently being distributed to another department)
     * - 'unaccounted_for' (missing or damaged in previous distribution)
     * 
     * Note: 'distributed' documents are now included to allow re-distribution
     */
    public function scopeAvailableForDistribution($query)
    {
        return $query->whereIn('distribution_status', ['available', 'distributed']);
    }

    /**
     * Scope a query to only include documents currently in transit.
     */
    public function scopeInTransit($query)
    {
        return $query->where('distribution_status', 'in_transit');
    }

    /**
     * Scope a query to only include distributed documents.
     */
    public function scopeDistributed($query)
    {
        return $query->where('distribution_status', 'distributed');
    }

    public function scopeUnaccountedFor($query)
    {
        return $query->where('distribution_status', 'unaccounted_for');
    }



    /**
     * Invoices linked to this additional document.
     */
    public function invoices(): BelongsToMany
    {
        return $this->belongsToMany(Invoice::class, 'additional_document_invoice')->withTimestamps();
    }

    /**
     * Distributions that include this additional document.
     */
    public function distributions(): BelongsToMany
    {
        return $this->belongsToMany(Distribution::class, 'distribution_documents', 'document_id', 'distribution_id')
            ->wherePivot('document_type', AdditionalDocument::class);
    }

    /**
     * Get the date when this document was last received at its current location
     * This is used for accurate aging calculation per department
     */
    public function getCurrentLocationArrivalDateAttribute()
    {
        // If document has never been distributed, use original receive_date
        if ($this->distribution_status === 'available' && !$this->hasBeenDistributed()) {
            return $this->receive_date ?: $this->created_at;
        }

        // Find the most recent distribution where this document was received
        $lastDistribution = $this->distributions()
            ->whereHas('documents', function ($query) {
                $query->where('document_id', $this->id)
                    ->where('document_type', AdditionalDocument::class)
                    ->where('receiver_verification_status', 'verified');
            })
            ->whereNotNull('received_at')
            ->orderBy('received_at', 'desc')
            ->first();

        if ($lastDistribution) {
            return $lastDistribution->received_at;
        }

        // Fallback to original receive_date
        return $this->receive_date ?: $this->created_at;
    }

    /**
     * Calculate how many days this document has been in its current location
     */
    public function getDaysInCurrentLocationAttribute()
    {
        $arrivalDate = $this->current_location_arrival_date;
        return $arrivalDate ? $arrivalDate->diffInDays(now()) : 0;
    }

    /**
     * Get age category for current location
     */
    public function getCurrentLocationAgeCategoryAttribute()
    {
        $days = $this->days_in_current_location;

        if ($days <= 7) {
            return '0-7_days';
        } elseif ($days <= 14) {
            return '8-14_days';
        } elseif ($days <= 30) {
            return '15-30_days';
        } else {
            return '30_plus_days';
        }
    }

    /**
     * Check if document has been distributed before
     */
    public function hasBeenDistributed()
    {
        return $this->distributions()->exists();
    }

    /**
     * Check if document location can be manually changed
     * Location cannot be changed if document has any distribution history
     */
    public function canChangeLocationManually(): bool
    {
        return !$this->hasBeenDistributed();
    }

    /**
     * Get distribution history for this document
     */
    public function getDistributionHistory()
    {
        return $this->distributions()
            ->with(['originDepartment', 'destinationDepartment', 'documents' => function ($query) {
                $query->where('document_id', $this->id)
                    ->where('document_type', AdditionalDocument::class);
            }])
            ->orderBy('received_at', 'desc')
            ->get();
    }
}
