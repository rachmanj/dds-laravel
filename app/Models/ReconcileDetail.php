<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ReconcileDetail extends Model
{
    protected $fillable = [
        'invoice_no',
        'vendor_id',
        'invoice_date',
        'user_id',
        'flag',
    ];

    protected $casts = [
        'invoice_date' => 'date',
    ];

    /**
     * Get the user that owns the reconcile detail.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the supplier for this reconcile detail.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'vendor_id');
    }

    /**
     * Get the matching invoice from the internal system.
     * Uses LIKE pattern matching to find invoices with similar invoice numbers.
     */
    public function matchingInvoice(): HasOne
    {
        return $this->hasOne(Invoice::class, 'invoice_number', 'invoice_no');
    }

    /**
     * Get the first matching invoice using LIKE pattern matching.
     * This method provides more flexible matching than the relationship above.
     * Loads distributions relationship to ensure distribution numbers are available.
     */
    public function getMatchingInvoiceAttribute()
    {
        if (!$this->invoice_no) {
            return null;
        }

        return Invoice::where('invoice_number', 'LIKE', '%' . $this->invoice_no . '%')
            ->orWhere('faktur_no', 'LIKE', '%' . $this->invoice_no . '%')
            ->with(['distributions' => function ($query) {
                $query->orderBy('created_at', 'desc');
            }])
            ->first();
    }

    /**
     * Scope a query to only include records for a specific user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to only include records with a specific flag.
     */
    public function scopeWithFlag($query, $flag)
    {
        return $query->where('flag', $flag);
    }

    /**
     * Scope a query to only include records without a flag (processed records).
     */
    public function scopeWithoutFlag($query)
    {
        return $query->whereNull('flag');
    }

    /**
     * Scope a query to only include records for a specific vendor.
     */
    public function scopeForVendor($query, $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    /**
     * Scope a query to only include records with matching invoices.
     */
    public function scopeWithMatchingInvoices($query)
    {
        return $query->whereHas('matchingInvoice');
    }

    /**
     * Scope a query to only include records without matching invoices.
     */
    public function scopeWithoutMatchingInvoices($query)
    {
        return $query->whereDoesntHave('matchingInvoice');
    }

    /**
     * Get the reconciliation status for this record.
     */
    public function getReconciliationStatusAttribute()
    {
        $matchingInvoice = $this->matching_invoice;

        if (!$matchingInvoice) {
            return 'no_match';
        }

        // Check if amounts match (if available)
        if ($this->invoice_date && $matchingInvoice->invoice_date) {
            if ($this->invoice_date->format('Y-m-d') === $matchingInvoice->invoice_date->format('Y-m-d')) {
                return 'matched';
            }
        }

        return 'partial_match';
    }

    /**
     * Get formatted reconciliation data for display.
     */
    public function getReconciliationDataAttribute()
    {
        $matchingInvoice = $this->matching_invoice;

        return [
            'external_invoice_no' => $this->invoice_no,
            'internal_invoice_no' => $matchingInvoice ? $matchingInvoice->invoice_number : null,
            'vendor_name' => $matchingInvoice && $matchingInvoice->supplier ? $matchingInvoice->supplier->name : null,
            'receive_date' => $matchingInvoice ? $matchingInvoice->receive_date : null,
            'amount' => $matchingInvoice ? number_format($matchingInvoice->amount, 2) : null,
            'spi_no' => $matchingInvoice ? $matchingInvoice->sap_doc : null,
            'spi_date' => $matchingInvoice ? $matchingInvoice->payment_date : null,
            'status' => $this->reconciliation_status,
        ];
    }
}
