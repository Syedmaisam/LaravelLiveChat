<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CannedResponse extends Model
{
    protected $fillable = [
        'user_id',
        'client_id',
        'shortcut',
        'title',
        'content',
        'category',
        'is_global',
        'usage_count',
    ];

    protected $casts = [
        'is_global' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get canned responses available to a user
     */
    public static function getForUser(User $user, ?int $clientId = null)
    {
        return static::where(function ($q) use ($user, $clientId) {
            // User's own responses
            $q->where('user_id', $user->id);
        })
        ->orWhere('is_global', true)
        ->when($clientId, function ($q, $clientId) {
            $q->where(function ($q2) use ($clientId) {
                $q2->whereNull('client_id')
                   ->orWhere('client_id', $clientId);
            });
        })
        ->orderBy('usage_count', 'desc')
        ->get();
    }

    /**
     * Increment usage count
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
    }
}
