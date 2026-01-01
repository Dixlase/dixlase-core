<?php

namespace App\Http\Requests\Admin\Profile;

use App\Enums\AppearanceMode;
use App\Enums\Locale;
use App\Enums\LoginNotificationMode;
use App\Enums\TwoFactorMethod;
use App\Enums\TwoFactorMode;
use App\Models\MemberSetting;
use App\Services\PasswordValidationService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Enum;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // プロフィール更新は認証済みユーザーのみアクセス可能
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $member = Auth::guard('member')->user();
        
        // パスワード設定を取得
        $passwordMinLength = (int) MemberSetting::getValue('password_min_length', 8);
        $passwordRequireUppercase = (bool) MemberSetting::getValue('password_require_uppercase', true);
        $passwordRequireLowercase = (bool) MemberSetting::getValue('password_require_lowercase', true);
        $passwordRequireNumber = (bool) MemberSetting::getValue('password_require_number', true);
        $passwordRequireSymbol = (bool) MemberSetting::getValue('password_require_symbol', false);
        $passwordCheckPwned = (bool) MemberSetting::getValue('password_check_pwned', false);

        // パスワードバリデーションルールを構築（任意入力）
        $passwordRules = PasswordValidationService::buildPasswordRules(
            $passwordMinLength,
            $passwordRequireUppercase,
            $passwordRequireLowercase,
            $passwordRequireNumber,
            $passwordRequireSymbol,
            false, // プロフィール更新時は任意
            $passwordCheckPwned
        );

        $rules = [
            'account_name' => 'required|string|alpha_num|min:3|max:20',
            'member_name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'email' => 'required|string|email|max:255|unique:members,email,' . $member->id,
            'locale' => 'nullable|string|in:' . implode(',', Locale::values()),
            'password' => $passwordRules,
            'appearance' => ['nullable', new Enum(AppearanceMode::class)],
            'login_notification_mode' => ['nullable', new Enum(LoginNotificationMode::class)],
            'two_factor_mode' => ['nullable', new Enum(TwoFactorMode::class)],
            'two_factor_method' => 'nullable|integer',
        ];

        // メールアドレスが変更された場合は確認フィールドを必須に
        if ($this->input('email') !== $member->email) {
            $rules['email_confirmation'] = 'required|email|same:email';
        }

        // 二段階認証方法のバリデーション（有効な方法の中から選択されているかチェック）
        $passkeyEnabledForValidation = MemberSetting::getValue('enabled_2fa_passkey', '0') === '1';
        $enabledTwoFactorMethods = [TwoFactorMethod::EMAIL->value];
        if ($passkeyEnabledForValidation) {
            $enabledTwoFactorMethods[] = TwoFactorMethod::PASSKEY->value;
        }
        $force2faValue = (int) MemberSetting::getValue('force_2fa', TwoFactorMode::UseProfileSetting->value);
        
        // フィールドが表示・編集可能な場合のみ認証方法選択をバリデーション
        // UseProfileSettingの場合のみフィールドが編集可能（Alwaysの場合は表示のみまたは非表示）
        if ($force2faValue === TwoFactorMode::UseProfileSetting->value && !empty($enabledTwoFactorMethods)) {
            // 有効な認証方法が1つだけの場合はその方法を強制
            if (count($enabledTwoFactorMethods) === 1) {
                $rules['two_factor_method'] = 'required|integer|in:' . $enabledTwoFactorMethods[0];
            } else {
                $rules['two_factor_method'] = 'required|integer|in:' . implode(',', $enabledTwoFactorMethods);
            }
        }

        return $rules;
    }
}
