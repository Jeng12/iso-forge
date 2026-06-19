<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIncidentReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenant = $this->route('tenant');

        return [
            'reference' => ['required', 'string', 'max:255', Rule::unique('incident_reports')->where('tenant_id', $tenant->id)],
            'title' => ['required', 'string', 'max:255'],
            'incident_type' => ['required', 'string', 'max:255'],
            'severity' => ['required', 'in:Minor,Major,Critical'],
            'status' => ['sometimes', 'string', 'max:255'],
            'reported_by_id' => ['nullable', Rule::exists('users', 'id')->where('tenant_id', $tenant->id)],
            'owner_id' => ['nullable', Rule::exists('users', 'id')->where('tenant_id', $tenant->id)],
            'source_control_type' => ['nullable', 'required_with:source_control_id', 'in:ccp,oprp,prp'],
            'source_control_id' => ['nullable', 'required_with:source_control_type', 'integer'],
            'detected_at' => ['required', 'date'],
            'description' => ['required', 'string'],
            'immediate_containment' => ['nullable', 'string'],
        ];
    }
}
