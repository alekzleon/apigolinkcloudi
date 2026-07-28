<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\LinkAnalyticsController;
use App\Http\Controllers\Api\LinkController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class)->middleware('throttle:api');

Route::prefix('auth')->group(function (): void {
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:auth.register');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:auth.login');

    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function (): void {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

Route::middleware(['auth:sanctum', 'throttle:api'])->group(function (): void {
    Route::get('/links', [LinkController::class, 'index']);
    Route::post('/links', [LinkController::class, 'store'])->middleware('throttle:links.create');
    Route::get('/links/{link}/analytics', LinkAnalyticsController::class);
    Route::get('/links/{link}', [LinkController::class, 'show']);
    Route::put('/links/{link}', [LinkController::class, 'update']);
    Route::patch('/links/{link}', [LinkController::class, 'update']);
    Route::patch('/links/{link}/status', [LinkController::class, 'updateStatus']);
    Route::delete('/links/{link}', [LinkController::class, 'destroy']);
});
