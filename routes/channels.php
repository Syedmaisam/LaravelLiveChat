<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Visitor tracking channels
Broadcast::channel('visitors.{clientId}', function ($user, $clientId) {
    // Agents assigned to this client can listen
    return $user->clients()->where('clients.id', $clientId)->exists();
});

Broadcast::channel('monitoring', function ($user) {
    // All authenticated agents can monitor
    return $user !== null;
});

Broadcast::channel('visitor.{visitorId}', function ($user, $visitorId) {
    // Agents can listen to visitor updates if they have access
    $visitor = \App\Models\Visitor::find($visitorId);
    if (!$visitor) {
        return false;
    }
    return $user->clients()->where('clients.id', $visitor->client_id)->exists();
});

// Chat channels
Broadcast::channel('chat.{chatId}', function ($user, $chatId) {
    $chat = \App\Models\Chat::find($chatId);
    if (!$chat) {
        return false;
    }
    // Agent must be assigned to the client or be a participant
    return $user->clients()->where('clients.id', $chat->client_id)->exists()
        || $chat->participants()->where('users.id', $user->id)->exists();
});

// Agent personal notifications
Broadcast::channel('agent.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});
