<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreManagementReviewRequest extends FormRequest
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
            'review_date' => ['required', 'date'],
            'chair_id' => ['nullable', Rule::exists('users', 'id')->where('tenant_id', $tenant->id)],
            'inputs' => ['nullable', 'array'],
            'decisions' => ['nullable', 'array'],
            'actions' => ['nullable', 'array'],
            'status' => ['sometimes', 'string', 'max:255'],
        ];
    }
}
