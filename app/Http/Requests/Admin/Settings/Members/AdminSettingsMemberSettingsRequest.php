<?php

namespace App\Http\Requests\Admin\Settings\Members;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Enum;
use App\Enums\LoginNotificationMode;
use App\Enums\TwoFactorMode;
use App\Enums\TwoFactorMethod;

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
            'password_min_length' => 'required|integer|min:6|max:32',
            'password_require_uppercase' => 'required|boolean',
            'password_require_symbol' => 'required|boolean',
            'login_notification_mode' => ['required', new Enum(LoginNotificationMode::class)],
            'force_2fa' => ['required', new Enum(TwoFactorMode::class)],
            'two_factor_methods' => 'required|array|min:1',
            'two_factor_methods.*' => 'in:' . implode(',', array_column(TwoFactorMethod::cases(), 'value')),
            'password_reset_enabled' => 'required|boolean',
        ];
    }
}
