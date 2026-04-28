<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\InvoiceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| ClientFlow API Routes
|--------------------------------------------------------------------------
|
| All routes are prefixed with /api (configured in bootstrap/app.php).
| Protected routes require a valid Sanctum Bearer token.
|
*/

// ── Public routes (no auth required) ─────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login',    [AuthController::class, 'login']);
});

// ── Protected routes (Sanctum auth required) ──────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::prefix('auth')->group(function () {
        Route::post('logout',         [AuthController::class, 'logout']);
        Route::get('me',              [AuthController::class, 'me']);
        Route::patch('me',            [AuthController::class, 'updateProfile']);
    });

    // Dashboard summary
    Route::get('dashboard',           [InvoiceController::class, 'summary']);

    // Clients
    Route::apiResource('clients', ClientController::class);
    Route::post('clients/{id}/restore', [ClientController::class, 'restore']);

    // Invoices
    Route::apiResource('invoices', InvoiceController::class);
    Route::patch('invoices/{invoice}/status', [InvoiceController::class, 'updateStatus']);
});
