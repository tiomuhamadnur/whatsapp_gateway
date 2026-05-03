<?php

use App\Http\Controllers\Cms\ApiDocumentationController;
use App\Http\Controllers\Cms\ApiTokenController;
use App\Http\Controllers\Cms\AuthController;
use App\Http\Controllers\Cms\DashboardController;
use App\Http\Controllers\Cms\DirectoryController;
use App\Http\Controllers\Cms\MessageController;
use App\Http\Controllers\Cms\SessionController;
use App\Http\Controllers\Owner\DashboardController as OwnerDashboardController;
use App\Http\Controllers\Owner\ProductPlanController;
use App\Http\Controllers\Owner\SessionMonitorController;
use App\Http\Controllers\Owner\UserController as OwnerUserController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('cms.dashboard');
    }

    return view('cms.auth.login');
});

Route::post('/webhook/whatsapp', [WebhookController::class, 'handle']);

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('cms.login.store');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('cms.register');
    Route::post('/register', [AuthController::class, 'register'])->name('cms.register.store');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])->name('cms.logout');

    Route::get('/dashboard', DashboardController::class)->name('cms.dashboard');
    Route::get('/sessions', [SessionController::class, 'index'])->name('cms.sessions.index');
    Route::get('/sessions/datatable', [SessionController::class, 'datatable'])->name('cms.sessions.datatable');
    Route::post('/sessions', [SessionController::class, 'store'])->name('cms.sessions.store');
    Route::patch('/sessions/{sessionId}', [SessionController::class, 'update'])->name('cms.sessions.update');
    Route::post('/sessions/{sessionId}/disconnect', [SessionController::class, 'disconnect'])->name('cms.sessions.disconnect');
    Route::delete('/sessions/{sessionId}', [SessionController::class, 'destroy'])->name('cms.sessions.destroy');
    Route::get('/sessions/{sessionId}/groups', [DirectoryController::class, 'groups'])->name('cms.sessions.groups');
    Route::get('/sessions/{sessionId}/contacts', [DirectoryController::class, 'contacts'])->name('cms.sessions.contacts');

    Route::get('/messages', [MessageController::class, 'index'])->name('cms.messages.index');
    Route::get('/messages/datatable', [MessageController::class, 'datatable'])->name('cms.messages.datatable');
    Route::post('/messages', [MessageController::class, 'store'])->name('cms.messages.store');

    Route::get('/api-tokens', [ApiTokenController::class, 'index'])->name('cms.tokens.index');
    Route::post('/api-tokens', [ApiTokenController::class, 'store'])->name('cms.tokens.store');
    Route::delete('/api-tokens/{tokenId}', [ApiTokenController::class, 'destroy'])->name('cms.tokens.destroy');

    Route::get('/api-docs', ApiDocumentationController::class)->name('cms.docs.api');

    Route::middleware('owner')->prefix('owner')->name('owner.')->group(function (): void {
        Route::get('/', OwnerDashboardController::class)->name('dashboard');
        Route::get('/users', [OwnerUserController::class, 'index'])->name('users.index');
        Route::patch('/users/{user}', [OwnerUserController::class, 'update'])->name('users.update');
        Route::post('/users/{user}/tokens', [OwnerUserController::class, 'issueToken'])->name('users.tokens.store');

        Route::get('/plans', [ProductPlanController::class, 'index'])->name('plans.index');
        Route::post('/plans', [ProductPlanController::class, 'store'])->name('plans.store');
        Route::patch('/plans/{plan}', [ProductPlanController::class, 'update'])->name('plans.update');
        Route::delete('/plans/{plan}', [ProductPlanController::class, 'destroy'])->name('plans.destroy');

        Route::get('/sessions', [SessionMonitorController::class, 'sessions'])->name('sessions.index');
        Route::get('/messages', [SessionMonitorController::class, 'messages'])->name('messages.index');
    });
});
