<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'nik',
        'username',
        'email',
        'password',
        'project',
        'department_id',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the department that owns the user.
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the project that owns the user.
     */
    public function projectInfo()
    {
        return $this->belongsTo(Project::class, 'project', 'code');
    }

    /**
     * Get the user's department's location code.
     */
    public function getDepartmentLocationCodeAttribute()
    {
        return $this->department ? $this->department->location_code : null;
    }

    /**
     * Scope a query to only include active users.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the messages sent by this user.
     */
    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    /**
     * Get the messages received by this user.
     */
    public function receivedMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'receiver_id');
    }

    /**
     * Get unread messages count for this user.
     */
    public function getUnreadMessagesCountAttribute(): int
    {
        return $this->receivedMessages()->unread($this->id)->notDeletedByReceiver()->count();
    }

    /**
     * Get recent messages for this user (both sent and received).
     */
    public function getRecentMessages($limit = 10)
    {
        return Message::where(function ($query) {
            $query->where('sender_id', $this->id)
                ->orWhere('receiver_id', $this->id);
        })
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q->where('sender_id', $this->id)
                        ->where('deleted_by_sender', false);
                })
                    ->orWhere(function ($q) {
                        $q->where('receiver_id', $this->id)
                            ->where('deleted_by_receiver', false);
                    });
            })
            ->with(['sender', 'receiver'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
