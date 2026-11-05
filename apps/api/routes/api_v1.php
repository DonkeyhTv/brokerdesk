<?php

use App\Http\Controllers\Api\V1\Auth\CurrentUserController;
use App\Http\Controllers\Api\V1\Auth\SessionController;
use App\Http\Controllers\Api\V1\Auth\TokenController;
use App\Http\Controllers\Api\V1\ClaimController;
use App\Http\Controllers\Api\V1\ClientController;
use App\Http\Controllers\Api\V1\ContractController;
use App\Http\Controllers\Api\V1\DemoAccountController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\InsurerController;
use App\Http\Controllers\Api\V1\UserController;
use App\Models\Claim;
use App\Models\Client;
use App\Models\Contract;
use App\Models\User;
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

    Route::get('/insurers', InsurerController::class);

    // Create and update are authorized by their form requests.
    Route::get('/clients', [ClientController::class, 'index'])->can('viewAny', Client::class);
    Route::post('/clients', [ClientController::class, 'store']);
    Route::get('/clients/{client}', [ClientController::class, 'show'])->can('view', 'client');
    Route::put('/clients/{client}', [ClientController::class, 'update']);
    Route::delete('/clients/{client}', [ClientController::class, 'destroy'])->can('delete', 'client');

    Route::get('/contracts', [ContractController::class, 'index'])->can('viewAny', Contract::class);
    Route::post('/contracts', [ContractController::class, 'store']);
    Route::get('/contracts/{contract}', [ContractController::class, 'show'])->can('view', 'contract');
    Route::put('/contracts/{contract}', [ContractController::class, 'update']);
    Route::delete('/contracts/{contract}', [ContractController::class, 'destroy'])->can('delete', 'contract');

    Route::get('/claims', [ClaimController::class, 'index'])->can('viewAny', Claim::class);
    Route::post('/claims', [ClaimController::class, 'store']);
    Route::get('/claims/{claim}', [ClaimController::class, 'show'])->can('view', 'claim');
    Route::put('/claims/{claim}', [ClaimController::class, 'update']);
    Route::delete('/claims/{claim}', [ClaimController::class, 'destroy'])->can('delete', 'claim');

    Route::get('/users', [UserController::class, 'index'])->can('viewAny', User::class);
    Route::post('/users', [UserController::class, 'store']);
    Route::get('/users/{user}', [UserController::class, 'show'])->can('view', 'user');
    Route::put('/users/{user}', [UserController::class, 'update']);
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->can('delete', 'user');
});
