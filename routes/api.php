<?php

use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\VisitorTrackingController;
use App\Http\Controllers\Api\WidgetController;
use Illuminate\Support\Facades\Route;

// Widget configuration
Route::get('/widget/config', [WidgetController::class, 'config']);

// Visitor tracking endpoints (public, authenticated by widget_key)
Route::prefix('visitor')->group(function () {
    Route::post('/track', [VisitorTrackingController::class, 'track']);
    Route::post('/session', [VisitorTrackingController::class, 'createSession']);
    Route::get('/status', [VisitorTrackingController::class, 'getStatus']);
    Route::post('/page-visit', [VisitorTrackingController::class, 'trackPageVisit']);
    Route::post('/heartbeat', [VisitorTrackingController::class, 'heartbeat']);
    Route::post('/offline', [VisitorTrackingController::class, 'markOffline']);
});

// Chat endpoints (public, authenticated by widget_key)
Route::prefix('chat')->group(function () {
    Route::post('/create', [ChatController::class, 'create']);
    Route::post('/{chat}/message', [ChatController::class, 'sendMessage']);
    Route::post('/{chat}/typing', [ChatController::class, 'typing']);
    Route::get('/{chat}/messages', [ChatController::class, 'getMessages']);
    Route::post('/{chat}/file', [ChatController::class, 'uploadFile']);
    Route::post('/{chat}/read', [ChatController::class, 'markAsRead']);
    Route::post('/{chat}/rate', [ChatController::class, 'rateChat']);
});

// Agent API endpoints (authenticated via session/CSRF)
Route::prefix('agent')->middleware(['web', 'auth'])->group(function () {
    Route::post('/chat/{chat}/message', [ChatController::class, 'agentSendMessage']);
    Route::post('/chat/{chat}/typing', [ChatController::class, 'agentTyping']);
});
