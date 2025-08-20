<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
}
