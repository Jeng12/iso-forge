<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDocumentVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $document = $this->route('document');

        return [
            'version_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('document_versions')->where('document_id', $document->id),
            ],
            'file' => ['nullable', 'file', 'max:20480'],
            'file_path' => ['required_without:file', 'nullable', 'string', 'max:255'],
            'mime_type' => ['nullable', 'string', 'max:255'],
            'file_size' => ['nullable', 'integer', 'min:0'],
            'change_summary' => ['nullable', 'string'],
            'approver_ids' => ['nullable', 'array'],
            'approver_ids.*' => [Rule::exists('users', 'id')->where('tenant_id', $document->tenant_id)],
        ];
    }
}
