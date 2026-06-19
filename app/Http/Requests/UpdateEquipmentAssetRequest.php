<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEquipmentAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenant = $this->route('tenant');
        $equipmentAsset = $this->route('equipmentAsset');

        return [
            'asset_tag' => ['sometimes', 'string', 'max:255', Rule::unique('equipment_assets')->where('tenant_id', $tenant->id)->ignore($equipmentAsset?->id)],
            'name' => ['sometimes', 'string', 'max:255'],
            'location' => ['sometimes', 'string', 'max:255'],
            'owner_id' => ['sometimes', 'nullable', Rule::exists('users', 'id')->where('tenant_id', $tenant->id)],
            'calibration_interval_days' => ['sometimes', 'integer', 'min:1', 'max:3660'],
            'critical_to_food_safety' => ['sometimes', 'boolean'],
            'last_calibrated_at' => ['sometimes', 'nullable', 'date'],
            'next_calibration_due_at' => ['sometimes', 'nullable', 'date'],
            'status' => ['sometimes', 'string', 'max:255'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
