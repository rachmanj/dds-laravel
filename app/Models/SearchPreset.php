<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SearchPreset extends Model
{
    protected $fillable = [
        'user_id',
        'model_type',
        'name',
        'filters',
    ];

    protected $casts = [
        'filters' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
