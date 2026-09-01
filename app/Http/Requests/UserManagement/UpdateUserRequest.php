<?php

namespace App\Http\Requests\UserManagement;

use App\Models\Loket;
use App\Models\User;
use App\UserRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
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
        /** @var User $user */
        $user = $this->route('user');

        return [
            'username' => ['required', 'string', 'min:3', 'max:50', 'regex:/^[a-zA-Z0-9._-]+$/', Rule::unique(User::class)->ignore($user)],
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'nip' => ['required', 'string', 'digits:18', Rule::unique(User::class)->ignore($user)],
            'role' => ['required', Rule::enum(UserRole::class)],
            'loket_id' => [
                Rule::requiredIf(fn (): bool => $this->input('role') === UserRole::PetugasLoket->value),
                'nullable',
                'integer',
                Rule::exists(Loket::class, 'id')->where('is_active', true),
            ],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * Normalize the username so login and uniqueness remain case-insensitive.
     */
    protected function prepareForValidation(): void
    {
        $username = $this->input('username');
        $nip = $this->input('nip');

        if (is_string($username)) {
            $this->merge(['username' => Str::lower($username)]);
        }

        if (is_string($nip)) {
            $this->merge(['nip' => trim($nip)]);
        }
    }
}
