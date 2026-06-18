<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\CorrectiveAction;
use App\Models\Tenant;
use App\Models\WorkflowTask;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkflowTaskController extends Controller
{
    public function index(Tenant $tenant): JsonResponse
    {
        return response()->json([
            'data' => WorkflowTask::query()
                ->with(['assignee:id,name,email', 'workflowInstance.workflow'])
                ->whereHas('workflowInstance', fn ($query) => $query->where('tenant_id', $tenant->id))
                ->orderByRaw("case when status = 'Open' then 0 when status = 'Waiting' then 1 else 2 end")
                ->orderBy('due_at')
                ->get(),
        ]);
    }

    public function complete(Request $request, Tenant $tenant, WorkflowTask $workflowTask): JsonResponse
    {
        $data = $request->validate([
            'comments' => ['nullable', 'string'],
        ]);

        $workflowTask->load('workflowInstance');
        $instance = $workflowTask->workflowInstance;

        abort_unless((int) $instance->tenant_id === (int) $tenant->id, 404);
        abort_unless(
            (int) $workflowTask->assigned_to_id === (int) $request->user()->id || $request->user()->hasPermission('capa.close'),
            403,
            'Permission denied.',
        );

        DB::transaction(function () use ($data, $instance, $request, $tenant, $workflowTask): void {
            $oldValues = $workflowTask->toArray();

            $workflowTask->update([
                'status' => 'Completed',
                'comments' => $data['comments'] ?? $workflowTask->comments,
                'completed_at' => now(),
            ]);

            $instance->update([
                'current_state' => $workflowTask->state,
            ]);

            if ($workflowTask->state === 'verification') {
                $instance->update([
                    'current_state' => 'closed',
                    'status' => 'Closed',
                    'completed_at' => now(),
                ]);

                if ($instance->model_type === CorrectiveAction::class && $instance->model_id) {
                    CorrectiveAction::query()
                        ->where('tenant_id', $tenant->id)
                        ->where('id', $instance->model_id)
                        ->update([
                            'status' => 'Verified',
                            'effectiveness_verified_at' => now(),
                        ]);
                }
            }

            AuditLog::appendFor(
                $tenant->id,
                $request->user()->id,
                'workflow_task.completed',
                WorkflowTask::class,
                $workflowTask->id,
                $oldValues,
                $workflowTask->fresh()->toArray(),
                $request->ip(),
                $request->userAgent(),
            );
        });

        return response()->json([
            'data' => $workflowTask->fresh(['assignee:id,name,email', 'workflowInstance.workflow']),
        ]);
    }
}
