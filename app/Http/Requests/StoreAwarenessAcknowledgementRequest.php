<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAwarenessAcknowledgementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenant = $this->route('tenant');

        return [
            'document_id' => ['required', Rule::exists('documents', 'id')->where('tenant_id', $tenant->id)],
            'user_id' => ['required', Rule::exists('users', 'id')->where('tenant_id', $tenant->id)],
            'acknowledged_by_id' => ['nullable', Rule::exists('users', 'id')->where('tenant_id', $tenant->id)],
            'acknowledged_at' => ['nullable', 'date'],
            'status' => ['sometimes', 'string', 'max:255'],
            'statement' => ['nullable', 'string'],
        ];
    }
}
