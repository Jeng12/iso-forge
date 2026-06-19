<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupplierEvaluationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenant = $this->route('tenant');

        return [
            'evaluated_by_id' => ['nullable', Rule::exists('users', 'id')->where('tenant_id', $tenant->id)],
            'evaluation_date' => ['required', 'date'],
            'score' => ['required', 'integer', 'min:0', 'max:100'],
            'result' => ['required', 'in:Approved,Conditional,Rejected'],
            'next_review_date' => ['nullable', 'date'],
            'evidence_document_id' => ['nullable', Rule::exists('documents', 'id')->where('tenant_id', $tenant->id)],
            'notes' => ['nullable', 'string'],
        ];
    }
}
