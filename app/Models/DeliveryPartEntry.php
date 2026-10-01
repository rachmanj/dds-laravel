<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryPartEntry extends Model
{
    public const SOURCE_SAP = 'sap';

    public const SOURCE_MANUAL = 'manual';

    /**
     * @var list<string>
     */
    public const EKSPEDISI_OPTIONS = [
        'TRUCK ARKA',
        'EKSPEDISI NAMARA',
        'EKSPEDISI JNE',
    ];

    /**
     * @var list<string>
     */
    public const MANUAL_TRACKED_FIELDS = [
        'ito_no_override',
        'no_spb',
        'remarks_barang',
        'tgl_delivery',
        'transporter',
        'unit_kendaraan',
        'ekspedisi',
    ];

    protected $fillable = [
        'project_id',
        'ito_no',
        'ito_no_override',
        'item_code',
        'unit_no',
        'source',
        'tanggal_received',
        'source_ref',
        'no_spb',
        'remarks_barang',
        'tgl_delivery',
        'transporter',
        'unit_kendaraan',
        'ekspedisi',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_received' => 'date',
            'tgl_delivery' => 'date',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(DeliveryPartEntryHistory::class);
    }
}
