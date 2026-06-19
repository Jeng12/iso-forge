<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIncidentActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenant = $this->route('tenant');

        return [
            'action_type' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
            'responsible_user_id' => ['sometimes', 'nullable', Rule::exists('users', 'id')->where('tenant_id', $tenant->id)],
            'due_date' => ['sometimes', 'nullable', 'date'],
            'completed_at' => ['sometimes', 'nullable', 'date'],
            'status' => ['sometimes', 'string', 'max:255'],
        ];
    }
}
