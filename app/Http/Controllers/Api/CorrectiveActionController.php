<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\CorrectiveAction;
use App\Models\NonConformance;
use App\Models\Tenant;
use App\Models\Workflow;
use App\Models\WorkflowInstance;
use App\Models\WorkflowTask;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CorrectiveActionController extends Controller
{
    public function index(Tenant $tenant): JsonResponse
    {
        return response()->json([
            'data' => CorrectiveAction::query()
                ->with([
                    'assignee:id,name,email',
                    'verifier:id,name,email',
                    'nonConformance',
                    'risk',
                ])
                ->where('tenant_id', $tenant->id)
                ->orderBy('due_date')
                ->get(),
        ]);
    }

    public function storeNonConformance(Request $request, Tenant $tenant): JsonResponse
    {
        $data = $request->validate([
            'reference' => [
                'required',
                'string',
                'max:255',
                Rule::unique('non_conformances')->where('tenant_id', $tenant->id),
            ],
            'source' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'iso_clause' => ['nullable', 'string', 'max:255'],
            'severity' => ['required', 'string', 'max:255'],
            'status' => ['sometimes', 'string', 'max:255'],
            'detected_at' => ['required', 'date'],
            'owner_id' => ['nullable', Rule::exists('users', 'id')->where('tenant_id', $tenant->id)],
            'root_cause' => ['nullable', 'string'],
        ]);

        $nonConformance = NonConformance::create([
            ...$data,
            'tenant_id' => $tenant->id,
            'status' => $data['status'] ?? 'Open',
        ]);

        AuditLog::appendFor(
            $tenant->id,
            $request->user()->id,
            'non_conformance.created',
            NonConformance::class,
            $nonConformance->id,
            [],
            $nonConformance->toArray(),
            $request->ip(),
            $request->userAgent(),
        );

        return response()->json(['data' => $nonConformance->load('owner:id,name,email')], 201);
    }

    public function store(Request $request, Tenant $tenant): JsonResponse
    {
        $data = $request->validate([
            'non_conformance_id' => ['nullable', Rule::exists('non_conformances', 'id')->where('tenant_id', $tenant->id)],
            'risk_id' => ['nullable', Rule::exists('risks', 'id')->where('tenant_id', $tenant->id)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'assigned_to_id' => ['nullable', Rule::exists('users', 'id')->where('tenant_id', $tenant->id)],
            'verified_by_id' => ['nullable', Rule::exists('users', 'id')->where('tenant_id', $tenant->id)],
            'due_date' => ['nullable', 'date'],
            'status' => ['sometimes', 'string', 'max:255'],
        ]);

        $action = DB::transaction(function () use ($data, $request, $tenant): CorrectiveAction {
            $action = CorrectiveAction::create([
                ...$data,
                'tenant_id' => $tenant->id,
                'status' => $data['status'] ?? 'Open',
            ]);

            $workflow = Workflow::query()
                ->where('tenant_id', $tenant->id)
                ->where('model_type', CorrectiveAction::class)
                ->where('is_active', true)
                ->first();

            if ($workflow) {
                $instance = WorkflowInstance::create([
                    'tenant_id' => $tenant->id,
                    'workflow_id' => $workflow->id,
                    'model_type' => CorrectiveAction::class,
                    'model_id' => $action->id,
                    'current_state' => 'open',
                    'status' => 'Open',
                    'started_by_id' => $request->user()->id,
                ]);

                WorkflowTask::create([
                    'workflow_instance_id' => $instance->id,
                    'assigned_to_id' => $action->assigned_to_id,
                    'title' => 'Investigate root cause and plan corrective action',
                    'state' => 'investigation',
                    'status' => 'Open',
                    'due_at' => $action->due_date,
                ]);
            }

            AuditLog::appendFor(
                $tenant->id,
                $request->user()->id,
                'capa.created',
                CorrectiveAction::class,
                $action->id,
                [],
                $action->toArray(),
                $request->ip(),
                $request->userAgent(),
            );

            return $action;
        });

        return response()->json([
            'data' => $action->load([
                'assignee:id,name,email',
                'verifier:id,name,email',
                'nonConformance',
                'risk',
            ]),
        ], 201);
    }

    public function update(Request $request, Tenant $tenant, CorrectiveAction $correctiveAction): JsonResponse
    {
        abort_unless((int) $correctiveAction->tenant_id === (int) $tenant->id, 404);

        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
            'assigned_to_id' => ['nullable', Rule::exists('users', 'id')->where('tenant_id', $tenant->id)],
            'verified_by_id' => ['nullable', Rule::exists('users', 'id')->where('tenant_id', $tenant->id)],
            'due_date' => ['nullable', 'date'],
            'completed_at' => ['nullable', 'date'],
            'effectiveness_verified_at' => ['nullable', 'date'],
            'status' => ['sometimes', 'string', 'max:255'],
        ]);

        if (isset($data['status']) && in_array($data['status'], ['Closed', 'Verified'], true)) {
            abort_unless($request->user()->hasPermission('capa.close'), 403, 'Permission denied.');
        }

        $oldValues = $correctiveAction->toArray();
        $correctiveAction->fill($data)->save();

        AuditLog::appendFor(
            $tenant->id,
            $request->user()->id,
            'capa.updated',
            CorrectiveAction::class,
            $correctiveAction->id,
            $oldValues,
            $correctiveAction->fresh()->toArray(),
            $request->ip(),
            $request->userAgent(),
        );

        return response()->json([
            'data' => $correctiveAction->fresh([
                'assignee:id,name,email',
                'verifier:id,name,email',
                'nonConformance',
                'risk',
            ]),
        ]);
    }
}
