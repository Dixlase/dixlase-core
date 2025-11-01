<?php

namespace App\Http\Requests\Admin\Settings\Members;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Enum;
use App\Enums\LoginNotificationMode;
use App\Enums\TwoFactorMode;
use App\Enums\TwoFactorMethod;
use App\Models\BaseSetting;

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
        $rules = [
            'password_min_length' => 'required|integer|min:6|max:32',
            'password_require_uppercase' => 'required|boolean',
            'password_require_number' => 'required|boolean',
            'password_require_symbol' => 'required|boolean',
            'login_notification_mode' => ['required', new Enum(LoginNotificationMode::class)],
            'force_2fa' => ['required', new Enum(TwoFactorMode::class)],
            'password_reset_enabled' => 'required|boolean',
            'pwned_password_check_enabled' => 'required|boolean',
            'login_attempt_limit_enabled' => 'required|boolean',
            'login_attempt_max_attempts' => 'required|integer|min:1|max:100',
            'login_attempt_time_window' => 'required|integer|min:1|max:1440', // 最大24時間
            'login_attempt_lockout_duration' => 'required|integer|min:1|max:10080', // 最大1週間
            'lockout_notification_enabled' => 'required|boolean',
            // 管理メンバー用セッション設定
            'members_session_lifetime_enabled' => 'required|boolean',
            'members_session_lifetime' => 'required|integer|min:1|max:43200', // 最大30日
            // 二段階認証の有効期限設定
            'two_factor_expire_minutes' => 'required|integer|min:1|max:60', // 1-60分（メール認証）
            'two_factor_resend_interval_seconds' => 'required|integer|min:60|max:600', // 60-600秒（1-10分）
            // Passkey有効/無効設定（メール認証は常に有効）
            'passkey_enabled' => 'nullable|boolean',
        ];

        return $rules;
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            // メッセージは不要（Passkeyは単純なチェックボックス）
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        // バリデーション後の追加チェックは不要（メール認証は常に有効、Passkeyは単純なチェックボックス）
    }
}
