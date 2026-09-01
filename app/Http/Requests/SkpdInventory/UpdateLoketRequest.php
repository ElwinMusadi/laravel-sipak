<?php

namespace App\Http\Requests\SkpdInventory;

use App\Models\Loket;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLoketRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('manage-lokets') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Loket $loket */
        $loket = $this->route('loket');

        return [
            'code' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique(Loket::class)->ignore($loket)],
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $code = $this->input('code');
        $name = $this->input('name');
        $description = $this->input('description');

        $this->merge([
            'code' => is_string($code) ? mb_strtoupper(trim($code)) : $code,
            'name' => is_string($name) ? trim($name) : $name,
            'description' => is_string($description) && trim($description) !== '' ? trim($description) : null,
        ]);
    }
}
