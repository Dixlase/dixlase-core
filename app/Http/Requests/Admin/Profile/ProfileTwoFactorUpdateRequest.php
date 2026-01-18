<?php

namespace App\Http\Requests\Admin\Profile;

use App\Enums\AuthenticationMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class ProfileTwoFactorUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'two_fa_mode' => ['nullable', new Enum(AuthenticationMode::class)],
            'two_fa_passkey_enabled' => 'nullable|boolean',
            'two_fa_default_method' => 'nullable|integer|in:0,1',
        ];
    }
}
