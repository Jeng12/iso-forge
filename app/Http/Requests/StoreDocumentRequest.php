<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenant = $this->route('tenant');

        return [
            'document_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('documents')->where('tenant_id', $tenant->id),
            ],
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:255'],
            'owner_id' => ['required', Rule::exists('users', 'id')->where('tenant_id', $tenant->id)],
            'version_number' => ['required', 'string', 'max:50'],
            'file' => ['nullable', 'file', 'max:20480'],
            'file_path' => ['required_without:file', 'nullable', 'string', 'max:255'],
            'mime_type' => ['nullable', 'string', 'max:255'],
            'file_size' => ['nullable', 'integer', 'min:0'],
            'retention_until' => ['nullable', 'date'],
            'change_summary' => ['nullable', 'string'],
            'approver_ids' => ['nullable', 'array'],
            'approver_ids.*' => [Rule::exists('users', 'id')->where('tenant_id', $tenant->id)],
        ];
    }
}
