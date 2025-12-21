<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Chat extends Model
{
    protected $fillable = [
        'uuid',
        'client_id',
        'visitor_id',
        'visitor_session_id',
        'status',
        'label',
        'last_message_at',
        'unread_count',
        'lead_form_filled',
        'started_at',
        'ended_at',
        'ended_by',
    ];

    protected $casts = [
        'lead_form_filled' => 'boolean',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'last_message_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($chat) {
            if (empty($chat->uuid)) {
                $chat->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName()
    {
        return 'uuid';
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(Visitor::class);
    }

    public function visitorSession(): BelongsTo
    {
        return $this->belongsTo(VisitorSession::class);
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'chat_participants')
            ->withPivot(['joined_at', 'left_at', 'agent_nickname'])
            ->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at');
    }

    public function transfers(): HasMany
    {
        return $this->hasMany(ChatTransfer::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(ChatNote::class)->orderBy('is_pinned', 'desc')->orderBy('created_at', 'desc');
    }

    public function rating()
    {
        return $this->hasOne(ChatRating::class);
    }
}
