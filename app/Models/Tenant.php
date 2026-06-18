<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'industry',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function roles(): HasMany
    {
        return $this->hasMany(Role::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function risks(): HasMany
    {
        return $this->hasMany(Risk::class);
    }

    public function qualityObjectives(): HasMany
    {
        return $this->hasMany(QualityObjective::class);
    }

    public function audits(): HasMany
    {
        return $this->hasMany(Audit::class);
    }

    public function auditFindings(): HasMany
    {
        return $this->hasMany(AuditFinding::class);
    }

    public function managementReviews(): HasMany
    {
        return $this->hasMany(ManagementReview::class);
    }

    public function haccpPlans(): HasMany
    {
        return $this->hasMany(HaccpPlan::class);
    }

    public function hazardAnalyses(): HasMany
    {
        return $this->hasMany(HazardAnalysis::class);
    }

    public function criticalControlPoints(): HasMany
    {
        return $this->hasMany(CriticalControlPoint::class);
    }

    public function operationalPrerequisitePrograms(): HasMany
    {
        return $this->hasMany(OperationalPrerequisiteProgram::class);
    }

    public function prerequisitePrograms(): HasMany
    {
        return $this->hasMany(PrerequisiteProgram::class);
    }

    public function monitoringRecords(): HasMany
    {
        return $this->hasMany(MonitoringRecord::class);
    }

    public function suppliers(): HasMany
    {
        return $this->hasMany(Supplier::class);
    }

    public function supplierEvaluations(): HasMany
    {
        return $this->hasMany(SupplierEvaluation::class);
    }

    public function supplierCertificates(): HasMany
    {
        return $this->hasMany(SupplierCertificate::class);
    }

    public function equipmentAssets(): HasMany
    {
        return $this->hasMany(EquipmentAsset::class);
    }

    public function calibrationRecords(): HasMany
    {
        return $this->hasMany(CalibrationRecord::class);
    }

    public function trainingPrograms(): HasMany
    {
        return $this->hasMany(TrainingProgram::class);
    }

    public function competencyRequirements(): HasMany
    {
        return $this->hasMany(CompetencyRequirement::class);
    }

    public function trainingAssignments(): HasMany
    {
        return $this->hasMany(TrainingAssignment::class);
    }

    public function trainingRecords(): HasMany
    {
        return $this->hasMany(TrainingRecord::class);
    }

    public function awarenessAcknowledgements(): HasMany
    {
        return $this->hasMany(AwarenessAcknowledgement::class);
    }

    public function incidentReports(): HasMany
    {
        return $this->hasMany(IncidentReport::class);
    }

    public function incidentActions(): HasMany
    {
        return $this->hasMany(IncidentAction::class);
    }

    public function emergencyResponsePlans(): HasMany
    {
        return $this->hasMany(EmergencyResponsePlan::class);
    }

    public function emergencyDrills(): HasMany
    {
        return $this->hasMany(EmergencyDrill::class);
    }

    public function correctiveActions(): HasMany
    {
        return $this->hasMany(CorrectiveAction::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }
}
