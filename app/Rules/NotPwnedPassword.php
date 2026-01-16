<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Traits\PwnedPasswordTrait;

/**
 * パスワード漏洩チェックバリデーションルール
 * 
 * Have I Been Pwned APIを使用してパスワードが漏洩データベースに含まれていないかチェック
 */
class NotPwnedPassword implements ValidationRule
{
    use PwnedPasswordTrait;

    private string $settingKey;
    private bool $skipOnApiError;

    /**
     * コンストラクタ
     * 
     * @param string $settingKey 設定キー（デフォルト: 'pwned_password_check_enabled'）
     * @param bool $skipOnApiError APIエラー時にバリデーションをスキップするか（デフォルト: true）
     */
    public function __construct(string $settingKey = 'pwned_password_check_enabled', bool $skipOnApiError = true)
    {
        $this->settingKey = $settingKey;
        $this->skipOnApiError = $skipOnApiError;
    }

    /**
     * バリデーション実行
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // 辞書攻撃対策が無効の場合はスキップ
        if (!$this->isPwnedPasswordCheckEnabled($this->settingKey)) {
            return;
        }

        // パスワードが文字列でない場合はスキップ
        if (!is_string($value)) {
            return;
        }

        $safetyCheck = $this->validatePasswordSafety($value, $this->settingKey);

        // APIエラーの場合
        if ($safetyCheck['pwned_info']['error']) {
            if (!$this->skipOnApiError) {
                $fail(__('validation.pwned_password_api_error'));
            }
            return;
        }

        // パスワードが漏洩している場合
        if (!$safetyCheck['is_safe']) {
            $fail($safetyCheck['message']);
        }
    }

    /**
     * 静的ファクトリーメソッド
     * 
     * @param string $settingKey 設定キー
     * @param bool $skipOnApiError APIエラー時にスキップするか
     * @return static
     */
    public static function using(string $settingKey = 'pwned_password_check_enabled', bool $skipOnApiError = true): static
    {
        return new static($settingKey, $skipOnApiError);
    }
}
