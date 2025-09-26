<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{
    protected $fillable = [
        'sender_id',
        'receiver_id',
        'subject',
        'body',
        'read_at',
        'deleted_by_sender',
        'deleted_by_receiver',
        'parent_id',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'deleted_by_sender' => 'boolean',
        'deleted_by_receiver' => 'boolean',
    ];

    /**
     * Get the sender of the message.
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Get the receiver of the message.
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    /**
     * Get the parent message (for threading).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'parent_id');
    }

    /**
     * Get the replies to this message.
     */
    public function replies(): HasMany
    {
        return $this->hasMany(Message::class, 'parent_id')->orderBy('created_at', 'asc');
    }

    /**
     * Get the attachments for this message.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(MessageAttachment::class);
    }

    /**
     * Scope to get unread messages for a user.
     */
    public function scopeUnread($query, $userId)
    {
        return $query->where('receiver_id', $userId)->whereNull('read_at');
    }

    /**
     * Scope to get messages not deleted by sender.
     */
    public function scopeNotDeletedBySender($query)
    {
        return $query->where('deleted_by_sender', false);
    }

    /**
     * Scope to get messages not deleted by receiver.
     */
    public function scopeNotDeletedByReceiver($query)
    {
        return $query->where('deleted_by_receiver', false);
    }

    /**
     * Mark message as read.
     */
    public function markAsRead()
    {
        if (is_null($this->read_at)) {
            $this->update(['read_at' => now()]);
        }
    }

    /**
     * Check if message is read.
     */
    public function isRead(): bool
    {
        return !is_null($this->read_at);
    }

    /**
     * Check if message is a reply.
     */
    public function isReply(): bool
    {
        return !is_null($this->parent_id);
    }
}
