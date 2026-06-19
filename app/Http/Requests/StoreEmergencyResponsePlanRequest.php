<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmergencyResponsePlanRequest extends FormRequest
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
            'scenario' => ['required', 'string'],
            'owner_id' => ['nullable', Rule::exists('users', 'id')->where('tenant_id', $tenant->id)],
            'related_document_id' => ['nullable', Rule::exists('documents', 'id')->where('tenant_id', $tenant->id)],
            'review_frequency_days' => ['sometimes', 'integer', 'min:1', 'max:3660'],
            'last_reviewed_at' => ['nullable', 'date'],
            'next_review_due_at' => ['nullable', 'date'],
            'response_steps' => ['nullable', 'array'],
            'response_steps.*' => ['string', 'max:255'],
            'status' => ['sometimes', 'string', 'max:255'],
        ];
    }
}
