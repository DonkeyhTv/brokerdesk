<?php

use App\Http\Controllers\Api\V1\Auth\CurrentUserController;
use App\Http\Controllers\Api\V1\Auth\SessionController;
use App\Http\Controllers\Api\V1\Auth\TokenController;
use App\Http\Controllers\Api\V1\DemoAccountController;
use App\Http\Controllers\Api\V1\HealthController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);
Route::get('/demo/accounts', DemoAccountController::class);

Route::middleware('throttle:login')->group(function () {
    Route::post('/auth/session', [SessionController::class, 'store']);
    Route::post('/auth/tokens', [TokenController::class, 'store']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', CurrentUserController::class);
    Route::delete('/auth/session', [SessionController::class, 'destroy']);
    Route::delete('/auth/tokens/current', [TokenController::class, 'destroy']);
});
