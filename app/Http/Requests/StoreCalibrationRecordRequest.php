<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCalibrationRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenant = $this->route('tenant');

        return [
            'performed_by_id' => ['nullable', Rule::exists('users', 'id')->where('tenant_id', $tenant->id)],
            'evidence_document_id' => ['nullable', Rule::exists('documents', 'id')->where('tenant_id', $tenant->id)],
            'performed_at' => ['required', 'date'],
            'due_at' => ['required', 'date'],
            'result' => ['required', 'in:Pass,Adjusted,Fail,Overdue'],
            'certificate_number' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
