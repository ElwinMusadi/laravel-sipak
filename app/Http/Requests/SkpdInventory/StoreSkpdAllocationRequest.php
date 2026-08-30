<?php

namespace App\Http\Requests\SkpdInventory;

use App\Models\Loket;
use App\Models\SkpdBox;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreSkpdAllocationRequest extends FormRequest
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
            'skpd_box_id' => ['required', 'integer', Rule::exists(SkpdBox::class, 'id')],
            'loket_id' => ['required', 'integer', Rule::exists(Loket::class, 'id')],
            'numerator_start' => ['required', 'string', 'regex:/^\d{7}$/'],
            'numerator_end' => ['required', 'string', 'regex:/^\d{7}$/'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['numerator_start', 'numerator_end'])) {
                    return;
                }

                if ((int) $this->input('numerator_end') <= (int) $this->input('numerator_start')) {
                    $validator->errors()->add('numerator_end', 'Nomeratur akhir harus lebih besar dari nomeratur awal.');
                }
            },
        ];
    }
}
