<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMonitoringRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenant = $this->route('tenant');

        return [
            'monitorable_type' => ['required', 'in:ccp,oprp'],
            'monitorable_id' => ['required', 'integer'],
            'recorded_by_id' => ['nullable', Rule::exists('users', 'id')->where('tenant_id', $tenant->id)],
            'measured_value' => ['nullable', 'numeric'],
            'unit' => ['nullable', 'string', 'max:50'],
            'result' => ['required', 'string', 'max:255'],
            'is_deviation' => ['required', 'boolean'],
            'observed_at' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
