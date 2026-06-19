<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTrainingAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenant = $this->route('tenant');

        return [
            'user_id' => ['sometimes', Rule::exists('users', 'id')->where('tenant_id', $tenant->id)],
            'assigned_by_id' => ['sometimes', 'nullable', Rule::exists('users', 'id')->where('tenant_id', $tenant->id)],
            'required_for_role_id' => ['sometimes', 'nullable', Rule::exists('roles', 'id')->where('tenant_id', $tenant->id)],
            'due_date' => ['sometimes', 'date'],
            'status' => ['sometimes', 'string', 'max:255'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
