<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Audit;
use App\Models\AuditFinding;
use App\Models\AuditLog;
use App\Models\CalibrationRecord;
use App\Models\CorrectiveAction;
use App\Models\EmergencyDrill;
use App\Models\IncidentReport;
use App\Models\ManagementReview;
use App\Models\QualityObjective;
use App\Models\Supplier;
use App\Models\SupplierCertificate;
use App\Models\SupplierEvaluation;
use App\Models\Tenant;
use App\Models\TrainingAssignment;
use App\Models\TrainingProgram;
use App\Models\TrainingRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

class ManagementReviewPacketController extends Controller
{
    public function index(Tenant $tenant): JsonResponse
    {
        $summary = $this->evidenceSummary($tenant);

        return response()->json([
            'data' => [
                'generated_at' => now()->toISOString(),
                'evidence_summary' => $summary,
                'packets' => ManagementReview::query()
                    ->with('chair:id,name,email')
                    ->where('tenant_id', $tenant->id)
                    ->orderByDesc('review_date')
                    ->get()
                    ->map(fn (ManagementReview $review): array => [
                        'id' => $review->id,
                        'packet_id' => $this->packetId($tenant, $review),
                        'title' => $review->title,
                        'review_date' => $review->review_date?->toDateString(),
                        'status' => $review->status,
                        'chair' => $review->chair,
                        'evidence_summary' => $summary,
                    ])
                    ->values(),
            ],
        ]);
    }

    public function show(Tenant $tenant, ManagementReview $managementReview): JsonResponse
    {
        abort_unless((int) $managementReview->tenant_id === (int) $tenant->id, 404);

        return response()->json(['data' => $this->buildPacket($tenant, $managementReview)]);
    }

    public function download(Tenant $tenant, ManagementReview $managementReview): JsonResponse
    {
        abort_unless((int) $managementReview->tenant_id === (int) $tenant->id, 404);

        $packet = $this->buildPacket($tenant, $managementReview);
        $filename = (string) str($packet['packet_id'])->lower()->replace([' ', '/'], '-')->append('.json');

        return response()
            ->json(['data' => $packet])
            ->withHeaders([
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ]);
    }

    private function buildPacket(Tenant $tenant, ManagementReview $review): array
    {
        $review->load('chair:id,name,email');

        $packet = [
            'format_version' => '1.0',
            'packet_id' => $this->packetId($tenant, $review),
            'generated_at' => now()->toISOString(),
            'tenant' => $tenant->only(['id', 'name', 'slug', 'industry']),
            'management_review' => [
                'id' => $review->id,
                'title' => $review->title,
                'review_date' => $review->review_date?->toDateString(),
                'chair' => $review->chair,
                'status' => $review->status,
                'inputs' => $review->inputs ?? [],
                'decisions' => $review->decisions ?? [],
                'actions' => $review->actions ?? [],
            ],
            'evidence_summary' => $this->evidenceSummary($tenant),
            'qms' => $this->qmsEvidence($tenant),
            'training' => $this->trainingEvidence($tenant),
            'incident_response' => $this->incidentEvidence($tenant),
            'supplier_quality' => $this->supplierEvidence($tenant),
            'capa' => $this->capaEvidence($tenant),
            'audit_chain' => $this->auditEvidence($tenant),
        ];

        $packet['packet_hash'] = hash('sha256', json_encode($packet, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION));

        return $packet;
    }

