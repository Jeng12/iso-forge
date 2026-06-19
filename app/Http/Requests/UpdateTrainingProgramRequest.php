<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTrainingProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenant = $this->route('tenant');
        $trainingProgram = $this->route('trainingProgram');

        return [
            'code' => ['sometimes', 'string', 'max:255', Rule::unique('training_programs')->where('tenant_id', $tenant->id)->ignore($trainingProgram?->id)],
            'title' => ['sometimes', 'string', 'max:255'],
            'iso_clause' => ['sometimes', 'nullable', 'string', 'max:255'],
            'delivery_method' => ['sometimes', 'string', 'max:255'],
            'owner_id' => ['sometimes', 'nullable', Rule::exists('users', 'id')->where('tenant_id', $tenant->id)],
            'refresher_interval_days' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:3660'],
            'status' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
