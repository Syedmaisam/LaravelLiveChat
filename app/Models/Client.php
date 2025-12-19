<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    protected $fillable = [
        'name',
        'domain',
        'widget_key',
        'logo',
        'widget_settings',
        'is_active',
    ];

    protected $casts = [
        'widget_settings' => 'array',
        'is_active' => 'boolean',
    ];

    public function agents(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'client_agent')
            ->withPivot('assigned_at');
    }

    public function visitors(): HasMany
    {
        return $this->hasMany(Visitor::class);
    }

    public function chats(): HasMany
    {
        return $this->hasMany(Chat::class);
    }

    public function visitorSessions(): HasMany
    {
        return $this->hasMany(VisitorSession::class);
    }
}
