<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/demo', function () {
    return view('demo');
});

// Widget embed route
Route::get('/widget.js', function () {
    return response()->file(public_path('widget.js'))
        ->header('Content-Type', 'application/javascript');
});

Route::get('/test-widget', function () {
    return view('test-widget');
});

// Authentication routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// Dashboard routes (protected)
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/monitoring', [DashboardController::class, 'monitoring'])->name('dashboard.monitoring');
    Route::get('/dashboard/chat/{chat}', [DashboardController::class, 'chat'])->name('dashboard.chat');
    Route::get('/inbox/{chat}', [DashboardController::class, 'inbox'])->name('inbox.chat');
    Route::get('/inbox/session/{session}', [DashboardController::class, 'initiateFromSession'])->name('inbox.initiate');
    Route::post('/dashboard/chat/initiate', [DashboardController::class, 'initiateChat'])->name('dashboard.chat.initiate');

    // Chat actions
    Route::post('/dashboard/chat/{chat}/message', [\App\Http\Controllers\Dashboard\ChatController::class, 'sendMessage'])->name('dashboard.chat.message');
    Route::post('/dashboard/chat/{chat}/file', [\App\Http\Controllers\Dashboard\ChatController::class, 'uploadFile'])->name('dashboard.chat.file');
    Route::post('/dashboard/chat/{chat}/typing', [\App\Http\Controllers\Dashboard\ChatController::class, 'typing'])->name('dashboard.chat.typing');
    Route::post('/dashboard/chat/{chat}/join', [\App\Http\Controllers\Dashboard\ChatController::class, 'joinChat'])->name('dashboard.chat.join');
    Route::post('/dashboard/chat/{chat}/close', [\App\Http\Controllers\Dashboard\ChatController::class, 'closeChat'])->name('dashboard.chat.close');
    Route::post('/dashboard/chat/{chat}/label', [\App\Http\Controllers\Dashboard\ChatController::class, 'updateLabel'])->name('dashboard.chat.label');
    Route::get('/dashboard/chat/{chat}/messages', [\App\Http\Controllers\Dashboard\ChatController::class, 'getMessages'])->name('dashboard.chat.messages');
    Route::post('/dashboard/chat/{chat}/read', [DashboardController::class, 'markAsRead'])->name('dashboard.chat.read');
    Route::get('/dashboard/chat/{chat}/file/{message}/download', [\App\Http\Controllers\Dashboard\ChatController::class, 'downloadFile'])->name('dashboard.chat.file.download');
    Route::get('/dashboard/message/{message}/download', [\App\Http\Controllers\Dashboard\ChatController::class, 'downloadFile'])->name('dashboard.message.download');
    
    // Chat Notes
    Route::post('/dashboard/chat/{chat}/notes', [\App\Http\Controllers\Dashboard\ChatController::class, 'addNote'])->name('dashboard.chat.notes.store');
    Route::put('/dashboard/chat/{chat}/notes/{note}', [\App\Http\Controllers\Dashboard\ChatController::class, 'updateNote'])->name('dashboard.chat.notes.update');
    Route::delete('/dashboard/chat/{chat}/notes/{note}', [\App\Http\Controllers\Dashboard\ChatController::class, 'deleteNote'])->name('dashboard.chat.notes.destroy');
    Route::post('/dashboard/chat/{chat}/notes/{note}/pin', [\App\Http\Controllers\Dashboard\ChatController::class, 'togglePinNote'])->name('dashboard.chat.notes.pin');
    
    // Proactive Messages (Agent-initiated)
    Route::post('/dashboard/visitor/{visitor}/proactive', [\App\Http\Controllers\Dashboard\ChatController::class, 'sendProactiveMessage'])->name('dashboard.visitor.proactive');
    
    // Reporting
    Route::get('/dashboard/reporting', [\App\Http\Controllers\ReportingController::class, 'index'])->name('dashboard.reporting');
    
    // Canned Responses
    Route::get('/dashboard/canned-responses', [\App\Http\Controllers\Dashboard\CannedResponseController::class, 'index'])->name('dashboard.canned-responses.index');
    Route::post('/dashboard/canned-responses', [\App\Http\Controllers\Dashboard\CannedResponseController::class, 'store'])->name('dashboard.canned-responses.store');
    Route::put('/dashboard/canned-responses/{cannedResponse}', [\App\Http\Controllers\Dashboard\CannedResponseController::class, 'update'])->name('dashboard.canned-responses.update');
    Route::delete('/dashboard/canned-responses/{cannedResponse}', [\App\Http\Controllers\Dashboard\CannedResponseController::class, 'destroy'])->name('dashboard.canned-responses.destroy');
    Route::get('/api/canned-responses/search', [\App\Http\Controllers\Dashboard\CannedResponseController::class, 'search'])->name('api.canned-responses.search');
    Route::post('/api/canned-responses/{cannedResponse}/use', [\App\Http\Controllers\Dashboard\CannedResponseController::class, 'use'])->name('api.canned-responses.use');
    
    // Admin Routes
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');
        Route::resource('users', \App\Http\Controllers\Admin\UserController::class);
        Route::resource('roles', \App\Http\Controllers\Admin\RoleController::class);
        Route::resource('permissions', \App\Http\Controllers\Admin\PermissionController::class);
        Route::resource('clients', \App\Http\Controllers\Admin\ClientController::class);
        
        // Visitors
        Route::get('/visitors', [\App\Http\Controllers\Admin\VisitorController::class, 'index'])->name('visitors.index');
        Route::get('/visitors/{session}', [\App\Http\Controllers\Admin\VisitorController::class, 'show'])->name('visitors.show');
        
        // Auto-greetings
        Route::get('/auto-greetings', [\App\Http\Controllers\Admin\AutoGreetingController::class, 'index'])->name('auto-greetings.index');
        Route::post('/auto-greetings', [\App\Http\Controllers\Admin\AutoGreetingController::class, 'store'])->name('auto-greetings.store');
        Route::put('/auto-greetings/{autoGreeting}', [\App\Http\Controllers\Admin\AutoGreetingController::class, 'update'])->name('auto-greetings.update');
        Route::delete('/auto-greetings/{autoGreeting}', [\App\Http\Controllers\Admin\AutoGreetingController::class, 'destroy'])->name('auto-greetings.destroy');
        Route::post('/auto-greetings/{autoGreeting}/toggle', [\App\Http\Controllers\Admin\AutoGreetingController::class, 'toggle'])->name('auto-greetings.toggle');
    });

    // Profile Settings
    Route::get('/profile', [\App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [\App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');

    // Client management
    Route::resource('clients', ClientController::class);
    Route::post('/clients/{client}/assign-agents', [ClientController::class, 'assignAgents'])->name('clients.assign-agents');
});
