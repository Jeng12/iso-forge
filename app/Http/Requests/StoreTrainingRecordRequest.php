<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTrainingRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenant = $this->route('tenant');

        return [
            'trainer_id' => ['nullable', Rule::exists('users', 'id')->where('tenant_id', $tenant->id)],
            'evidence_document_id' => ['nullable', Rule::exists('documents', 'id')->where('tenant_id', $tenant->id)],
            'completed_at' => ['required', 'date'],
            'score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'result' => ['required', 'in:Pass,Fail'],
            'competency_status' => ['required', 'in:Competent,Needs Coaching'],
            'expires_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
