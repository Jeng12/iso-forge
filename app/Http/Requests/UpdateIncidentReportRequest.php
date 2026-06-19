<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIncidentReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenant = $this->route('tenant');
        $incidentReport = $this->route('incidentReport');

        return [
            'reference' => ['sometimes', 'string', 'max:255', Rule::unique('incident_reports')->where('tenant_id', $tenant->id)->ignore($incidentReport?->id)],
            'title' => ['sometimes', 'string', 'max:255'],
            'incident_type' => ['sometimes', 'string', 'max:255'],
            'severity' => ['sometimes', 'in:Minor,Major,Critical'],
            'status' => ['sometimes', 'string', 'max:255'],
            'reported_by_id' => ['sometimes', 'nullable', Rule::exists('users', 'id')->where('tenant_id', $tenant->id)],
            'owner_id' => ['sometimes', 'nullable', Rule::exists('users', 'id')->where('tenant_id', $tenant->id)],
            'source_control_type' => ['sometimes', 'nullable', 'required_with:source_control_id', 'in:ccp,oprp,prp'],
            'source_control_id' => ['sometimes', 'nullable', 'required_with:source_control_type', 'integer'],
            'detected_at' => ['sometimes', 'date'],
            'description' => ['sometimes', 'string'],
            'immediate_containment' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