    private function evidenceSummary(Tenant $tenant): array
    {
        return [
            'qms' => [
                'objectives' => QualityObjective::query()->where('tenant_id', $tenant->id)->count(),
                'audits' => Audit::query()->where('tenant_id', $tenant->id)->count(),
                'open_findings' => AuditFinding::query()->where('tenant_id', $tenant->id)->whereNotIn('status', ['Closed', 'Verified'])->count(),
                'management_reviews' => ManagementReview::query()->where('tenant_id', $tenant->id)->count(),
            ],
            'training' => [
                'programs' => TrainingProgram::query()->where('tenant_id', $tenant->id)->count(),
                'assignments' => TrainingAssignment::query()->where('tenant_id', $tenant->id)->count(),
                'records' => TrainingRecord::query()->where('tenant_id', $tenant->id)->count(),
            ],
            'incident_response' => [
                'reports' => IncidentReport::query()->where('tenant_id', $tenant->id)->count(),
                'open_reports' => IncidentReport::query()->where('tenant_id', $tenant->id)->whereNotIn('status', ['Closed', 'Contained'])->count(),
                'emergency_drills' => EmergencyDrill::query()->where('tenant_id', $tenant->id)->count(),
            ],
            'supplier_quality' => [
                'suppliers' => Supplier::query()->where('tenant_id', $tenant->id)->count(),
                'evaluations' => SupplierEvaluation::query()->where('tenant_id', $tenant->id)->count(),
                'certificates' => SupplierCertificate::query()->where('tenant_id', $tenant->id)->count(),
                'calibration_records' => CalibrationRecord::query()->where('tenant_id', $tenant->id)->count(),
            ],
            'capa' => [
                'open_actions' => CorrectiveAction::query()->where('tenant_id', $tenant->id)->whereNotIn('status', ['Closed', 'Verified'])->count(),
            ],
            'audit_chain' => [
                'events' => AuditLog::query()->where('tenant_id', $tenant->id)->count(),
                'latest_hash' => AuditLog::query()->where('tenant_id', $tenant->id)->latest('id')->value('entry_hash'),
            ],
        ];
    }

    private function qmsEvidence(Tenant $tenant): array
    {
        return [
            'objectives' => QualityObjective::query()
                ->with('owner:id,name,email')
                ->where('tenant_id', $tenant->id)
                ->orderBy('due_date')
                ->get(['id', 'tenant_id', 'title', 'iso_clause', 'target_value', 'current_value', 'unit', 'measurement_method', 'owner_id', 'due_date', 'status']),
            'audits' => Audit::query()
                ->with(['leadAuditor:id,name,email', 'findings:id,audit_id,reference,severity,status'])
                ->where('tenant_id', $tenant->id)
                ->orderByDesc('scheduled_date')
                ->get(['id', 'tenant_id', 'title', 'audit_type', 'iso_standard', 'scope', 'lead_auditor_id', 'scheduled_date', 'completed_at', 'status', 'summary']),
            'findings' => AuditFinding::query()
                ->with(['audit:id,title', 'owner:id,name,email'])
                ->where('tenant_id', $tenant->id)
                ->orderBy('due_date')
                ->get(['id', 'tenant_id', 'audit_id', 'reference', 'iso_clause', 'finding_type', 'severity', 'description', 'evidence', 'owner_id', 'due_date', 'status']),
        ];
    }

    private function trainingEvidence(Tenant $tenant): array
    {
        return [
            'programs' => TrainingProgram::query()
                ->with('owner:id,name,email')
                ->where('tenant_id', $tenant->id)
                ->orderBy('code')
                ->get(['id', 'tenant_id', 'code', 'title', 'iso_clause', 'delivery_method', 'owner_id', 'refresher_interval_days', 'status']),
            'assignments_by_status' => $this->countByField(TrainingAssignment::query()->where('tenant_id', $tenant->id), 'status'),
            'records_by_result' => $this->countByField(TrainingRecord::query()->where('tenant_id', $tenant->id), 'result'),
            'recent_records' => TrainingRecord::query()
                ->with(['trainingProgram:id,code,title', 'user:id,name,email', 'trainer:id,name,email', 'correctiveAction:id,title,status'])
                ->where('tenant_id', $tenant->id)
                ->latest('completed_at')
                ->limit(10)
                ->get(['id', 'tenant_id', 'training_program_id', 'user_id', 'trainer_id', 'corrective_action_id', 'completed_at', 'score', 'result', 'competency_status', 'expires_at', 'notes']),
        ];
    }

