<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmergencyDrillRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenant = $this->route('tenant');

        return [
            'facilitator_id' => ['nullable', Rule::exists('users', 'id')->where('tenant_id', $tenant->id)],
            'scheduled_at' => ['nullable', 'date'],
            'completed_at' => ['required', 'date'],
            'result' => ['required', 'in:Effective,Needs Improvement,Failed'],
            'participants_count' => ['sometimes', 'integer', 'min:0', 'max:10000'],
            'effectiveness_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'scenario_notes' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
