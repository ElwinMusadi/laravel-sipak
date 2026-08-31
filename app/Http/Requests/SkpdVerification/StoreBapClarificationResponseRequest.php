<?php

namespace App\Http\Requests\SkpdVerification;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreBapClarificationResponseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'response' => ['required', 'string', 'max:2000'],
            'status' => ['prohibited'],
            'notes' => ['prohibited'],
            'outcome' => ['prohibited'],
            'expected_value' => ['prohibited'],
            'actual_value' => ['prohibited'],
            'difference' => ['prohibited'],
        ];
    }
}
