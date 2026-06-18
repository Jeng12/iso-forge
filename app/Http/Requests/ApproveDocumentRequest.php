<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApproveDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'comments' => ['nullable', 'string'],
            'effective_date' => ['nullable', 'date'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
