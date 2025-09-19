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
            'password_require_symbol' => 'required|boolean',
            'login_notification_mode' => ['required', new Enum(LoginNotificationMode::class)],
            'force_2fa' => ['required', new Enum(TwoFactorMode::class)],
            'password_reset_enabled' => 'required|boolean',
            'login_attempt_limit_enabled' => 'required|boolean',
            'login_attempt_max_attempts' => 'required|integer|min:1|max:100',
            'login_attempt_time_window' => 'required|integer|min:1|max:1440', // 最大24時間
            'login_attempt_lockout_duration' => 'required|integer|min:1|max:10080', // 最大1週間
            'lockout_notification_enabled' => 'required|boolean',
            // 管理メンバー用セッション設定
            'members_session_lifetime_enabled' => 'required|boolean',
            'members_session_lifetime' => 'required|integer|min:1|max:43200', // 最大30日
        ];

        // 二段階認証方法の設定は無効時でも保存できるようにする
        $force2fa = $this->input('force_2fa');
        if ($force2fa && $force2fa != TwoFactorMode::Disabled->value && $force2fa != TwoFactorMode::UseProfileSetting->value) {
            // 強制有効時は認証方法の選択を必須にする
            $rules['enabled_two_factor_methods'] = 'required|array|min:1';
            $rules['enabled_two_factor_methods.*'] = 'required|in:' . implode(',', array_column(TwoFactorMethod::forGlobalSettings(), 'value'));
            $rules['default_two_factor_method'] = 'nullable|in:' . implode(',', array_column(TwoFactorMethod::forGlobalSettings(), 'value'));
        } else {
            // 無効時やプロフィール設定時でも認証方法設定は保存可能
            $rules['enabled_two_factor_methods'] = 'nullable|array';
            $rules['enabled_two_factor_methods.*'] = 'nullable|in:' . implode(',', array_column(TwoFactorMethod::forGlobalSettings(), 'value'));
            $rules['default_two_factor_method'] = 'nullable|in:' . implode(',', array_column(TwoFactorMethod::forGlobalSettings(), 'value'));
        }

        return $rules;
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'enabled_two_factor_methods.required' => '二段階認証を強制有効にしている場合は、いずれかの認証方法を選択してください。',
            'enabled_two_factor_methods.min' => '二段階認証を強制有効にしている場合は、最低1つの認証方法を選択してください。',
            'default_two_factor_method.required' => '二段階認証を強制有効にしている場合は、デフォルトの認証方法を選択してください。',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $force2fa = $this->input('force_2fa');
            $enabledMethods = $this->input('enabled_two_factor_methods', []);
            $defaultMethod = $this->input('default_two_factor_method');
            
            // 有効な認証方法が1つだけの場合は、デフォルト方法のバリデーションをスキップ
            // （コントローラー側で自動的に設定されるため）
            if (!empty($enabledMethods) && count($enabledMethods) === 1) {
                return; // バリデーションエラーを出さずに通す
            }
            
            // 二段階認証が強制有効な場合のみバリデーション
            if ($force2fa && $force2fa != TwoFactorMode::Disabled->value && $force2fa != TwoFactorMode::UseProfileSetting->value) {
                // デフォルトの二段階認証方法が有効な方法の中に含まれているかチェック
                if ($defaultMethod && !in_array($defaultMethod, $enabledMethods)) {
                    $validator->errors()->add('default_two_factor_method', 
                        'デフォルトの二段階認証方法は、有効な認証方法の中から選択してください。');
                }
            }
            
            // 無効時でも認証方法が選択されている場合はデフォルト方法の整合性をチェック
            if (($force2fa == TwoFactorMode::Disabled->value || $force2fa == TwoFactorMode::UseProfileSetting->value) && !empty($enabledMethods) && $defaultMethod) {
                if (!in_array($defaultMethod, $enabledMethods)) {
                    $validator->errors()->add('default_two_factor_method', 
                        'デフォルトの二段階認証方法は、有効な認証方法の中から選択してください。');
                }
            }
            
            // Mail server validation has been relaxed for all features
            // All mail-dependent features (password reset, login notifications, 2FA) 
            // can now be configured regardless of mail server test status
            // Features will display warnings but allow configuration
        });
    }
}
