<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\CorrectiveAction;
use App\Models\Document;
use App\Models\DocumentApproval;
use App\Models\Risk;
use App\Models\Tenant;
use App\Models\WorkflowTask;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $tenant = Tenant::query()->where('slug', 'angkor-quality-foods')->first() ?? Tenant::query()->first();

        if (! $tenant) {
            return view('dashboard', [
                'tenant' => null,
                'metrics' => [],
                'documents' => collect(),
                'pendingApprovals' => collect(),
                'workflowTasks' => collect(),
                'risks' => collect(),
                'correctiveActions' => collect(),
                'auditLogs' => collect(),
            ]);
        }

        $pendingApprovals = DocumentApproval::query()
            ->with(['approver', 'documentVersion.document'])
            ->where('status', 'Pending')
            ->whereHas('documentVersion.document', fn ($query) => $query->where('tenant_id', $tenant->id))
            ->latest()
            ->get();

        $workflowTasks = WorkflowTask::query()
            ->with(['assignee', 'workflowInstance.workflow'])
            ->whereHas('workflowInstance', fn ($query) => $query->where('tenant_id', $tenant->id))
            ->orderByRaw("case when status = 'Open' then 0 when status = 'Waiting' then 1 else 2 end")
            ->orderBy('due_at')
            ->get();

        $metrics = [
            'documents' => $tenant->documents()->count(),
            'pending_approvals' => $pendingApprovals->count(),
            'high_risks' => $tenant->risks()->where('risk_score', '>=', 15)->count(),
            'open_capas' => $tenant->correctiveActions()->whereNotIn('status', ['Closed', 'Verified'])->count(),
        ];

        return view('dashboard', [
            'tenant' => $tenant,
            'metrics' => $metrics,
            'documents' => Document::query()
                ->with(['owner', 'currentVersion'])
                ->where('tenant_id', $tenant->id)
                ->latest()
                ->get(),
            'pendingApprovals' => $pendingApprovals,
            'workflowTasks' => $workflowTasks,
            'risks' => Risk::query()
                ->with('owner')
                ->where('tenant_id', $tenant->id)
                ->orderByDesc('risk_score')
                ->get(),
            'correctiveActions' => CorrectiveAction::query()
                ->with(['assignee', 'verifier', 'nonConformance', 'risk'])
                ->where('tenant_id', $tenant->id)
                ->orderBy('due_date')
                ->get(),
            'auditLogs' => AuditLog::query()
                ->with('user')
                ->where('tenant_id', $tenant->id)
                ->latest('occurred_at')
                ->limit(8)
                ->get(),
        ]);
    }
}
