<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Visitor extends Model
{
    protected $fillable = [
        'visitor_key',
        'client_id',
        'name',
        'email',
        'phone',
        'ip_address',
        'country',
        'city',
        'device',
        'browser',
        'os',
        'first_visit_at',
        'last_visit_at',
        'total_visits',
    ];

    protected $casts = [
        'first_visit_at' => 'datetime',
        'last_visit_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(VisitorSession::class);
    }

    public function chats(): HasMany
    {
        return $this->hasMany(Chat::class);
    }

    public function currentSession(): HasMany
    {
        return $this->hasMany(VisitorSession::class)->where('is_online', true)->latest();
    }
}
