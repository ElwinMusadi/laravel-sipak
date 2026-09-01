<?php

namespace App\Http\Requests\SkpdInventory;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSkpdBoxRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('manage-skpd-inventory') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'box_number' => ['required', 'string', 'max:50'],
            'central_storage_location' => ['required', 'string', 'max:100'],
            'received_at' => ['required', 'date_format:Y-m-d'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $boxNumber = $this->input('box_number');
        $centralStorageLocation = $this->input('central_storage_location');

        $this->merge([
            'box_number' => is_string($boxNumber) ? trim($boxNumber) : $boxNumber,
            'central_storage_location' => is_string($centralStorageLocation) ? trim($centralStorageLocation) : $centralStorageLocation,
        ]);
    }
}
