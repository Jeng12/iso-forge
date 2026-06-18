<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Risk;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RiskController extends Controller
{
    public function index(Tenant $tenant): JsonResponse
    {
        return response()->json([
            'data' => Risk::query()
                ->with('owner:id,name,email')
                ->where('tenant_id', $tenant->id)
                ->orderByDesc('risk_score')
                ->get(),
        ]);
    }

    public function store(Request $request, Tenant $tenant): JsonResponse
    {
        $data = $this->validatedRisk($request, $tenant);

        $risk = Risk::create([
            ...$data,
            'tenant_id' => $tenant->id,
        ]);

        AuditLog::appendFor(
            $tenant->id,
            $request->user()->id,
            'risk.created',
            Risk::class,
            $risk->id,
            [],
            $risk->toArray(),
            $request->ip(),
            $request->userAgent(),
        );

        return response()->json(['data' => $risk->load('owner:id,name,email')], 201);
    }

    public function update(Request $request, Tenant $tenant, Risk $risk): JsonResponse
    {
        abort_unless((int) $risk->tenant_id === (int) $tenant->id, 404);

        $data = $this->validatedRisk($request, $tenant, partial: true);
        $oldValues = $risk->toArray();

        $risk->fill($data)->save();

        AuditLog::appendFor(
            $tenant->id,
            $request->user()->id,
            'risk.updated',
            Risk::class,
            $risk->id,
            $oldValues,
            $risk->fresh()->toArray(),
            $request->ip(),
            $request->userAgent(),
        );

        return response()->json(['data' => $risk->fresh('owner:id,name,email')]);
    }

    private function validatedRisk(Request $request, Tenant $tenant, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'title' => [$required, 'string', 'max:255'],
            'category' => ['sometimes', 'string', 'max:255'],
            'likelihood' => [$required, 'integer', 'min:1', 'max:5'],
            'severity' => [$required, 'integer', 'min:1', 'max:5'],
            'residual_likelihood' => ['nullable', 'integer', 'min:1', 'max:5'],
            'residual_severity' => ['nullable', 'integer', 'min:1', 'max:5'],
            'owner_id' => ['nullable', Rule::exists('users', 'id')->where('tenant_id', $tenant->id)],
            'treatment_plan' => ['nullable', 'string'],
            'status' => ['sometimes', 'string', 'max:255'],
        ]);
    }
}
