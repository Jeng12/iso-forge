<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\IsoForgeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::get('/user', function (Request $request) {
        return $request->user()->load(['tenant', 'roles.permissions']);
    });

    Route::get('/tenants/{tenant:slug}/snapshot', [IsoForgeController::class, 'snapshot']);
    Route::get('/tenants/{tenant:slug}/documents', [IsoForgeController::class, 'documents']);
    Route::get('/tenants/{tenant:slug}/risks', [IsoForgeController::class, 'risks']);
    Route::get('/tenants/{tenant:slug}/corrective-actions', [IsoForgeController::class, 'correctiveActions']);
    Route::get('/tenants/{tenant:slug}/audit-logs', [IsoForgeController::class, 'auditLogs']);
    Route::post('/tenants/{tenant:slug}/audit-logs', [IsoForgeController::class, 'storeAuditLog']);
});
