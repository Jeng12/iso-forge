<?php

namespace Database\Seeders;

use App\Models\Audit;
use App\Models\AuditFinding;
use App\Models\AuditLog;
use App\Models\CorrectiveAction;
use App\Models\CriticalControlPoint;
use App\Models\Document;
use App\Models\DocumentApproval;
use App\Models\DocumentVersion;
use App\Models\ElectronicSignature;
use App\Models\HaccpPlan;
use App\Models\HazardAnalysis;
use App\Models\ManagementReview;
use App\Models\MonitoringRecord;
use App\Models\NonConformance;
use App\Models\OperationalPrerequisiteProgram;
use App\Models\Permission;
use App\Models\PrerequisiteProgram;
use App\Models\ProcessStep;
use App\Models\QualityObjective;
use App\Models\Risk;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowInstance;
use App\Models\WorkflowTask;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::create([
            'name' => 'Angkor Quality Foods',
            'slug' => 'angkor-quality-foods',
            'industry' => 'Food Manufacturing',
            'is_active' => true,
        ]);

        $permissions = collect([
            ['name' => 'Create documents', 'slug' => 'document.create'],
            ['name' => 'Approve documents', 'slug' => 'document.approve'],
            ['name' => 'View documents', 'slug' => 'document.view'],
            ['name' => 'Open CAPA', 'slug' => 'capa.create'],
            ['name' => 'Close CAPA', 'slug' => 'capa.close'],
            ['name' => 'View audit ledger', 'slug' => 'audit.view'],
            ['name' => 'Manage risks', 'slug' => 'risk.manage'],
            ['name' => 'View QMS module', 'slug' => 'qms.view'],
            ['name' => 'Manage QMS module', 'slug' => 'qms.manage'],
            ['name' => 'View FSMS module', 'slug' => 'fsms.view'],
            ['name' => 'Manage FSMS module', 'slug' => 'fsms.manage'],
        ])->map(fn (array $permission) => Permission::create($permission));

        $adminRole = Role::create([
            'tenant_id' => $tenant->id,
            'name' => 'System Admin',
            'slug' => 'system-admin',
            'description' => 'Maintains tenant configuration, users, and evidence integrity.',
        ]);

        $qualityRole = Role::create([
            'tenant_id' => $tenant->id,
            'name' => 'Quality Manager',
            'slug' => 'quality-manager',
            'description' => 'Owns document control, CAPA, and management system performance.',
        ]);

        $auditorRole = Role::create([
            'tenant_id' => $tenant->id,
            'name' => 'Auditor',
            'slug' => 'auditor',
            'description' => 'Reviews objective evidence with read-only audit access.',
        ]);

        $operatorRole = Role::create([
            'tenant_id' => $tenant->id,
            'name' => 'Production Operator',
            'slug' => 'production-operator',
            'description' => 'Uses approved work instructions and reports deviations.',
        ]);

        $adminRole->permissions()->attach($permissions->pluck('id'));
        $qualityRole->permissions()->attach($permissions->whereIn('slug', [
            'document.create',
            'document.approve',
            'document.view',
            'capa.create',
            'capa.close',
            'audit.view',
            'risk.manage',
            'qms.view',
            'qms.manage',
            'fsms.view',
            'fsms.manage',
        ])->pluck('id'));
        $auditorRole->permissions()->attach($permissions->whereIn('slug', [
            'document.view',
            'audit.view',
            'qms.view',
            'fsms.view',
        ])->pluck('id'));
        $operatorRole->permissions()->attach($permissions->whereIn('slug', [
            'document.view',
            'capa.create',
        ])->pluck('id'));

        $jojo = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Jojo ISO Lead',
            'email' => 'jojo@iso-forge.test',
            'job_title' => 'Backend and Database Lead',
            'password' => Hash::make('password'),
        ]);
        $jojo->roles()->attach([$adminRole->id, $qualityRole->id]);

        $joto = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Joto Quality Designer',
            'email' => 'joto@iso-forge.test',
            'job_title' => 'UI/UX and Compliance Analyst',
            'password' => Hash::make('password'),
        ]);
        $joto->roles()->attach($qualityRole->id);

        $jono = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'JoNo Floor Operator',
            'email' => 'jono@iso-forge.test',
            'job_title' => 'Production Operator',
            'password' => Hash::make('password'),
        ]);
        $jono->roles()->attach($operatorRole->id);

        $auditor = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Sokha External Auditor',
            'email' => 'auditor@iso-forge.test',
            'job_title' => 'Certification Auditor',
            'password' => Hash::make('password'),
        ]);
        $auditor->roles()->attach($auditorRole->id);

        $sop = Document::create([
            'tenant_id' => $tenant->id,
            'document_number' => 'QMS-SOP-001',
            'title' => 'Documented Information Control',
            'category' => 'ISO 9001 Clause 7.5',
            'owner_id' => $jojo->id,
            'status' => 'Approved',
        ]);

        $sopVersion = DocumentVersion::create([
            'document_id' => $sop->id,
            'version_number' => '1.0',
            'file_path' => 'documents/qms-sop-001-v1.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 284000,
            'effective_date' => now()->subDays(6)->toDateString(),
            'status' => 'Approved',
            'reviewed_by_id' => $joto->id,
            'approved_by_id' => $jojo->id,
            'review_date' => now()->subDays(8)->toDateString(),
            'approval_date' => now()->subDays(7)->toDateString(),
            'change_summary' => 'Initial controlled release for Annex SL documented information.',
        ]);

        $sop->update(['current_version_id' => $sopVersion->id]);

        DocumentApproval::create([
            'document_version_id' => $sopVersion->id,
            'approver_id' => $jojo->id,
            'status' => 'Approved',
            'comments' => 'Released for production use.',
            'approved_at' => now()->subDays(7),
        ]);

        ElectronicSignature::sign($sopVersion, $jojo, 'Document approval', 'Release SOP version 1.0');

        $pendingProcedure = Document::create([
            'tenant_id' => $tenant->id,
            'document_number' => 'FSMS-WI-014',
            'title' => 'CCP Temperature Monitoring Work Instruction',
            'category' => 'ISO 22000 Clause 8.5',
            'owner_id' => $joto->id,
            'status' => 'Under Review',
        ]);

        $pendingVersion = DocumentVersion::create([
            'document_id' => $pendingProcedure->id,
            'version_number' => '0.3',
            'file_path' => 'documents/fsms-wi-014-draft.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 198000,
            'status' => 'Under Review',
            'reviewed_by_id' => $joto->id,
            'change_summary' => 'Adds deviation escalation rule for critical limit breaches.',
        ]);

        $pendingProcedure->update(['current_version_id' => $pendingVersion->id]);

        DocumentApproval::create([
            'document_version_id' => $pendingVersion->id,
            'approver_id' => $jojo->id,
            'status' => 'Pending',
        ]);

        $supplierRisk = Risk::create([
            'tenant_id' => $tenant->id,
            'title' => 'Approved supplier documents expire before annual reassessment',
            'category' => 'Supplier Quality',
            'likelihood' => 4,
            'severity' => 4,
            'residual_likelihood' => 2,
            'residual_severity' => 3,
            'owner_id' => $joto->id,
            'treatment_plan' => 'Automate supplier certificate expiry reminders and escalation tasks.',
            'status' => 'Treatment Planned',
        ]);

        Risk::create([
            'tenant_id' => $tenant->id,
            'title' => 'Production team references obsolete cleaning instruction',
            'category' => 'Document Control',
            'likelihood' => 3,
            'severity' => 5,
            'residual_likelihood' => 1,
            'residual_severity' => 4,
            'owner_id' => $jojo->id,
            'treatment_plan' => 'Replace printed binders with controlled QR-code access.',
            'status' => 'Mitigating',
        ]);

        $nonConformance = NonConformance::create([
            'tenant_id' => $tenant->id,
            'reference' => 'NC-2026-0001',
            'source' => 'Internal Audit',
            'description' => 'Two operators used a superseded sanitation checklist during line clearance.',
            'iso_clause' => 'ISO 9001:2015 7.5.3',
            'severity' => 'Major',
            'status' => 'Investigation',
            'detected_at' => now()->subDays(3)->toDateString(),
            'owner_id' => $jojo->id,
            'root_cause' => 'Printed document binder was not reconciled after procedure release.',
        ]);

        $capa = CorrectiveAction::create([
            'tenant_id' => $tenant->id,
            'non_conformance_id' => $nonConformance->id,
            'risk_id' => $supplierRisk->id,
            'title' => 'Lock document distribution to approved current versions',
            'description' => 'Introduce controlled access links and retire uncontrolled print binders.',
            'assigned_to_id' => $jojo->id,
            'verified_by_id' => $auditor->id,
            'due_date' => now()->addDays(10)->toDateString(),
            'status' => 'In Progress',
        ]);

        $objective = QualityObjective::create([
            'tenant_id' => $tenant->id,
            'title' => 'Reduce uncontrolled document use on production lines',
            'iso_clause' => 'ISO 9001:2015 6.2',
            'baseline_value' => 6,
            'target_value' => 1,
            'current_value' => 2,
            'unit' => 'incidents',
            'measurement_method' => 'Monthly line-clearance audit sampling',
            'owner_id' => $jojo->id,
            'due_date' => now()->addMonths(3)->toDateString(),
            'status' => 'Active',
        ]);

        $auditProgram = Audit::create([
            'tenant_id' => $tenant->id,
            'title' => 'Q3 ISO 9001 Internal Audit',
            'audit_type' => 'Internal',
            'iso_standard' => 'ISO 9001:2015',
            'scope' => 'Document control, production release, supplier quality, and CAPA effectiveness.',
            'lead_auditor_id' => $auditor->id,
            'scheduled_date' => now()->addDays(21)->toDateString(),
            'status' => 'Planned',
            'summary' => 'Audit plan prepared for process-based QMS review.',
        ]);

        $finding = AuditFinding::create([
            'tenant_id' => $tenant->id,
            'audit_id' => $auditProgram->id,
            'non_conformance_id' => $nonConformance->id,
            'reference' => 'AF-2026-0001',
            'iso_clause' => 'ISO 9001:2015 7.5.3',
            'finding_type' => 'Nonconformity',
            'severity' => 'Major',
            'description' => 'Controlled document retrieval process was not consistently followed.',
            'evidence' => 'Two sampled sanitation records referenced obsolete revision codes.',
            'owner_id' => $jojo->id,
            'due_date' => now()->addDays(14)->toDateString(),
            'status' => 'Open',
        ]);

        $review = ManagementReview::create([
            'tenant_id' => $tenant->id,
            'title' => 'Monthly QMS Leadership Review',
            'review_date' => now()->addDays(30)->toDateString(),
            'chair_id' => $jojo->id,
            'inputs' => [
                'audit_results' => 'Q3 internal audit plan and open document-control finding.',
                'customer_feedback' => 'No critical complaints in the current period.',
                'process_performance' => 'CAPA closure timeliness is stable but document retrieval needs improvement.',
            ],
            'decisions' => [
                'prioritize_qr_document_access' => true,
                'increase_floor_supervisor_sampling' => true,
            ],
            'actions' => [
                ['owner' => 'Jojo ISO Lead', 'action' => 'Deploy approved-document QR access points.'],
                ['owner' => 'Joto Quality Designer', 'action' => 'Update line clearance visual checks.'],
            ],
            'status' => 'Planned',
        ]);

        $haccpPlan = HaccpPlan::create([
            'tenant_id' => $tenant->id,
            'name' => 'Pasteurized Mango Juice HACCP Plan',
            'product' => 'Mango Juice',
            'scope' => 'Receiving, preparation, pasteurization, filling, and finished-product release.',
            'owner_id' => $joto->id,
            'effective_date' => now()->addDays(10)->toDateString(),
            'status' => 'Active',
        ]);

        ProcessStep::create([
            'tenant_id' => $tenant->id,
            'haccp_plan_id' => $haccpPlan->id,
            'sequence' => 1,
            'name' => 'Raw mango receiving',
            'description' => 'Approved supplier and incoming fruit inspection before washing.',
        ]);

        $pasteurizationStep = ProcessStep::create([
            'tenant_id' => $tenant->id,
            'haccp_plan_id' => $haccpPlan->id,
            'sequence' => 4,
            'name' => 'Pasteurization',
            'description' => 'Thermal treatment before aseptic filling.',
        ]);

        $hazard = HazardAnalysis::create([
            'tenant_id' => $tenant->id,
            'process_step_id' => $pasteurizationStep->id,
            'hazard_type' => 'Biological',
            'hazard_description' => 'Pathogen survival from insufficient heat treatment.',
            'likelihood' => 3,
            'severity' => 5,
            'control_measure' => 'Validate pasteurizer temperature and holding time for every batch.',
            'control_type' => 'CCP',
            'status' => 'Assessed',
        ]);

        $ccp = CriticalControlPoint::create([
            'tenant_id' => $tenant->id,
            'hazard_analysis_id' => $hazard->id,
            'name' => 'Pasteurization temperature',
            'critical_limit' => '>=72 C for 15 seconds',
            'monitoring_frequency' => 'Every batch',
            'responsible_user_id' => $jono->id,
            'corrective_action_procedure' => 'Hold affected batch, verify pasteurizer calibration, and open CAPA before release.',
            'status' => 'Active',
        ]);

        $oprpHazard = HazardAnalysis::create([
            'tenant_id' => $tenant->id,
            'process_step_id' => $pasteurizationStep->id,
            'hazard_type' => 'Physical',
            'hazard_description' => 'Foreign material from processing equipment.',
            'likelihood' => 2,
            'severity' => 4,
            'control_measure' => 'Metal detection verification after filling.',
            'control_type' => 'OPRP',
            'status' => 'Assessed',
        ]);

        OperationalPrerequisiteProgram::create([
            'tenant_id' => $tenant->id,
            'hazard_analysis_id' => $oprpHazard->id,
            'name' => 'Metal detector challenge test',
            'control_measure' => 'Challenge test with Fe, non-Fe, and stainless standards.',
            'monitoring_frequency' => 'Start-up and every two hours',
            'responsible_user_id' => $joto->id,
            'status' => 'Active',
        ]);

        $prp = PrerequisiteProgram::create([
            'tenant_id' => $tenant->id,
            'name' => 'Sanitation prerequisite program',
            'category' => 'Sanitation',
            'description' => 'Pre-operation cleaning verification for juice contact surfaces.',
            'owner_id' => $joto->id,
            'verification_frequency' => 'Daily pre-operation inspection',
            'status' => 'Active',
        ]);

        $monitoringRecord = MonitoringRecord::create([
            'tenant_id' => $tenant->id,
            'monitorable_type' => CriticalControlPoint::class,
            'monitorable_id' => $ccp->id,
            'recorded_by_id' => $jono->id,
            'measured_value' => 73.20,
            'unit' => 'C',
            'result' => 'Pass',
            'is_deviation' => false,
            'observed_at' => now()->subHours(6),
            'notes' => 'Batch MJ-260618-01 remained above the validated critical limit.',
        ]);

        $workflow = Workflow::create([
            'tenant_id' => $tenant->id,
            'name' => 'CAPA Workflow',
            'description' => 'Root cause, action implementation, independent effectiveness verification.',
            'model_type' => CorrectiveAction::class,
            'definition' => [
                'states' => ['open', 'investigation', 'implementation', 'verification', 'closed'],
                'transitions' => [
                    ['from' => 'open', 'to' => 'investigation', 'permission' => 'capa.create'],
                    ['from' => 'verification', 'to' => 'closed', 'permission' => 'capa.close'],
                ],
            ],
            'is_active' => true,
        ]);

        $instance = WorkflowInstance::create([
            'tenant_id' => $tenant->id,
            'workflow_id' => $workflow->id,
            'model_type' => CorrectiveAction::class,
            'model_id' => $capa->id,
            'current_state' => 'implementation',
            'status' => 'Open',
            'started_by_id' => $jojo->id,
        ]);

        WorkflowTask::create([
            'workflow_instance_id' => $instance->id,
            'assigned_to_id' => $jojo->id,
            'title' => 'Remove obsolete printed sanitation checklists',
            'state' => 'implementation',
            'status' => 'Open',
            'due_at' => now()->addDays(3),
        ]);

        WorkflowTask::create([
            'workflow_instance_id' => $instance->id,
            'assigned_to_id' => $auditor->id,
            'title' => 'Verify effectiveness after next line clearance audit',
            'state' => 'verification',
            'status' => 'Waiting',
            'due_at' => now()->addDays(14),
        ]);

        AuditLog::appendFor($tenant->id, $jojo->id, 'tenant.created', Tenant::class, $tenant->id, [], [
            'name' => $tenant->name,
            'slug' => $tenant->slug,
        ]);

        AuditLog::appendFor($tenant->id, $jojo->id, 'document.approved', DocumentVersion::class, $sopVersion->id, [
            'status' => 'Under Review',
        ], [
            'status' => 'Approved',
            'document_number' => $sop->document_number,
        ]);

        AuditLog::appendFor($tenant->id, $jojo->id, 'capa.opened', CorrectiveAction::class, $capa->id, [], [
            'reference' => $nonConformance->reference,
            'status' => $capa->status,
        ]);

        AuditLog::appendFor($tenant->id, $joto->id, 'risk.assessed', Risk::class, $supplierRisk->id, [], [
            'risk_score' => $supplierRisk->risk_score,
            'residual_score' => $supplierRisk->residual_score,
        ]);

        AuditLog::appendFor($tenant->id, $jojo->id, 'qms.objective.created', QualityObjective::class, $objective->id, [], [
            'title' => $objective->title,
            'target_value' => $objective->target_value,
        ]);

        AuditLog::appendFor($tenant->id, $auditor->id, 'qms.audit.created', Audit::class, $auditProgram->id, [], [
            'title' => $auditProgram->title,
            'scheduled_date' => $auditProgram->scheduled_date?->toDateString(),
        ]);

        AuditLog::appendFor($tenant->id, $auditor->id, 'qms.audit_finding.created', AuditFinding::class, $finding->id, [], [
            'reference' => $finding->reference,
            'severity' => $finding->severity,
        ]);

        AuditLog::appendFor($tenant->id, $jojo->id, 'qms.management_review.created', ManagementReview::class, $review->id, [], [
            'title' => $review->title,
            'review_date' => $review->review_date?->toDateString(),
        ]);

        AuditLog::appendFor($tenant->id, $joto->id, 'fsms.haccp_plan.created', HaccpPlan::class, $haccpPlan->id, [], [
            'name' => $haccpPlan->name,
            'product' => $haccpPlan->product,
        ]);

        AuditLog::appendFor($tenant->id, $joto->id, 'fsms.hazard.created', HazardAnalysis::class, $hazard->id, [], [
            'hazard_type' => $hazard->hazard_type,
            'risk_score' => $hazard->risk_score,
        ]);

        AuditLog::appendFor($tenant->id, $jono->id, 'fsms.ccp.created', CriticalControlPoint::class, $ccp->id, [], [
            'name' => $ccp->name,
            'critical_limit' => $ccp->critical_limit,
        ]);

        AuditLog::appendFor($tenant->id, $jono->id, 'fsms.monitoring_record.created', MonitoringRecord::class, $monitoringRecord->id, [], [
            'result' => $monitoringRecord->result,
            'is_deviation' => $monitoringRecord->is_deviation,
        ]);
    }
}
