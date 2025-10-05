<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Carbon\Carbon;

class Invoice extends Model
{
    protected $fillable = [
        'invoice_number',
        'faktur_no',
        'invoice_date',
        'receive_date',
        'supplier_id',
        'po_no',
        'receive_project',
        'invoice_project',
        'payment_project',
        'currency',
        'amount',
        'type_id',
        'payment_date',
        'payment_status',
        'paid_by',
        'paid_at',
        'remarks',
        'cur_loc',
        'status',
        'distribution_status',
        'created_by',
        'duration1',
        'duration2',
        'sap_doc',
        'flag',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'receive_date' => 'date',
        'payment_date' => 'date',
        'paid_at' => 'datetime',
        'amount' => 'decimal:2',
    ];

    /**
     * Get the supplier that owns the invoice.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the invoice type that owns the invoice.
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(InvoiceType::class, 'type_id');
    }

    /**
     * Get the user that created the invoice.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user that marked the invoice as paid.
     */
    public function paidByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    /**
     * Get the user associated with the invoice (alias for paidByUser).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    /**
     * Get the attachments for the invoice.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(InvoiceAttachment::class);
    }

    /**
     * Additional documents linked to this invoice.
     */
    public function additionalDocuments(): BelongsToMany
    {
        return $this->belongsToMany(AdditionalDocument::class, 'additional_document_invoice')->withTimestamps();
    }

    /**
     * Distributions that include this invoice.
     */
    public function distributions(): BelongsToMany
    {
        return $this->morphedByMany(Distribution::class, 'document', 'distribution_documents', 'document_id', 'distribution_id');
    }

    /**
     * Get the department for the invoice via location code.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'cur_loc', 'location_code');
    }

    /**
     * Scope a query to only include invoices available for distribution.
     * 
     * Documents are NOT available if they are:
     * - 'in_transit' (currently being distributed to another department)
     * - 'distributed' (already distributed to another department)
     * - 'unaccounted_for' (missing or damaged in previous distribution)
     */
    public function scopeAvailableForDistribution($query)
    {
        return $query->where('distribution_status', 'available');
    }

    /**
     * Scope a query to only include invoices currently in transit.
     */
    public function scopeInTransit($query)
    {
        return $query->where('distribution_status', 'in_transit');
    }

