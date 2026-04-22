<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolarPriceHistory extends Model
{
    protected $fillable = [
        'invoice_id',
        'invoice_line_detail_id',
        'unit_price',
        'period_start',
        'period_end',
        'quantity',
        'amount',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:4',
            'period_start' => 'date',
            'period_end' => 'date',
            'quantity' => 'decimal:4',
            'amount' => 'decimal:2',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function invoiceLineDetail(): BelongsTo
    {
        return $this->belongsTo(InvoiceLineDetail::class, 'invoice_line_detail_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Rows whose period contains the given calendar day (inclusive).
     *
     * @param  Builder<static>  $query
     */
    public function scopeActiveForDate(Builder $query, ?string $date = null): Builder
    {
        $d = $date ?? now()->toDateString();

        return $query->whereDate('period_start', '<=', $d)
            ->whereDate('period_end', '>=', $d);
    }
}
