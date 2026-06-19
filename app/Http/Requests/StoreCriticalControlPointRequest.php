<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCriticalControlPointRequest extends FormRequest
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
            'critical_limit' => ['required', 'string', 'max:255'],
            'monitoring_frequency' => ['required', 'string', 'max:255'],
            'responsible_user_id' => ['nullable', Rule::exists('users', 'id')->where('tenant_id', $tenant->id)],
            'corrective_action_procedure' => ['required', 'string'],
            'status' => ['sometimes', 'string', 'max:255'],
        ];
    }
}
