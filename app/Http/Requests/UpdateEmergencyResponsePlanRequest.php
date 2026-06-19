<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmergencyResponsePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenant = $this->route('tenant');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'scenario' => ['sometimes', 'string'],
            'owner_id' => ['sometimes', 'nullable', Rule::exists('users', 'id')->where('tenant_id', $tenant->id)],
            'related_document_id' => ['sometimes', 'nullable', Rule::exists('documents', 'id')->where('tenant_id', $tenant->id)],
            'review_frequency_days' => ['sometimes', 'integer', 'min:1', 'max:3660'],
            'last_reviewed_at' => ['sometimes', 'nullable', 'date'],
            'next_review_due_at' => ['sometimes', 'nullable', 'date'],
            'response_steps' => ['sometimes', 'nullable', 'array'],
            'response_steps.*' => ['string', 'max:255'],
            'status' => ['sometimes', 'string', 'max:255'],
        ];
    }
}
