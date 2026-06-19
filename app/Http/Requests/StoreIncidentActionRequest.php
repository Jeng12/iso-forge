<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIncidentActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenant = $this->route('tenant');

        return [
            'action_type' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'responsible_user_id' => ['nullable', Rule::exists('users', 'id')->where('tenant_id', $tenant->id)],
            'due_date' => ['nullable', 'date'],
            'completed_at' => ['nullable', 'date'],
            'status' => ['sometimes', 'string', 'max:255'],
        ];
    }
}
