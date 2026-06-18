<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RequestDocumentApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenant = $this->route('tenant');

        return [
            'approver_ids' => ['required', 'array', 'min:1'],
            'approver_ids.*' => [Rule::exists('users', 'id')->where('tenant_id', $tenant->id)],
        ];
    }
}
