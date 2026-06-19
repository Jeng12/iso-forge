<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCalibrationRecordRequest;
use App\Http\Requests\StoreEquipmentAssetRequest;
use App\Http\Requests\StoreSupplierCertificateRequest;
use App\Http\Requests\StoreSupplierEvaluationRequest;
use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateEquipmentAssetRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Http\Resources\CalibrationRecordResource;
use App\Http\Resources\EquipmentAssetResource;
use App\Http\Resources\SupplierCertificateResource;
use App\Http\Resources\SupplierEvaluationResource;
use App\Http\Resources\SupplierResource;
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

class SupplierQualityController extends Controller
{
    public function overview(Tenant $tenant): JsonResponse
    {
        return response()->json([
            'data' => [
                'suppliers' => SupplierResource::collection(
                    Supplier::query()
                        ->with([
                            'owner:id,name,email,job_title',
                            'risk:id,title,risk_score,status',
                            'evaluations.evaluator:id,name,email,job_title',
                            'evaluations.evidenceDocument:id,tenant_id,document_number,title,category,owner_id,current_version_id,status,created_at,updated_at',
                            'certificates.document:id,tenant_id,document_number,title,category,owner_id,current_version_id,status,created_at,updated_at',
                        ])
                        ->where('tenant_id', $tenant->id)
                        ->orderBy('name')
                        ->get()
                ),
                'evaluations' => SupplierEvaluationResource::collection(
                    SupplierEvaluation::query()
                        ->with(['supplier:id,name,supplier_code', 'evaluator:id,name,email,job_title', 'evidenceDocument:id,tenant_id,document_number,title,category,owner_id,current_version_id,status,created_at,updated_at'])
                        ->where('tenant_id', $tenant->id)
                        ->latest('evaluation_date')
                        ->limit(50)
                        ->get()
                ),
                'certificates' => SupplierCertificateResource::collection(
                    SupplierCertificate::query()
                        ->with(['supplier:id,name,supplier_code', 'document:id,tenant_id,document_number,title,category,owner_id,current_version_id,status,created_at,updated_at'])
                        ->where('tenant_id', $tenant->id)
                        ->orderBy('expires_at')
                        ->get()
                ),
                'equipment_assets' => EquipmentAssetResource::collection(
                    EquipmentAsset::query()
                        ->with(['owner:id,name,email,job_title', 'calibrationRecords.performer:id,name,email,job_title', 'calibrationRecords.correctiveAction:id,title,status'])
                        ->where('tenant_id', $tenant->id)
                        ->orderBy('asset_tag')
                        ->get()
                ),
                'calibration_records' => CalibrationRecordResource::collection(
                    CalibrationRecord::query()
                        ->with(['equipmentAsset:id,asset_tag,name,status', 'performer:id,name,email,job_title', 'evidenceDocument:id,tenant_id,document_number,title,category,owner_id,current_version_id,status,created_at,updated_at', 'correctiveAction:id,title,status'])
                        ->where('tenant_id', $tenant->id)
                        ->latest('performed_at')
                        ->limit(50)
                        ->get()
                ),
            ],
        ]);
    }

    public function storeSupplier(StoreSupplierRequest $request, Tenant $tenant): JsonResponse
    {
        $data = $request->validated();

        $supplier = Supplier::create([
            ...$data,
            'tenant_id' => $tenant->id,
            'approval_status' => $data['approval_status'] ?? 'Pending',
            'risk_level' => $data['risk_level'] ?? 'Medium',
        ]);

        $this->audit($request, $tenant, 'supplier_quality.supplier.created', Supplier::class, $supplier->id, [], $supplier->toArray());

        return response()->json(['data' => new SupplierResource($supplier->load(['owner:id,name,email,job_title', 'risk:id,title,risk_score,status']))], 201);
    }

