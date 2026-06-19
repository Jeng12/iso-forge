<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAuditRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenant = $this->route('tenant');

        return [
            'title' => ['required', 'string', 'max:255'],
            'audit_type' => ['sometimes', 'string', 'max:255'],
            'iso_standard' => ['sometimes', 'string', 'max:255'],
            'scope' => ['required', 'string'],
            'lead_auditor_id' => ['nullable', Rule::exists('users', 'id')->where('tenant_id', $tenant->id)],
            'scheduled_date' => ['required', 'date'],
            'status' => ['sometimes', 'string', 'max:255'],
            'summary' => ['nullable', 'string'],
        ];
    }
}
