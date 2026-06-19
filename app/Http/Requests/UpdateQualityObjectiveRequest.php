<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateQualityObjectiveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenant = $this->route('tenant');

        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'iso_clause' => ['sometimes', 'nullable', 'string', 'max:255'],
            'baseline_value' => ['sometimes', 'nullable', 'numeric'],
            'target_value' => ['sometimes', 'numeric'],
            'current_value' => ['sometimes', 'nullable', 'numeric'],
            'unit' => ['sometimes', 'nullable', 'string', 'max:50'],
            'measurement_method' => ['sometimes', 'string', 'max:255'],
            'owner_id' => ['sometimes', 'nullable', Rule::exists('users', 'id')->where('tenant_id', $tenant->id)],
            'due_date' => ['sometimes', 'nullable', 'date'],
            'status' => ['sometimes', 'string', 'max:255'],
        ];
    }
}
