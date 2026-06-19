<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenant = $this->route('tenant');

        return [
            'name' => ['required', 'string', 'max:255'],
            'supplier_code' => ['required', 'string', 'max:255', Rule::unique('suppliers')->where('tenant_id', $tenant->id)],
            'category' => ['required', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'approval_status' => ['sometimes', 'string', 'max:255'],
            'risk_level' => ['sometimes', 'string', 'max:255'],
            'approved_until' => ['nullable', 'date'],
            'owner_id' => ['nullable', Rule::exists('users', 'id')->where('tenant_id', $tenant->id)],
            'risk_id' => ['nullable', Rule::exists('risks', 'id')->where('tenant_id', $tenant->id)],
            'notes' => ['nullable', 'string'],
        ];
    }
}
