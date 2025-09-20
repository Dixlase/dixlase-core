<?php

namespace App\Helpers;

use App\Traits\PwnedPasswordTrait;
use Illuminate\Support\Facades\Log;

/**
 * パスワードセキュリティヘルパー
 * 
 * パスワードの安全性チェック機能を提供
 * 管理システムとユーザー管理プラグインで共有可能
 */
class PasswordSecurityHelper
{
    use PwnedPasswordTrait;

    /**
     * パスワードの包括的セキュリティチェック
     * 
     * @param string $password チェックするパスワード
     * @param array $options オプション設定
     * @return array
     */
    public static function validatePassword(string $password, array $options = []): array
    {
        $helper = new static();
        
        $settingKey = $options['setting_key'] ?? 'pwned_password_check_enabled';
        $skipPwnedCheck = $options['skip_pwned_check'] ?? false;
        
        $result = [
            'is_valid' => true,
            'errors' => [],
            'warnings' => [],
            'pwned_info' => null
        ];

        // 辞書攻撃対策チェック
        if (!$skipPwnedCheck) {
            $safetyCheck = $helper->validatePasswordSafety($password, $settingKey);
            $result['pwned_info'] = $safetyCheck['pwned_info'];
            
            if (!$safetyCheck['is_safe']) {
                $result['is_valid'] = false;
                $result['errors'][] = $safetyCheck['message'];
            } elseif (!empty($safetyCheck['message'])) {
                $result['warnings'][] = $safetyCheck['message'];
            }
        }

        return $result;
    }

    /**
     * 辞書攻撃対策設定の取得
     * 
     * @param string $settingKey 設定キー
     * @return array
     */
    public static function getPwnedPasswordSettings(string $settingKey = 'pwned_password_check_enabled'): array
    {
        $helper = new static();
        
        return [
            'enabled' => $helper->isPwnedPasswordCheckEnabled($settingKey),
            'setting_key' => $settingKey,
            'api_endpoint' => 'https://api.pwnedpasswords.com',
            'description' => __('admin.settings.security.pwned_password_description')
        ];
    }

    /**
     * パスワード強度とセキュリティの統合チェック
     * 
     * @param string $password パスワード
     * @param array $strengthRequirements 強度要件
     * @param array $securityOptions セキュリティオプション
     * @return array
     */
    public static function comprehensivePasswordCheck(
        string $password, 
        array $strengthRequirements = [], 
        array $securityOptions = []
    ): array {
        $result = [
            'is_valid' => true,
            'strength_errors' => [],
            'security_errors' => [],
            'warnings' => [],
            'pwned_info' => null
        ];

        // 基本的な強度チェック
        if (!empty($strengthRequirements)) {
            $strengthResult = static::checkPasswordStrength($password, $strengthRequirements);
            $result['strength_errors'] = $strengthResult['errors'];
            
            if (!empty($strengthResult['errors'])) {
                $result['is_valid'] = false;
            }
        }

        // セキュリティチェック（辞書攻撃対策）
        $securityResult = static::validatePassword($password, $securityOptions);
        $result['security_errors'] = $securityResult['errors'];
        $result['warnings'] = array_merge($result['warnings'], $securityResult['warnings']);
        $result['pwned_info'] = $securityResult['pwned_info'];
        
        if (!$securityResult['is_valid']) {
            $result['is_valid'] = false;
        }

        return $result;
    }

    /**
     * パスワード強度チェック
     * 
     * @param string $password パスワード
     * @param array $requirements 要件
     * @return array
     */
    private static function checkPasswordStrength(string $password, array $requirements): array
    {
        $errors = [];
        
        // 最小長チェック
        if (isset($requirements['min_length'])) {
            if (strlen($password) < $requirements['min_length']) {
                $errors[] = __('validation.min.string', [
                    'attribute' => __('validation.attributes.password'),
                    'min' => $requirements['min_length']
                ]);
            }
        }

        // 大文字必須チェック
        if (!empty($requirements['require_uppercase'])) {
            if (!preg_match('/[A-Z]/', $password)) {
                $errors[] = __('validation.password_uppercase_required');
            }
        }

        // 記号必須チェック
        if (!empty($requirements['require_symbol'])) {
            if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
                $errors[] = __('validation.password_symbol_required');
            }
        }

        return ['errors' => $errors];
    }

    /**
     * 設定値の取得（MemberSetting、SecuritySettingまたはconfig）
     * 
     * @param string $key 設定キー
     * @param mixed $default デフォルト値
     * @return mixed
     */
    public static function getSetting(string $key, $default = null)
    {
        // MemberSettingクラスが存在する場合はそれを使用（デフォルト）
        if (class_exists('\App\Models\MemberSetting')) {
            return \App\Models\MemberSetting::getValue($key, $default);
        }

        // SecuritySettingクラスが存在する場合はそれを使用（後方互換性）
        if (class_exists('\App\Models\SecuritySetting')) {
            return \App\Models\SecuritySetting::get($key, $default);
        }
        
        return config("security.{$key}", $default);
    }

    /**
     * ログ記録
     * 
     * @param string $level ログレベル
     * @param string $message メッセージ
     * @param array $context コンテキスト
     */
    public static function log(string $level, string $message, array $context = []): void
    {
        Log::log($level, "[PasswordSecurity] {$message}", $context);
    }
}
