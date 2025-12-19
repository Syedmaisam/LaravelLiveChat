<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VisitorSession extends Model
{
    protected $fillable = [
        'visitor_id',
        'client_id',
        'session_key',
        'referrer_url',
        'landing_page',
        'current_page',
        'is_online',
        'started_at',
        'last_activity_at',
    ];

    protected $casts = [
        'is_online' => 'boolean',
        'started_at' => 'datetime',
        'last_activity_at' => 'datetime',
    ];

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(Visitor::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function pageVisits(): HasMany
    {
        return $this->hasMany(VisitorPageVisit::class);
    }

    public function chats(): HasMany
    {
        return $this->hasMany(Chat::class);
    }
}
