<?php

namespace App\Http\Requests\Admin\Profile;

use App\Models\MemberSetting;
use App\Services\PasswordService;
use Illuminate\Foundation\Http\FormRequest;

class ProfilePasswordUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // パスワード設定を取得
        $passwordMinLength = (int) MemberSetting::getValue('password_min_length', 8);
        $passwordRequireUppercase = (bool) MemberSetting::getValue('password_require_uppercase', true);
        $passwordRequireLowercase = (bool) MemberSetting::getValue('password_require_lowercase', true);
        $passwordRequireNumber = (bool) MemberSetting::getValue('password_require_number', true);
        $passwordRequireSymbol = (bool) MemberSetting::getValue('password_require_symbol', false);
        $passwordCheckPwned = (bool) MemberSetting::getValue('password_check_pwned', false);

        // パスワードバリデーションルールを構築（任意入力）
        $passwordRules = PasswordService::buildPasswordRules(
            $passwordMinLength,
            $passwordRequireUppercase,
            $passwordRequireLowercase,
            $passwordRequireNumber,
            $passwordRequireSymbol,
            false, // プロフィール更新時は任意
            $passwordCheckPwned
        );

        return [
            'password' => $passwordRules,
        ];
    }
}
