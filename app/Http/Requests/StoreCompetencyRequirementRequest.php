<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCompetencyRequirementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenant = $this->route('tenant');

        return [
            'role_id' => ['required', Rule::exists('roles', 'id')->where('tenant_id', $tenant->id)],
            'training_program_id' => ['required', Rule::exists('training_programs', 'id')->where('tenant_id', $tenant->id)],
            'competency_area' => ['required', 'string', 'max:255'],
            'required_level' => ['sometimes', 'string', 'max:255'],
            'assessment_method' => ['sometimes', 'string', 'max:255'],
            'due_within_days' => ['sometimes', 'integer', 'min:1', 'max:3660'],
            'is_mandatory' => ['sometimes', 'boolean'],
        ];
    }
}
