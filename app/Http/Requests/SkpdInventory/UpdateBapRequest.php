<?php

namespace App\Http\Requests\SkpdInventory;

use App\Models\Bap;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateBapRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $bap = $this->route('bap');

        return $bap instanceof Bap && ($this->user()?->can('update-bap', $bap) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'loket_id' => ['prohibited'],
            'service_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'numerator_start' => ['required', 'string', 'regex:/^\d{7}$/'],
            'numerator_end' => ['required', 'string', 'regex:/^\d{7}$/'],
            'online_usage_count' => ['required', 'integer', 'min:0'],
            'status' => ['prohibited'],
            'total_usage' => ['prohibited'],
        ];
    }

    /**
     * @return array<int, \Closure(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->hasAny(['numerator_start', 'numerator_end', 'online_usage_count'])) {
                return;
            }

            $numeratorStart = (int) $this->input('numerator_start');
            $numeratorEnd = (int) $this->input('numerator_end');
            $onlineUsageCount = (int) $this->input('online_usage_count');

            if ($numeratorEnd < $numeratorStart) {
                $validator->errors()->add('numerator_end', 'Nomeratur akhir harus sama dengan atau lebih besar dari nomeratur awal.');

                return;
            }

            if ($onlineUsageCount > $numeratorEnd - $numeratorStart + 1) {
                $validator->errors()->add('online_usage_count', 'Jumlah SKPD online tidak boleh melebihi total pemakaian.');
            }
        }];
    }
}
