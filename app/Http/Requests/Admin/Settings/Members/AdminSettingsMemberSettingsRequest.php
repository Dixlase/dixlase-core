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
    private function settingsSection(): ?string
    {
        $section = $this->input('settings_section');

        if ($this->routeIs('admin.members.settings.password.update')) {
            return 'password';
        }
        if ($this->routeIs('admin.members.settings.session.update')) {
            return 'session';
        }
        if ($this->routeIs('admin.members.settings.auth.update')) {
            return 'auth';
        }

        return $section;
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::guard('member')->check(); // 管理者のみ許可など必要に応じて
    }

    /**
     * Prepare the data for validation.
     * セキュリティ設定と同じ方法でcheckbox/toggleフィールドをboolean変換
     */
    public function prepareForValidation()
    {
        $this->merge([
            // パスワード条件設定
            'password_require_uppercase' => filter_var($this->input('password_require_uppercase'), FILTER_VALIDATE_BOOLEAN),
            'password_require_number' => filter_var($this->input('password_require_number'), FILTER_VALIDATE_BOOLEAN),
            'password_require_symbol' => filter_var($this->input('password_require_symbol'), FILTER_VALIDATE_BOOLEAN),
            // ログイン試行制限設定
            'login_attempt_limit_enabled' => filter_var($this->input('login_attempt_limit_enabled'), FILTER_VALIDATE_BOOLEAN),
            'lockout_notification_enabled' => filter_var($this->input('lockout_notification_enabled'), FILTER_VALIDATE_BOOLEAN),
            // パスワードリセット機能設定
            'password_reset_enabled' => filter_var($this->input('password_reset_enabled'), FILTER_VALIDATE_BOOLEAN),
            // パスワード辞書攻撃対策設定
            'pwned_password_check_enabled' => filter_var($this->input('pwned_password_check_enabled'), FILTER_VALIDATE_BOOLEAN),
            // 管理メンバー用セッション設定
            'members_session_lifetime_enabled' => filter_var($this->input('members_session_lifetime_enabled'), FILTER_VALIDATE_BOOLEAN),
            // 二段階認証設定
            'enabled_2fa_passkey' => filter_var($this->input('enabled_2fa_passkey'), FILTER_VALIDATE_BOOLEAN),
            '2fa_lockout_notification_enabled' => filter_var($this->input('2fa_lockout_notification_enabled'), FILTER_VALIDATE_BOOLEAN),
            // CAPTCHA設定（管理画面ログイン用）
            'captcha_admin_login_enabled' => filter_var($this->input('captcha_admin_login_enabled'), FILTER_VALIDATE_BOOLEAN),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $section = $this->settingsSection();

        $passwordRules = [
            'password_min_length' => 'required|integer|min:6|max:32',
            'password_require_uppercase' => 'required|boolean',
            'password_require_number' => 'required|boolean',
            'password_require_symbol' => 'required|boolean',
            'password_reset_enabled' => 'required|boolean',
            'pwned_password_check_enabled' => 'required|boolean',
        ];

        $sessionRules = [
            'members_session_lifetime_enabled' => 'required|boolean',
            'members_session_lifetime' => 'required|integer|min:1|max:43200', // 最大30日
        ];

        $authenticationRules = [
            'login_notification_mode' => ['required', new Enum(LoginNotificationMode::class)],
            'login_attempt_limit_enabled' => 'required|boolean',
            'login_attempt_max_attempts' => 'required|integer|min:1|max:100',
            'login_attempt_time_window' => 'required|integer|min:1|max:1440', // 最大24時間
            'login_attempt_lockout_duration' => 'required|integer|min:1|max:10080', // 最大1週間
            'lockout_notification_enabled' => 'required|boolean',
            'force_2fa' => ['required', new Enum(TwoFactorMode::class)],
            'two_factor_expire_minutes' => 'required|integer|min:1|max:60', // 1-60分（メール認証）
            'two_factor_resend_interval_seconds' => 'required|integer|min:60|max:600', // 60-600秒（1-10分）
            'enabled_2fa_passkey' => 'nullable|boolean',
            '2fa_max_attempts' => 'required|integer|min:1|max:10',
            '2fa_attempt_window' => 'required|integer|min:5|max:60',
            '2fa_lockout_duration' => 'required|integer|min:5|max:1440',
            '2fa_lockout_notification_enabled' => 'required|boolean',
            'recovery_codes_count' => 'required|integer|min:1|max:10',
            'recovery_code_regenerate_interval' => 'required|integer|min:1|max:168', // 1-168時間（1時間-7日間）
            'captcha_admin_login_enabled' => 'nullable|boolean',
        ];

        return match ($section) {
            'password' => $passwordRules,
            'session' => $sessionRules,
            'auth' => $authenticationRules,
            default => array_merge($passwordRules, $sessionRules, $authenticationRules),
        };
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
