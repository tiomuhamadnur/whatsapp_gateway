<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\WhatsAppSessionController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::get('/wa/sessions', [WhatsAppSessionController::class, 'index']);
    Route::post('/wa/sessions', [WhatsAppSessionController::class, 'store']);
    Route::get('/wa/sessions/{sessionId}/qr', [WhatsAppSessionController::class, 'qr']);
    Route::get('/wa/sessions/{sessionId}/status', [WhatsAppSessionController::class, 'status']);
    Route::get('/wa/sessions/{sessionId}/groups', [WhatsAppSessionController::class, 'groups']);
    Route::get('/wa/sessions/{sessionId}/contacts', [WhatsAppSessionController::class, 'contacts']);
    Route::delete('/wa/sessions/{sessionId}', [WhatsAppSessionController::class, 'destroy']);

    Route::get('/messages', [MessageController::class, 'index']);
    Route::post('/messages/send', [MessageController::class, 'send']);
});
