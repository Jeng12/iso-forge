<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQualityObjectiveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenant = $this->route('tenant');

        return [
            'title' => ['required', 'string', 'max:255'],
            'iso_clause' => ['sometimes', 'string', 'max:255'],
            'baseline_value' => ['nullable', 'numeric'],
            'target_value' => ['required', 'numeric'],
            'current_value' => ['nullable', 'numeric'],
            'unit' => ['sometimes', 'string', 'max:50'],
            'measurement_method' => ['required', 'string', 'max:255'],
            'owner_id' => ['nullable', Rule::exists('users', 'id')->where('tenant_id', $tenant->id)],
            'due_date' => ['nullable', 'date'],
            'status' => ['sometimes', 'string', 'max:255'],
        ];
    }
}