    /**
     * Scope a query to only include distributed invoices.
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
     * Get the receive project information.
     */
    public function receiveProjectInfo(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'receive_project', 'code');
    }

    /**
     * Get the invoice project information.
     */
    public function invoiceProjectInfo(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'invoice_project', 'code');
    }

    /**
     * Get the payment project information.
     */
    public function paymentProjectInfo(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'payment_project', 'code');
    }

    /**
     * Get the user's department location code.
     */
    public function getCreatorLocationCodeAttribute()
    {
        return $this->creator ? $this->creator->department_location_code : null;
    }

    /**
     * Scope a query to only include invoices for a specific location.
     */
    public function scopeForLocation($query, $locationCode)
    {
        return $query->where('cur_loc', $locationCode);
    }

    /**
     * Scope a query to only include invoices created by a specific user.
     */
    public function scopeCreatedBy($query, $userId)
    {
        return $query->where('created_by', $userId);
    }

    /**
     * Scope a query to only include invoices with specific status.
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include active invoices.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'open');
    }

    /**
     * Scope a query to only include invoices pending payment.
     */
    public function scopePendingPayment($query)
    {
        return $query->where('payment_status', 'pending');
    }

    /**
     * Scope a query to only include paid invoices.
     */
    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    /**
     * Scope a query to only include invoices overdue for payment.
     */
    public function scopeOverdue($query, $days = 30)
    {
        return $query->where('payment_status', 'pending')
            ->where('receive_date', '<=', now()->subDays($days));
    }

    /**
     * Scope a query to only include invoices in user's department.
     */
    public function scopeInUserDepartment($query, $userLocationCode)
    {
        return $query->where('cur_loc', $userLocationCode);
    }

    /**
     * Get formatted invoice date.
     */
    public function getFormattedInvoiceDateAttribute()
    {
        return $this->invoice_date ? $this->invoice_date->format('d-M-Y') : '-';
    }

    /**
     * Get formatted receive date.
     */
    public function getFormattedReceiveDateAttribute()
    {
        return $this->receive_date ? $this->receive_date->format('d-M-Y') : '-';
    }

    /**
     * Get formatted payment date.
     */
    public function getFormattedPaymentDateAttribute()
    {
        return $this->payment_date ? $this->payment_date->format('d-M-Y') : '-';
    }

    /**
     * Get formatted amount with currency.
     */
    public function getFormattedAmountAttribute()
    {
        return $this->currency . ' ' . number_format($this->amount, 2);
    }

    /**
     * Get formatted payment date.
     */
    public function getFormattedPaidAtAttribute()
    {
        return $this->paid_at ? $this->paid_at->format('d-M-Y H:i') : '-';
    }

    /**
     * Get days since invoice received in department.
     */
    public function getDaysSinceReceivedAttribute()
    {
        // If no receive_date, try to use created_at as fallback
        $dateToUse = $this->receive_date ?: $this->created_at;

        if (!$dateToUse) {
            return null;
        }

        // Calculate days and ensure it's a whole number
        $days = $dateToUse->diffInDays(now());
        return (int) round($days);
    }

    /**
     * Get overdue status for payment.
     */
    public function getIsOverdueAttribute()
    {
        $configDays = config('invoice.payment_overdue_days', 30);
        return $this->payment_status === 'pending' && $this->days_since_received > $configDays;
    }

    /**
     * Get payment status badge HTML.
     */
    public function getPaymentStatusBadgeAttribute()
    {
        $statusColors = [
            'pending' => 'badge-warning',
            'paid' => 'badge-success',
        ];

        $color = $statusColors[$this->payment_status] ?? 'badge-secondary';
        return '<span class="badge ' . $color . '">' . ucfirst($this->payment_status) . '</span>';
    }



    /**
     * Check if invoice number is unique for the given supplier.
     */
    public static function isInvoiceNumberUniqueForSupplier(string $invoiceNumber, int $supplierId, ?int $excludeId = null): bool
    {
        $query = static::where('supplier_id', $supplierId)
            ->where('invoice_number', $invoiceNumber);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return !$query->exists();
    }

    /**
     * Get duplicate invoice numbers for a supplier.
     */
    public static function getDuplicateInvoiceNumbersForSupplier(int $supplierId): array
    {
        return static::select('invoice_number')
            ->where('supplier_id', $supplierId)
            ->groupBy('invoice_number')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('invoice_number')
            ->toArray();
    }

    /**
     * Scope to find invoices with specific invoice number and supplier.
     */
    public function scopeBySupplierAndInvoiceNumber($query, int $supplierId, string $invoiceNumber)
    {
        return $query->where('supplier_id', $supplierId)
            ->where('invoice_number', $invoiceNumber);
    }

    /**
     * Get status badge HTML.
     */
    public function getStatusBadgeAttribute()
    {
        $statusColors = [
            'open' => 'badge-warning',
            'verify' => 'badge-info',
            'return' => 'badge-danger',
            'sap' => 'badge-primary',
            'close' => 'badge-success',
            'cancel' => 'badge-secondary',
        ];

        $color = $statusColors[$this->status] ?? 'badge-secondary';
        return '<span class="badge ' . $color . '">' . ucfirst($this->status) . '</span>';
    }

    /**
     * Get attachment count.
     */
    public function getAttachmentCountAttribute()
    {
        return $this->attachments()->count();
    }

    /**
     * Check if the invoice has attachments.
     */
    public function hasAttachments(): bool
    {
        return $this->attachments()->exists();
    }

    /**
     * Get the date when this invoice was last received at its current location
     * This is used for accurate aging calculation per department
     */
    public function getCurrentLocationArrivalDateAttribute()
    {
        // If invoice has never been distributed, use original receive_date
        if ($this->distribution_status === 'available' && !$this->hasBeenDistributed()) {
            return $this->receive_date ?: $this->created_at;
        }

        // Find the most recent distribution where this invoice was received
        $lastDistribution = $this->distributions()
            ->whereHas('documents', function ($query) {
                $query->where('document_id', $this->id)
                    ->where('document_type', Invoice::class)
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
     * Calculate how many days this invoice has been in its current location
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
     * Check if invoice has been distributed before
     */
    public function hasBeenDistributed()
    {
        return $this->distributions()->exists();
    }
}
