<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAuditFindingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenant = $this->route('tenant');

        return [
            'reference' => [
                'required',
                'string',
                'max:255',
                Rule::unique('audit_findings')->where('tenant_id', $tenant->id),
            ],
            'non_conformance_id' => ['nullable', Rule::exists('non_conformances', 'id')->where('tenant_id', $tenant->id)],
            'iso_clause' => ['required', 'string', 'max:255'],
            'finding_type' => ['required', 'string', 'max:255'],
            'severity' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'evidence' => ['nullable', 'string'],
            'owner_id' => ['nullable', Rule::exists('users', 'id')->where('tenant_id', $tenant->id)],
            'due_date' => ['nullable', 'date'],
            'status' => ['sometimes', 'string', 'max:255'],
        ];
    }
}
