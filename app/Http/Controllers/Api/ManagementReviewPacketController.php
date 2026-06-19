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
use Illuminate\Http\Response;

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

    public function pdf(Tenant $tenant, ManagementReview $managementReview): Response
    {
        abort_unless((int) $managementReview->tenant_id === (int) $tenant->id, 404);

        $packet = $this->buildPacket($tenant, $managementReview);
        $filename = (string) str($packet['packet_id'])->lower()->replace([' ', '/'], '-')->append('.pdf');

        return response($this->renderPacketPdf($packet), 200, [
            'Content-Type' => 'application/pdf',
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

    private function renderPacketPdf(array $packet): string
    {
        $packet = json_decode(json_encode($packet, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), true);
        $summary = $packet['evidence_summary'];
        $pages = [];

        $this->appendPdfLines($pages, [
            'ISO-Forge Management Review Packet',
            'Packet ID: '.$packet['packet_id'],
            'Tenant: '.$packet['tenant']['name'].' ('.$packet['tenant']['slug'].')',
            'Review: '.$packet['management_review']['title'],
            'Review date: '.($packet['management_review']['review_date'] ?? '-'),
            'Status: '.$packet['management_review']['status'],
            'Packet hash: '.$packet['packet_hash'],
            'QMS: '.$summary['qms']['objectives'].' objectives, '.$summary['qms']['audits'].' audits, '.$summary['qms']['open_findings'].' open findings',
            'Training: '.$summary['training']['programs'].' programs, '.$summary['training']['assignments'].' assignments, '.$summary['training']['records'].' records',
            'Incident response: '.$summary['incident_response']['reports'].' reports, '.$summary['incident_response']['open_reports'].' open, '.$summary['incident_response']['emergency_drills'].' drills',
            'Supplier quality: '.$summary['supplier_quality']['suppliers'].' suppliers, '.$summary['supplier_quality']['evaluations'].' evaluations, '.$summary['supplier_quality']['certificates'].' certificates',
            'CAPA: '.$summary['capa']['open_actions'].' open actions',
            'Audit chain: '.$summary['audit_chain']['events'].' events',
            'Latest audit hash: '.$summary['audit_chain']['latest_hash'],
            'Generated at: '.$packet['generated_at'],
            '',
            'Signature Blocks',
            'Prepared by: ______________________________ Date: ______________',
            'Reviewed by: ______________________________ Date: ______________',
            'Approved by: ______________________________ Date: ______________',
            'Management review chair: '.$this->pdfField($packet['management_review'], 'chair.name'),
        ]);

        $this->appendPdfTable($pages, 'QMS Objectives', ['Title', 'Owner', 'Status', 'Due'], array_map(fn (array $objective): array => [
            $objective['title'] ?? '-',
            $this->pdfField($objective, 'owner.name'),
            $objective['status'] ?? '-',
            $objective['due_date'] ?? '-',
        ], $packet['qms']['objectives'] ?? []));

        $this->appendPdfTable($pages, 'Audit Program', ['Title', 'Lead', 'Status', 'Scheduled'], array_map(fn (array $audit): array => [
            $audit['title'] ?? '-',
            $this->pdfField($audit, 'lead_auditor.name'),
            $audit['status'] ?? '-',
            $audit['scheduled_date'] ?? '-',
        ], $packet['qms']['audits'] ?? []));

        $this->appendPdfTable($pages, 'Audit Findings', ['Reference', 'Type', 'Severity', 'Status'], array_map(fn (array $finding): array => [
            $finding['reference'] ?? '-',
            $finding['finding_type'] ?? '-',
            $finding['severity'] ?? '-',
            $finding['status'] ?? '-',
        ], $packet['qms']['findings'] ?? []));

        $this->appendPdfTable($pages, 'Training Programs', ['Code', 'Title', 'Owner', 'Status'], array_map(fn (array $program): array => [
            $program['code'] ?? '-',
            $program['title'] ?? '-',
            $this->pdfField($program, 'owner.name'),
            $program['status'] ?? '-',
        ], $packet['training']['programs'] ?? []));

        $this->appendPdfTable($pages, 'Recent Training Records', ['Program', 'User', 'Result', 'Completed'], array_map(fn (array $record): array => [
            $this->pdfField($record, 'training_program.code'),
            $this->pdfField($record, 'user.name'),
            ($record['result'] ?? '-').' / '.($record['competency_status'] ?? '-'),
            $record['completed_at'] ?? '-',
        ], $packet['training']['recent_records'] ?? []));

        $this->appendPdfTable($pages, 'Incident Reports', ['Reference', 'Severity', 'Status', 'Owner'], array_map(fn (array $report): array => [
            $report['reference'] ?? '-',
            $report['severity'] ?? '-',
            $report['status'] ?? '-',
            $this->pdfField($report, 'owner.name'),
        ], $packet['incident_response']['reports'] ?? []));

        $this->appendPdfTable($pages, 'Supplier Register', ['Code', 'Supplier', 'Risk', 'Approval'], array_map(fn (array $supplier): array => [
            $supplier['supplier_code'] ?? '-',
            $supplier['name'] ?? '-',
            $supplier['risk_level'] ?? '-',
            $supplier['approval_status'] ?? '-',
        ], $packet['supplier_quality']['suppliers'] ?? []));

        $this->appendPdfTable($pages, 'Certificates Expiring Within 90 Days', ['Supplier', 'Type', 'Expires', 'Status'], array_map(fn (array $certificate): array => [
            $this->pdfField($certificate, 'supplier.name'),
            $certificate['certificate_type'] ?? '-',
            $certificate['expires_at'] ?? '-',
            $certificate['status'] ?? '-',
        ], $packet['supplier_quality']['certificates_expiring_90_days'] ?? []));

        $this->appendPdfTable($pages, 'Calibration Failures', ['Asset', 'Result', 'Due', 'CAPA'], array_map(fn (array $record): array => [
            $this->pdfField($record, 'equipment_asset.asset_tag'),
            $record['result'] ?? '-',
            $record['due_at'] ?? '-',
            $this->pdfField($record, 'corrective_action.title'),
        ], $packet['supplier_quality']['calibration_failures'] ?? []));

        $this->appendPdfTable($pages, 'Open CAPA', ['Title', 'Assignee', 'Due', 'Status'], array_map(fn (array $action): array => [
            $action['title'] ?? '-',
            $this->pdfField($action, 'assignee.name'),
            $action['due_date'] ?? '-',
            $action['status'] ?? '-',
        ], $packet['capa']['open_actions'] ?? []));

        $this->appendPdfTable($pages, 'Latest Audit Chain Events', ['Event', 'User', 'Hash'], array_map(fn (array $log): array => [
            $log['event'] ?? '-',
            $this->pdfField($log, 'user.name'),
            $log['entry_hash'] ?? '-',
        ], $packet['audit_chain']['latest_events'] ?? []));

        return $this->buildPdfDocument($pages);
    }

    private function appendPdfLines(array &$pages, array $lines): void
    {
        foreach ($lines as $line) {
            if ($pages === [] || count($pages[array_key_last($pages)]) >= 42) {
                $pages[] = [];
            }

            $pages[array_key_last($pages)][] = $this->pdfCell($line, 106);
        }
    }

    private function appendPdfTable(array &$pages, string $title, array $headers, array $rows): void
    {
        $header = implode(' | ', $headers);
        $lines = [
            '',
            $title,
            $header,
            str_repeat('-', min(strlen($header) + 24, 106)),
        ];

        if ($rows === []) {
            $lines[] = 'No records found.';
        }

        foreach ($rows as $row) {
            $lines[] = implode(' | ', array_map(fn ($cell): string => $this->pdfCell($cell, 25), $row));
        }

        $this->appendPdfLines($pages, $lines);
    }

    private function pdfField(array $item, string $path, string $default = '-'): string
    {
        $value = $item;

        foreach (explode('.', $path) as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value) || $value[$segment] === null || $value[$segment] === '') {
                return $default;
            }

            $value = $value[$segment];
        }

        return $this->pdfCell($value, 80);
    }

    private function pdfCell(mixed $value, int $limit): string
    {
        $text = is_array($value)
            ? json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            : (string) ($value ?? '-');

        $text = trim((string) preg_replace('/\s+/', ' ', $text));
        $text = $text === '' ? '-' : $text;
        $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;

        if (strlen($text) <= $limit) {
            return $text;
        }

        return substr($text, 0, max(0, $limit - 3)).'...';
    }

    private function buildPdfDocument(array $pages): string
    {
        $pages = array_values(array_filter($pages));
        $pageCount = count($pages) ?: 1;
        $pages = $pages === [] ? [['ISO-Forge Management Review Packet']] : $pages;
        $kids = [];
        $objects = [
            1 => "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n",
            3 => "3 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n",
        ];

        foreach ($pages as $index => $lines) {
            $pageObjectId = 4 + ($index * 2);
            $contentObjectId = $pageObjectId + 1;
            $kids[] = $pageObjectId.' 0 R';
            $stream = $this->renderPdfPageStream($lines, $index + 1, $pageCount);

            $objects[$pageObjectId] = $pageObjectId." 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 3 0 R >> >> /Contents ".$contentObjectId." 0 R >>\nendobj\n";
            $objects[$contentObjectId] = $contentObjectId." 0 obj\n<< /Length ".strlen($stream)." >>\nstream\n".$stream."endstream\nendobj\n";
        }

        $objects[2] = "2 0 obj\n<< /Type /Pages /Kids [".implode(' ', $kids).'] /Count '.$pageCount." >>\nendobj\n";
        ksort($objects);

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $id => $object) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $object;
        }

        $size = max(array_keys($objects)) + 1;
        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 ".$size."\n";
        $pdf .= "0000000000 65535 f \n";

        for ($id = 1; $id < $size; $id++) {
            $offset = $offsets[$id] ?? 0;
            $pdf .= str_pad((string) $offset, 10, '0', STR_PAD_LEFT)." 00000 n \n";
        }

        $pdf .= "trailer\n<< /Size ".$size." /Root 1 0 R >>\n";
        $pdf .= "startxref\n".$xrefOffset."\n%%EOF\n";

        return $pdf;
    }

    private function renderPdfPageStream(array $lines, int $pageNumber, int $pageCount): string
    {
        $stream = "BT\n/F1 9 Tf\n40 760 Td\n";

        foreach ($lines as $index => $line) {
            if ($index > 0) {
                $stream .= "0 -16 Td\n";
            }

            $stream .= '('.$this->escapePdfText($line).") Tj\n";
        }

        $stream .= "0 -24 Td\n";
        $stream .= '(Page '.$pageNumber.' of '.$pageCount.") Tj\n";
        $stream .= "ET\n";

        return $stream;
    }

    private function escapePdfText(string $line): string
    {
        $line = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $line) ?: $line;

        return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', ' ', ' '], $line);
    }
}
