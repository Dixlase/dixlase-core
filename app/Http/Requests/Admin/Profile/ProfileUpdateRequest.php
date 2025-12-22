<?php

namespace App\Http\Requests\Admin\Profile;

use App\Enums\AppearanceMode;
use App\Enums\Locale;
use App\Enums\LoginNotificationMode;
use App\Enums\TwoFactorMethod;
use App\Enums\TwoFactorMode;
use App\Models\MemberSetting;
use App\Rules\NotPwnedPassword;
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
        
        // パスワード条件を全体設定から取得
        $minLength = (int) MemberSetting::getValue('password_min_length', 8);
        $requireUppercase = (bool) MemberSetting::getValue('password_require_uppercase', true);
        $requireSymbol = (bool) MemberSetting::getValue('password_require_symbol', false);

        // パスワードのルールを動的に構築
        $passwordRules = ['nullable', "min:$minLength"];

        // パスワードが入力されている場合のみ確認を必須にする
        if ($this->filled('password') && $this->filled('password_confirmation')) {
            $passwordRules[] = 'confirmed';
        }

        // パスワードが入力されている場合のみ複雑性チェックを適用
        if ($this->filled('password')) {
            // 常に小文字と数字を必須にする
            $passwordRules[] = 'regex:/[a-z]/'; // 小文字
            $passwordRules[] = 'regex:/[0-9]/'; // 数字

            // 条件に応じて大文字と記号を追加
            if ($requireUppercase) {
                $passwordRules[] = 'regex:/[A-Z]/'; // 大文字
            }
            if ($requireSymbol) {
                $passwordRules[] = 'regex:/[!@#$%^&*(),.?":{}|<>]/'; // 記号
            }
            
            // パスワード辞書攻撃対策
            $passwordRules[] = new NotPwnedPassword();
        }

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
