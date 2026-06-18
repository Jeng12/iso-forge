<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CorrectiveActionController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\FsmsController;
use App\Http\Controllers\Api\IsoForgeController;
use App\Http\Controllers\Api\QmsController;
use App\Http\Controllers\Api\RiskController;
use App\Http\Controllers\Api\SupplierQualityController;
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

            Route::get('/qms', [QmsController::class, 'overview'])->middleware('permission:qms.view,qms.manage');
            Route::post('/qms/objectives', [QmsController::class, 'storeObjective'])->middleware('permission:qms.manage');
            Route::patch('/qms/objectives/{qualityObjective}', [QmsController::class, 'updateObjective'])->middleware('permission:qms.manage');
            Route::post('/qms/audits', [QmsController::class, 'storeAudit'])->middleware('permission:qms.manage');
            Route::patch('/qms/audits/{audit}', [QmsController::class, 'updateAudit'])->middleware('permission:qms.manage');
            Route::post('/qms/audits/{audit}/findings', [QmsController::class, 'storeFinding'])->middleware('permission:qms.manage');
            Route::post('/qms/management-reviews', [QmsController::class, 'storeManagementReview'])->middleware('permission:qms.manage');

            Route::get('/fsms', [FsmsController::class, 'overview'])->middleware('permission:fsms.view,fsms.manage');
            Route::post('/fsms/haccp-plans', [FsmsController::class, 'storePlan'])->middleware('permission:fsms.manage');
            Route::post('/fsms/haccp-plans/{haccpPlan}/steps', [FsmsController::class, 'storeStep'])->middleware('permission:fsms.manage');
            Route::post('/fsms/process-steps/{processStep}/hazards', [FsmsController::class, 'storeHazard'])->middleware('permission:fsms.manage');
            Route::post('/fsms/hazards/{hazardAnalysis}/ccps', [FsmsController::class, 'storeCcp'])->middleware('permission:fsms.manage');
            Route::post('/fsms/hazards/{hazardAnalysis}/oprps', [FsmsController::class, 'storeOprp'])->middleware('permission:fsms.manage');
            Route::post('/fsms/prps', [FsmsController::class, 'storePrp'])->middleware('permission:fsms.manage');
            Route::post('/fsms/monitoring-records', [FsmsController::class, 'storeMonitoringRecord'])->middleware('permission:fsms.manage');

            Route::get('/supplier-quality', [SupplierQualityController::class, 'overview'])->middleware('permission:supplier.view,supplier.manage');
            Route::post('/supplier-quality/suppliers', [SupplierQualityController::class, 'storeSupplier'])->middleware('permission:supplier.manage');
            Route::post('/supplier-quality/suppliers/{supplier}/evaluations', [SupplierQualityController::class, 'storeEvaluation'])->middleware('permission:supplier.manage');
            Route::post('/supplier-quality/suppliers/{supplier}/certificates', [SupplierQualityController::class, 'storeCertificate'])->middleware('permission:supplier.manage');
            Route::post('/supplier-quality/equipment', [SupplierQualityController::class, 'storeEquipment'])->middleware('permission:supplier.manage');
            Route::post('/supplier-quality/equipment/{equipmentAsset}/calibrations', [SupplierQualityController::class, 'storeCalibration'])->middleware('permission:supplier.manage');

            Route::get('/audit-logs', [IsoForgeController::class, 'auditLogs'])->middleware('permission:audit.view');
            Route::post('/audit-logs', [IsoForgeController::class, 'storeAuditLog'])->middleware('permission:audit.view');
        });
});
