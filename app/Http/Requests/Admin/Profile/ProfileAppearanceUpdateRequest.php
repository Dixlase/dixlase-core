<?php

namespace App\Http\Requests\Admin\Profile;

use App\Enums\AppearanceMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class ProfileAppearanceUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'appearance' => ['nullable', new Enum(AppearanceMode::class)],
        ];
    }
}
