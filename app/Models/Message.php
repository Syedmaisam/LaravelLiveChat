<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'chat_id',
        'sender_type',
        'sender_id',
        'message_type',
        'message',
        'file_path',
        'file_name',
        'file_size',
        'file_type',
        'is_read',
        'read_at',
        'deleted_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'deleted_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class);
    }

    public function sender()
    {
        if ($this->sender_type === 'agent') {
            return $this->belongsTo(User::class, 'sender_id');
        }
        return $this->belongsTo(Visitor::class, 'sender_id');
    }
}
