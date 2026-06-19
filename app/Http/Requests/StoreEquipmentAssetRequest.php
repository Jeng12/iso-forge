<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEquipmentAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenant = $this->route('tenant');

        return [
            'asset_tag' => ['required', 'string', 'max:255', Rule::unique('equipment_assets')->where('tenant_id', $tenant->id)],
            'name' => ['required', 'string', 'max:255'],
            'location' => ['required', 'string', 'max:255'],
            'owner_id' => ['nullable', Rule::exists('users', 'id')->where('tenant_id', $tenant->id)],
            'calibration_interval_days' => ['sometimes', 'integer', 'min:1', 'max:3660'],
            'critical_to_food_safety' => ['sometimes', 'boolean'],
            'last_calibrated_at' => ['nullable', 'date'],
            'next_calibration_due_at' => ['nullable', 'date'],
            'status' => ['sometimes', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
