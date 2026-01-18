<?php

namespace App\Http\Requests\Admin\Profile;

use App\Enums\AuthenticationMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class ProfileNotificationsUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'login_notification_mode' => ['nullable', new Enum(AuthenticationMode::class)],
        ];
    }
}
