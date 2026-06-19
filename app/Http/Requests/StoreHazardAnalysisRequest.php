<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreHazardAnalysisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'hazard_type' => ['required', 'string', 'max:255'],
            'hazard_description' => ['required', 'string'],
            'likelihood' => ['required', 'integer', 'min:1', 'max:5'],
            'severity' => ['required', 'integer', 'min:1', 'max:5'],
            'control_measure' => ['required', 'string'],
            'control_type' => ['required', 'in:CCP,OPRP,PRP,None'],
            'status' => ['sometimes', 'string', 'max:255'],
        ];
    }
}