    public function updateSupplier(UpdateSupplierRequest $request, Tenant $tenant, Supplier $supplier): JsonResponse
    {
        abort_unless((int) $supplier->tenant_id === (int) $tenant->id, 404);

        $data = $request->validated();
        $oldValues = $supplier->toArray();
        $supplier->update($data);

        $this->audit($request, $tenant, 'supplier_quality.supplier.updated', Supplier::class, $supplier->id, $oldValues, $supplier->fresh()->toArray());

        return response()->json(['data' => new SupplierResource($supplier->fresh(['owner:id,name,email,job_title', 'risk:id,title,risk_score,status']))]);
    }

    public function storeEvaluation(StoreSupplierEvaluationRequest $request, Tenant $tenant, Supplier $supplier): JsonResponse
    {
        abort_unless((int) $supplier->tenant_id === (int) $tenant->id, 404);

        $data = $request->validated();

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
            'data' => new SupplierEvaluationResource($evaluation->load(['supplier:id,name,supplier_code,approval_status,risk_level', 'evaluator:id,name,email,job_title', 'evidenceDocument:id,tenant_id,document_number,title,category,owner_id,current_version_id,status,created_at,updated_at'])),
        ], 201);
    }

    public function storeCertificate(StoreSupplierCertificateRequest $request, Tenant $tenant, Supplier $supplier): JsonResponse
    {
        abort_unless((int) $supplier->tenant_id === (int) $tenant->id, 404);

        $data = $request->validated();

        $certificate = SupplierCertificate::create([
            ...$data,
            'tenant_id' => $tenant->id,
            'supplier_id' => $supplier->id,
            'status' => $data['status'] ?? $this->certificateStatus($data['expires_at']),
        ]);

        $this->audit($request, $tenant, 'supplier_quality.certificate.created', SupplierCertificate::class, $certificate->id, [], $certificate->toArray());

        return response()->json([
            'data' => new SupplierCertificateResource($certificate->load(['supplier:id,name,supplier_code', 'document:id,tenant_id,document_number,title,category,owner_id,current_version_id,status,created_at,updated_at'])),
        ], 201);
    }

    public function storeEquipment(StoreEquipmentAssetRequest $request, Tenant $tenant): JsonResponse
    {
        $data = $request->validated();

        $asset = EquipmentAsset::create([
            ...$data,
            'tenant_id' => $tenant->id,
            'calibration_interval_days' => $data['calibration_interval_days'] ?? 365,
            'critical_to_food_safety' => $data['critical_to_food_safety'] ?? false,
            'status' => $data['status'] ?? 'Active',
        ]);

        $this->audit($request, $tenant, 'supplier_quality.equipment.created', EquipmentAsset::class, $asset->id, [], $asset->toArray());

        return response()->json(['data' => new EquipmentAssetResource($asset->load('owner:id,name,email,job_title'))], 201);
    }

    public function updateEquipment(UpdateEquipmentAssetRequest $request, Tenant $tenant, EquipmentAsset $equipmentAsset): JsonResponse
    {
        abort_unless((int) $equipmentAsset->tenant_id === (int) $tenant->id, 404);

        $data = $request->validated();
        $oldValues = $equipmentAsset->toArray();
        $equipmentAsset->update($data);

        $this->audit($request, $tenant, 'supplier_quality.equipment.updated', EquipmentAsset::class, $equipmentAsset->id, $oldValues, $equipmentAsset->fresh()->toArray());

        return response()->json(['data' => new EquipmentAssetResource($equipmentAsset->fresh('owner:id,name,email,job_title'))]);
    }

    public function storeCalibration(StoreCalibrationRecordRequest $request, Tenant $tenant, EquipmentAsset $equipmentAsset): JsonResponse
    {
        abort_unless((int) $equipmentAsset->tenant_id === (int) $tenant->id, 404);

        $data = $request->validated();

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
            'data' => new CalibrationRecordResource($record->load(['equipmentAsset:id,asset_tag,name,status', 'performer:id,name,email,job_title', 'evidenceDocument:id,tenant_id,document_number,title,category,owner_id,current_version_id,status,created_at,updated_at', 'correctiveAction:id,title,status'])),
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
