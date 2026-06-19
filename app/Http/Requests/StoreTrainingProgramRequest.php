<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTrainingProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenant = $this->route('tenant');

        return [
            'code' => ['required', 'string', 'max:255', Rule::unique('training_programs')->where('tenant_id', $tenant->id)],
            'title' => ['required', 'string', 'max:255'],
            'iso_clause' => ['nullable', 'string', 'max:255'],
            'delivery_method' => ['sometimes', 'string', 'max:255'],
            'owner_id' => ['nullable', Rule::exists('users', 'id')->where('tenant_id', $tenant->id)],
            'refresher_interval_days' => ['nullable', 'integer', 'min:1', 'max:3660'],
            'status' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ];
    }
}
