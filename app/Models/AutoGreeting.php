<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutoGreeting extends Model
{
    protected $fillable = [
        'client_id',
        'name',
        'message',
        'trigger_type',
        'trigger_conditions',
        'is_active',
        'delay_seconds',
        'cooldown_hours',
        'priority',
        'shown_count',
        'clicked_count',
    ];

    protected $casts = [
        'trigger_conditions' => 'array',
        'is_active' => 'boolean',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get active auto-greetings for a client
     */
    public static function getForClient(int $clientId)
    {
        return static::where('client_id', $clientId)
            ->where('is_active', true)
            ->orderBy('priority', 'desc')
            ->get();
    }

    /**
     * Increment shown count
     */
    public function incrementShown(): void
    {
        $this->increment('shown_count');
    }

    /**
     * Increment clicked count
     */
    public function incrementClicked(): void
    {
        $this->increment('clicked_count');
    }

    /**
     * Get conversion rate percentage
     */
    public function getConversionRateAttribute(): float
    {
        if ($this->shown_count === 0) return 0;
        return round(($this->clicked_count / $this->shown_count) * 100, 1);
    }
}