    private function incidentEvidence(Tenant $tenant): array
    {
        return [
            'reports_by_status' => $this->countByField(IncidentReport::query()->where('tenant_id', $tenant->id), 'status'),
            'reports_by_severity' => $this->countByField(IncidentReport::query()->where('tenant_id', $tenant->id), 'severity'),
            'reports' => IncidentReport::query()
                ->with(['reporter:id,name,email', 'owner:id,name,email', 'sourceControl', 'correctiveAction:id,title,status'])
                ->where('tenant_id', $tenant->id)
                ->latest('detected_at')
                ->limit(10)
                ->get(['id', 'tenant_id', 'reference', 'title', 'incident_type', 'severity', 'status', 'reported_by_id', 'owner_id', 'source_control_type', 'source_control_id', 'detected_at', 'description', 'immediate_containment', 'corrective_action_id']),
            'emergency_drills' => EmergencyDrill::query()
                ->with(['emergencyResponsePlan:id,name,status', 'facilitator:id,name,email', 'correctiveAction:id,title,status'])
                ->where('tenant_id', $tenant->id)
                ->latest('completed_at')
                ->limit(10)
                ->get(['id', 'tenant_id', 'emergency_response_plan_id', 'facilitator_id', 'completed_at', 'result', 'participants_count', 'effectiveness_score', 'notes', 'corrective_action_id']),
        ];
    }

    private function supplierEvidence(Tenant $tenant): array
    {
        return [
            'suppliers_by_risk_level' => $this->countByField(Supplier::query()->where('tenant_id', $tenant->id), 'risk_level'),
            'suppliers_by_approval_status' => $this->countByField(Supplier::query()->where('tenant_id', $tenant->id), 'approval_status'),
            'suppliers' => Supplier::query()
                ->with(['owner:id,name,email', 'evaluations:id,supplier_id,evaluation_date,score,result,next_review_date'])
                ->where('tenant_id', $tenant->id)
                ->orderBy('name')
                ->get(['id', 'tenant_id', 'name', 'supplier_code', 'category', 'approval_status', 'risk_level', 'approved_until', 'owner_id']),
            'certificates_expiring_90_days' => SupplierCertificate::query()
                ->with('supplier:id,name,supplier_code')
                ->where('tenant_id', $tenant->id)
                ->where('expires_at', '<=', now()->addDays(90)->toDateString())
                ->orderBy('expires_at')
                ->get(['id', 'tenant_id', 'supplier_id', 'certificate_type', 'certificate_number', 'expires_at', 'status']),
            'calibration_failures' => CalibrationRecord::query()
                ->with(['equipmentAsset:id,asset_tag,name,status', 'correctiveAction:id,title,status'])
                ->where('tenant_id', $tenant->id)
                ->whereIn('result', ['Fail', 'Overdue'])
                ->latest('performed_at')
                ->get(['id', 'tenant_id', 'equipment_asset_id', 'corrective_action_id', 'performed_at', 'due_at', 'result', 'notes']),
        ];
    }

    private function capaEvidence(Tenant $tenant): array
    {
        return [
            'open_actions' => CorrectiveAction::query()
                ->with(['assignee:id,name,email', 'verifier:id,name,email'])
                ->where('tenant_id', $tenant->id)
                ->whereNotIn('status', ['Closed', 'Verified'])
                ->orderBy('due_date')
                ->get(['id', 'tenant_id', 'title', 'description', 'assigned_to_id', 'verified_by_id', 'due_date', 'status']),
        ];
    }

    private function auditEvidence(Tenant $tenant): array
    {
        return [
            'events_count' => AuditLog::query()->where('tenant_id', $tenant->id)->count(),
            'latest_hash' => AuditLog::query()->where('tenant_id', $tenant->id)->latest('id')->value('entry_hash'),
            'latest_events' => AuditLog::query()
                ->with('user:id,name,email')
                ->where('tenant_id', $tenant->id)
                ->latest('id')
                ->limit(10)
                ->get(['id', 'tenant_id', 'user_id', 'event', 'auditable_type', 'auditable_id', 'entry_hash', 'previous_hash', 'occurred_at']),
        ];
    }

    private function countByField(Builder $query, string $field): array
    {
        return $query
            ->get([$field])
            ->countBy(fn ($model) => $model->{$field} ?: 'Unspecified')
            ->all();
    }

    private function packetId(Tenant $tenant, ManagementReview $review): string
    {
        return 'MRP-'.str($tenant->slug)->upper().'-'.str_pad((string) $review->id, 4, '0', STR_PAD_LEFT);
    }
}
