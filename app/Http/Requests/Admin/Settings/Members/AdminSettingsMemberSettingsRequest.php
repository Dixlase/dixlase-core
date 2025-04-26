<?php

namespace App\Http\Requests\Admin\Settings\Members;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class AdminSettingsMemberSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::guard('member')->check(); // 管理者のみ許可など必要に応じて
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'force_2fa' => ['required', new \Illuminate\Validation\Rules\Enum(\App\Enums\TwoFactorMode::class)],
            'login_notification_mode' => ['required', new \Illuminate\Validation\Rules\Enum(\App\Enums\LoginNotificationMode::class)],
            'password_min_length' => ['required', 'integer', 'in:8,12,16'],
            'password_require_uppercase' => ['required', 'boolean'],
            'password_require_symbol' => ['required', 'boolean'],
        ];
    }
}
