<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupplierCertificateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenant = $this->route('tenant');

        return [
            'document_id' => ['nullable', Rule::exists('documents', 'id')->where('tenant_id', $tenant->id)],
            'certificate_type' => ['required', 'string', 'max:255'],
            'certificate_number' => ['required', 'string', 'max:255'],
            'issued_at' => ['nullable', 'date'],
            'expires_at' => ['required', 'date'],
            'status' => ['sometimes', 'string', 'max:255'],
        ];
    }
}
