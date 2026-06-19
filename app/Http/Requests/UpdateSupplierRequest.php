<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenant = $this->route('tenant');
        $supplier = $this->route('supplier');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'supplier_code' => ['sometimes', 'string', 'max:255', Rule::unique('suppliers')->where('tenant_id', $tenant->id)->ignore($supplier?->id)],
            'category' => ['sometimes', 'string', 'max:255'],
            'contact_email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'approval_status' => ['sometimes', 'string', 'max:255'],
            'risk_level' => ['sometimes', 'string', 'max:255'],
            'approved_until' => ['sometimes', 'nullable', 'date'],
            'owner_id' => ['sometimes', 'nullable', Rule::exists('users', 'id')->where('tenant_id', $tenant->id)],
            'risk_id' => ['sometimes', 'nullable', Rule::exists('risks', 'id')->where('tenant_id', $tenant->id)],
            'notes' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
