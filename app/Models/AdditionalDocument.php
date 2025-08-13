<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AdditionalDocument extends Model
{
    protected $fillable = [
        'type_id',
        'document_number',
        'document_date',
        'po_no',
        'project',
        'receive_date',
        'created_by',
        'attachment',
        'remarks',
        'flag',
        'status',
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
        // Admin and superadmin can edit any document
        if ($user->hasRole(['admin', 'superadmin'])) {
            return true;
        }

        // Regular users can only edit their own documents
        return $this->created_by === $user->id;
    }

    /**
     * Check if the document can be deleted by the given user.
     */
    public function canBeDeletedBy(User $user): bool
    {
        // Admin and superadmin can delete any document
        if ($user->hasRole(['admin', 'superadmin'])) {
            return true;
        }

        // Regular users can only delete their own documents
        return $this->created_by === $user->id;
    }

    /**
     * Invoices linked to this additional document.
     */
    public function invoices(): BelongsToMany
    {
        return $this->belongsToMany(Invoice::class, 'additional_document_invoice')->withTimestamps();
    }
}
