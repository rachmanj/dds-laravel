<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssistantRequestLog extends Model
{
    public const STATUS_SUCCESS = 'success';

    public const STATUS_ERROR = 'error';

    protected $fillable = [
        'user_id',
        'assistant_conversation_id',
        'status',
        'tools_invoked',
        'show_all_records',
        'user_message_length',
        'duration_ms',
        'error_summary',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'tools_invoked' => 'array',
            'show_all_records' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AssistantConversation::class, 'assistant_conversation_id');
    }
}
