<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CorrectiveActionController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\IsoForgeController;
use App\Http\Controllers\Api\RiskController;
use App\Http\Controllers\Api\WorkflowTaskController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::get('/user', function (Request $request) {
        return $request->user()->load(['tenant', 'roles.permissions']);
    });

    Route::prefix('/tenants/{tenant:slug}')
        ->middleware('tenant')
        ->group(function (): void {
            Route::get('/snapshot', [IsoForgeController::class, 'snapshot']);
            Route::get('/users', [IsoForgeController::class, 'users']);

            Route::get('/documents', [DocumentController::class, 'index'])->middleware('permission:document.view');
            Route::post('/documents', [DocumentController::class, 'store'])->middleware('permission:document.create');
            Route::post('/documents/{document}/approvals', [DocumentController::class, 'requestApprovals'])->middleware('permission:document.create');
            Route::get('/document-approvals', [DocumentController::class, 'approvals'])->middleware('permission:document.view');
            Route::post('/document-approvals/{documentApproval}/approve', [DocumentController::class, 'approve'])->middleware('permission:document.approve');

            Route::get('/risks', [RiskController::class, 'index'])->middleware('permission:risk.manage');
            Route::post('/risks', [RiskController::class, 'store'])->middleware('permission:risk.manage');
            Route::patch('/risks/{risk}', [RiskController::class, 'update'])->middleware('permission:risk.manage');

            Route::get('/corrective-actions', [CorrectiveActionController::class, 'index'])->middleware('permission:capa.create,capa.close');
            Route::post('/non-conformances', [CorrectiveActionController::class, 'storeNonConformance'])->middleware('permission:capa.create');
            Route::post('/corrective-actions', [CorrectiveActionController::class, 'store'])->middleware('permission:capa.create');
            Route::patch('/corrective-actions/{correctiveAction}', [CorrectiveActionController::class, 'update'])->middleware('permission:capa.create,capa.close');
            Route::get('/workflow-tasks', [WorkflowTaskController::class, 'index'])->middleware('permission:capa.create,capa.close');
            Route::post('/workflow-tasks/{workflowTask}/complete', [WorkflowTaskController::class, 'complete']);

            Route::get('/audit-logs', [IsoForgeController::class, 'auditLogs'])->middleware('permission:audit.view');
            Route::post('/audit-logs', [IsoForgeController::class, 'storeAuditLog'])->middleware('permission:audit.view');
        });
});
