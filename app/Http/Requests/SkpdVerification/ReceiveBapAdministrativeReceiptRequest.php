<?php

namespace App\Http\Requests\SkpdVerification;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ReceiveBapAdministrativeReceiptRequest extends FormRequest
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
            'receipt_notes' => ['nullable', 'string', 'max:2000'],
            'received_at' => ['prohibited'],
            'received_by' => ['prohibited'],
            'receipt_date' => ['prohibited'],
            'bap_id' => ['prohibited'],
            'status' => ['prohibited'],
            'loket_id' => ['prohibited'],
            'service_date' => ['prohibited'],
            'numerator_start' => ['prohibited'],
            'numerator_end' => ['prohibited'],
            'total_usage' => ['prohibited'],
            'online_usage_count' => ['prohibited'],
            'usage_segments' => ['prohibited'],
            'cancellations' => ['prohibited'],
            'verifications' => ['prohibited'],
            'clarifications' => ['prohibited'],
        ];
    }
}
