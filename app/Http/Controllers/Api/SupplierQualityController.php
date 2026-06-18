<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\CalibrationRecord;
use App\Models\CorrectiveAction;
use App\Models\EquipmentAsset;
use App\Models\Supplier;
use App\Models\SupplierCertificate;
use App\Models\SupplierEvaluation;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SupplierQualityController extends Controller
{
    public function overview(Tenant $tenant): JsonResponse
    {
        return response()->json([
            'data' => [
                'suppliers' => Supplier::query()
                    ->with([
                        'owner:id,name,email',
                        'risk:id,title,risk_score,status',
                        'evaluations.evaluator:id,name,email',
                        'evaluations.evidenceDocument:id,document_number,title,status',
                        'certificates.document:id,document_number,title,status',
                    ])
                    ->where('tenant_id', $tenant->id)
                    ->orderBy('name')
                    ->get(),
                'evaluations' => SupplierEvaluation::query()
                    ->with(['supplier:id,name,supplier_code', 'evaluator:id,name,email', 'evidenceDocument:id,document_number,title,status'])
                    ->where('tenant_id', $tenant->id)
                    ->latest('evaluation_date')
                    ->limit(50)
                    ->get(),
                'certificates' => SupplierCertificate::query()
                    ->with(['supplier:id,name,supplier_code', 'document:id,document_number,title,status'])
                    ->where('tenant_id', $tenant->id)
                    ->orderBy('expires_at')
                    ->get(),
                'equipment_assets' => EquipmentAsset::query()
                    ->with(['owner:id,name,email', 'calibrationRecords.performer:id,name,email', 'calibrationRecords.correctiveAction:id,title,status'])
                    ->where('tenant_id', $tenant->id)
                    ->orderBy('asset_tag')
                    ->get(),
                'calibration_records' => CalibrationRecord::query()
                    ->with(['equipmentAsset:id,asset_tag,name,status', 'performer:id,name,email', 'evidenceDocument:id,document_number,title,status', 'correctiveAction:id,title,status'])
                    ->where('tenant_id', $tenant->id)
                    ->latest('performed_at')
                    ->limit(50)
                    ->get(),
            ],
        ]);
    }

    public function storeSupplier(Request $request, Tenant $tenant): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'supplier_code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('suppliers')->where('tenant_id', $tenant->id),
            ],
            'category' => ['required', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'approval_status' => ['sometimes', 'string', 'max:255'],
            'risk_level' => ['sometimes', 'string', 'max:255'],
            'approved_until' => ['nullable', 'date'],
            'owner_id' => ['nullable', Rule::exists('users', 'id')->where('tenant_id', $tenant->id)],
            'risk_id' => ['nullable', Rule::exists('risks', 'id')->where('tenant_id', $tenant->id)],
            'notes' => ['nullable', 'string'],
        ]);

        $supplier = Supplier::create([
            ...$data,
            'tenant_id' => $tenant->id,
            'approval_status' => $data['approval_status'] ?? 'Pending',
            'risk_level' => $data['risk_level'] ?? 'Medium',
        ]);

        $this->audit($request, $tenant, 'supplier_quality.supplier.created', Supplier::class, $supplier->id, [], $supplier->toArray());

        return response()->json(['data' => $supplier->load(['owner:id,name,email', 'risk:id,title,risk_score,status'])], 201);
    }

    public function storeEvaluation(Request $request, Tenant $tenant, Supplier $supplier): JsonResponse
    {
        abort_unless((int) $supplier->tenant_id === (int) $tenant->id, 404);

        $data = $request->validate([
            'evaluated_by_id' => ['nullable', Rule::exists('users', 'id')->where('tenant_id', $tenant->id)],
            'evaluation_date' => ['required', 'date'],
            'score' => ['required', 'integer', 'min:0', 'max:100'],
            'result' => ['required', 'in:Approved,Conditional,Rejected'],
            'next_review_date' => ['nullable', 'date'],
            'evidence_document_id' => ['nullable', Rule::exists('documents', 'id')->where('tenant_id', $tenant->id)],
            'notes' => ['nullable', 'string'],
        ]);

        $evaluation = DB::transaction(function () use ($data, $request, $supplier, $tenant): SupplierEvaluation {
            $evaluation = SupplierEvaluation::create([
                ...$data,
                'tenant_id' => $tenant->id,
                'supplier_id' => $supplier->id,
            ]);

            $oldValues = $supplier->toArray();
            $supplier->update([
                'approval_status' => $data['result'],
                'risk_level' => $this->riskLevelForScore((int) $data['score']),
                'approved_until' => $data['next_review_date'] ?? null,
            ]);

            $this->audit($request, $tenant, 'supplier_quality.supplier.updated', Supplier::class, $supplier->id, $oldValues, $supplier->fresh()->toArray());
            $this->audit($request, $tenant, 'supplier_quality.evaluation.created', SupplierEvaluation::class, $evaluation->id, [], $evaluation->toArray());

            return $evaluation;
        });

        return response()->json([
            'data' => $evaluation->load(['supplier:id,name,supplier_code,approval_status,risk_level', 'evaluator:id,name,email', 'evidenceDocument:id,document_number,title,status']),
        ], 201);
    }

    public function storeCertificate(Request $request, Tenant $tenant, Supplier $supplier): JsonResponse
    {
        abort_unless((int) $supplier->tenant_id === (int) $tenant->id, 404);

        $data = $request->validate([
            'document_id' => ['nullable', Rule::exists('documents', 'id')->where('tenant_id', $tenant->id)],
            'certificate_type' => ['required', 'string', 'max:255'],
            'certificate_number' => ['required', 'string', 'max:255'],
            'issued_at' => ['nullable', 'date'],
            'expires_at' => ['required', 'date'],
            'status' => ['sometimes', 'string', 'max:255'],
        ]);

        $certificate = SupplierCertificate::create([
            ...$data,
            'tenant_id' => $tenant->id,
            'supplier_id' => $supplier->id,
            'status' => $data['status'] ?? $this->certificateStatus($data['expires_at']),
        ]);

        $this->audit($request, $tenant, 'supplier_quality.certificate.created', SupplierCertificate::class, $certificate->id, [], $certificate->toArray());

        return response()->json([
            'data' => $certificate->load(['supplier:id,name,supplier_code', 'document:id,document_number,title,status']),
        ], 201);
    }

    public function storeEquipment(Request $request, Tenant $tenant): JsonResponse
    {
        $data = $request->validate([
            'asset_tag' => [
                'required',
                'string',
                'max:255',
                Rule::unique('equipment_assets')->where('tenant_id', $tenant->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'location' => ['required', 'string', 'max:255'],
            'owner_id' => ['nullable', Rule::exists('users', 'id')->where('tenant_id', $tenant->id)],
            'calibration_interval_days' => ['sometimes', 'integer', 'min:1', 'max:3660'],
            'critical_to_food_safety' => ['sometimes', 'boolean'],
            'last_calibrated_at' => ['nullable', 'date'],
            'next_calibration_due_at' => ['nullable', 'date'],
            'status' => ['sometimes', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $asset = EquipmentAsset::create([
            ...$data,
            'tenant_id' => $tenant->id,
            'calibration_interval_days' => $data['calibration_interval_days'] ?? 365,
            'critical_to_food_safety' => $data['critical_to_food_safety'] ?? false,
            'status' => $data['status'] ?? 'Active',
        ]);

        $this->audit($request, $tenant, 'supplier_quality.equipment.created', EquipmentAsset::class, $asset->id, [], $asset->toArray());

        return response()->json(['data' => $asset->load('owner:id,name,email')], 201);
    }

    public function storeCalibration(Request $request, Tenant $tenant, EquipmentAsset $equipmentAsset): JsonResponse
    {
        abort_unless((int) $equipmentAsset->tenant_id === (int) $tenant->id, 404);

        $data = $request->validate([
            'performed_by_id' => ['nullable', Rule::exists('users', 'id')->where('tenant_id', $tenant->id)],
            'evidence_document_id' => ['nullable', Rule::exists('documents', 'id')->where('tenant_id', $tenant->id)],
            'performed_at' => ['required', 'date'],
            'due_at' => ['required', 'date'],
            'result' => ['required', 'in:Pass,Adjusted,Fail,Overdue'],
            'certificate_number' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $record = DB::transaction(function () use ($data, $equipmentAsset, $request, $tenant): CalibrationRecord {
            $correctiveAction = null;

            if (in_array($data['result'], ['Fail', 'Overdue'], true)) {
                $correctiveAction = CorrectiveAction::create([
                    'tenant_id' => $tenant->id,
                    'title' => 'Calibration attention: '.$equipmentAsset->asset_tag,
                    'description' => $data['notes'] ?? 'Calibration record requires corrective action before equipment release.',
                    'assigned_to_id' => $data['performed_by_id'] ?? $request->user()->id,
                    'verified_by_id' => $request->user()->id,
                    'due_date' => now()->addDays(7)->toDateString(),
                    'status' => 'Open',
                ]);
            }

            $record = CalibrationRecord::create([
                ...$data,
                'tenant_id' => $tenant->id,
                'equipment_asset_id' => $equipmentAsset->id,
                'corrective_action_id' => $correctiveAction?->id,
            ]);

            $oldValues = $equipmentAsset->toArray();
            $equipmentAsset->update([
                'last_calibrated_at' => $data['performed_at'],
                'next_calibration_due_at' => $data['due_at'],
                'status' => in_array($data['result'], ['Fail', 'Overdue'], true) ? 'Hold' : 'Active',
            ]);

            $this->audit($request, $tenant, 'supplier_quality.equipment.updated', EquipmentAsset::class, $equipmentAsset->id, $oldValues, $equipmentAsset->fresh()->toArray());
            $this->audit($request, $tenant, 'supplier_quality.calibration.created', CalibrationRecord::class, $record->id, [], $record->toArray());

            if ($correctiveAction) {
                $this->audit($request, $tenant, 'supplier_quality.calibration_capa.created', CorrectiveAction::class, $correctiveAction->id, [], $correctiveAction->toArray());
            }

            return $record;
        });

        return response()->json([
            'data' => $record->load(['equipmentAsset:id,asset_tag,name,status', 'performer:id,name,email', 'evidenceDocument:id,document_number,title,status', 'correctiveAction:id,title,status']),
        ], 201);
    }

    private function riskLevelForScore(int $score): string
    {
        if ($score >= 90) {
            return 'Low';
        }

        if ($score >= 75) {
            return 'Medium';
        }

        return 'High';
    }

    private function certificateStatus(string $expiresAt): string
    {
        $expiry = Carbon::parse($expiresAt)->startOfDay();

        if ($expiry->isPast()) {
            return 'Expired';
        }

        if ($expiry->lte(now()->addDays(30)->startOfDay())) {
            return 'Expiring';
        }

        return 'Current';
    }

    private function audit(Request $request, Tenant $tenant, string $event, string $type, int $id, array $oldValues, array $newValues): void
    {
        AuditLog::appendFor(
            $tenant->id,
            $request->user()->id,
            $event,
            $type,
            $id,
            $oldValues,
            $newValues,
            $request->ip(),
            $request->userAgent(),
        );
    }
}
