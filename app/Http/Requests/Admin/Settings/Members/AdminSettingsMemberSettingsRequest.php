<?php

namespace App\Http\Requests\Admin\Settings\Members;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Enum;
use App\Enums\AuthenticationMode;
use App\Enums\TwoFaMethod;
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
            'login_attempt_lockout_notification_enabled' => filter_var($this->input('login_attempt_lockout_notification_enabled'), FILTER_VALIDATE_BOOLEAN),
            // パスワードリセット機能設定
            'password_reset_enabled' => filter_var($this->input('password_reset_enabled'), FILTER_VALIDATE_BOOLEAN),
            // 管理メンバー用セッション設定
            'members_session_lifetime_enabled' => filter_var($this->input('members_session_lifetime_enabled'), FILTER_VALIDATE_BOOLEAN),
            // 二段階認証設定
            'two_fa_lockout_notification_enabled' => filter_var($this->input('two_fa_lockout_notification_enabled'), FILTER_VALIDATE_BOOLEAN),
            // CAPTCHA設定
            'captcha_admin_login_enabled' => filter_var($this->input('captcha_admin_login_enabled'), FILTER_VALIDATE_BOOLEAN),
            'captcha_password_reset_enabled' => filter_var($this->input('captcha_password_reset_enabled'), FILTER_VALIDATE_BOOLEAN),
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
        ];

        $sessionRules = [
            'members_session_lifetime_enabled' => 'required|boolean',
            'members_session_lifetime' => 'required|integer|min:1|max:43200', // 最大30日
        ];

        $authenticationRules = [
            'login_notification_mode' => ['required', new Enum(AuthenticationMode::class)],
            'login_attempt_limit_enabled' => 'required|boolean',
            'login_attempt_max_attempts' => 'required|integer|min:1|max:100',
            'login_attempt_max_attempts_ip' => 'required|integer|min:1|max:100',
            'login_attempt_time_window' => 'required|integer|min:1|max:1440', // 最大24時間
            'login_attempt_lockout_duration' => 'required|integer|min:1|max:10080', // 最大1週間
            'login_attempt_lockout_notification_enabled' => 'required|boolean',
            'two_fa_force_mode' => ['required', new Enum(AuthenticationMode::class)],
            'two_fa_passkey_mode' => 'required|integer|in:0,1,2', // 0=無効, 1=有効, 2=プロフィール設定に従う
            'two_fa_default_method' => 'nullable|integer|in:0,1', // 0=メール, 1=パスキー（パスキー有効時のみ）
            'two_fa_expire_minutes' => 'required|integer|min:1|max:60', // 1-60分（メール認証）
            'two_fa_resend_interval_seconds' => 'required|integer|min:60|max:600', // 60-600秒（1-10分）
            'two_fa_max_attempts' => 'required|integer|min:1|max:10',
            'two_fa_attempt_window' => 'required|integer|min:5|max:60',
            'two_fa_lockout_duration' => 'required|integer|min:5|max:1440',
            'two_fa_lockout_notification_enabled' => 'required|boolean',
            'two_fa_recovery_codes_count' => 'required|integer|min:1|max:10',
            'two_fa_recovery_code_regenerate_interval' => 'required|integer|min:1|max:168', // 1-168時間（1時間-7日間）
            'captcha_admin_login_enabled' => 'nullable|boolean',
            'captcha_password_reset_enabled' => 'nullable|boolean',
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
