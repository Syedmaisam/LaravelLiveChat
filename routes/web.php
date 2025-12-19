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
    Route::post('/dashboard/chat/initiate', [DashboardController::class, 'initiateChat'])->name('dashboard.chat.initiate');

    // Chat actions
    Route::post('/dashboard/chat/{chat}/message', [\App\Http\Controllers\Dashboard\ChatController::class, 'sendMessage'])->name('dashboard.chat.message');
    Route::post('/dashboard/chat/{chat}/file', [\App\Http\Controllers\Dashboard\ChatController::class, 'uploadFile'])->name('dashboard.chat.file');
    Route::post('/dashboard/chat/{chat}/typing', [\App\Http\Controllers\Dashboard\ChatController::class, 'typing'])->name('dashboard.chat.typing');
    Route::post('/dashboard/chat/{chat}/join', [\App\Http\Controllers\Dashboard\ChatController::class, 'joinChat'])->name('dashboard.chat.join');
    Route::post('/dashboard/chat/{chat}/close', [\App\Http\Controllers\Dashboard\ChatController::class, 'closeChat'])->name('dashboard.chat.close');
    Route::get('/dashboard/chat/{chat}/messages', [\App\Http\Controllers\Dashboard\ChatController::class, 'getMessages'])->name('dashboard.chat.messages');
    Route::get('/dashboard/message/{message}/download', [\App\Http\Controllers\Dashboard\ChatController::class, 'downloadFile'])->name('dashboard.message.download');
    
    // Reporting
    Route::get('/dashboard/reporting', [\App\Http\Controllers\ReportingController::class, 'index'])->name('dashboard.reporting');
    
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
    });

    // Profile Settings
    Route::get('/profile', [\App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [\App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');

    // Client management
    Route::resource('clients', ClientController::class);
    Route::post('/clients/{client}/assign-agents', [ClientController::class, 'assignAgents'])->name('clients.assign-agents');
});
