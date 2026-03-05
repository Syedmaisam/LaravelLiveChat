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
        // Widget Appearance
        'widget_color',
        'widget_icon',
        'widget_icon_url',
        'widget_position',
        // Welcome Message
        'widget_welcome_title',
        'widget_welcome_message',
        'widget_agent_name',
        'widget_agent_avatar',
        // Behavior
        'widget_show_branding',
        'widget_auto_open',
        'widget_auto_open_delay',
    ];

    protected $casts = [
        'widget_settings' => 'array',
        'is_active' => 'boolean',
        'widget_show_branding' => 'boolean',
        'widget_auto_open' => 'boolean',
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
