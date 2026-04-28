<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see LICENSE
 *       for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE.commercial, or contact office@exc-d.com).
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the AGPL terms below.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\Helpers;

use App\Models\SecuritySetting;
use App\Services\CaptchaBypassService;
use App\Services\CaptchaFailoverService;
use App\Services\CaptchaTestService;
use Illuminate\Support\Facades\Log;

class CaptchaHelper
{
    /**
     * CAPTCHA設定を一括取得
     */
    public static function getSettings(): array
    {
        $driver = SecuritySetting::get('captcha_driver', 'google');

        // プロバイダー別のキーを取得
        $siteKey = '';
        $secretKey = '';

        switch ($driver) {
            case 'google':
                $siteKey = SecuritySetting::get('captcha_google_site_key', '');
                $secretKey = SecuritySetting::get('captcha_google_secret_key', '');
                break;
            case 'google_enterprise':
                $siteKey = SecuritySetting::get('captcha_google_enterprise_site_key', '');
                $secretKey = SecuritySetting::get('captcha_google_enterprise_secret_key', '');
                break;
            case 'turnstile':
                $siteKey = SecuritySetting::get('captcha_turnstile_site_key', '');
                $secretKey = SecuritySetting::get('captcha_turnstile_secret_key', '');
                break;
        }

        return [
            'enabled' => filter_var(SecuritySetting::get('captcha_enabled', false), FILTER_VALIDATE_BOOLEAN),
            'driver' => $driver,
            'site_key' => $siteKey,
            'secret_key' => $secretKey,
            'google_version' => SecuritySetting::get('captcha_google_version', 'v3'),
            'google_min_score' => (float) SecuritySetting::get('captcha_google_min_score', 0.5),
            'google_project_id' => SecuritySetting::get('captcha_google_project_id', ''),
            'authentication_result' => filter_var(SecuritySetting::get('captcha_authentication_result', false), FILTER_VALIDATE_BOOLEAN),
        ];
    }

    /**
     * 指定されたフォームでCAPTCHAを表示すべきかチェック
     */
    public static function shouldShowCaptcha(?string $formName = null): bool
    {
        // 緊急バイパスがアクティブな場合はCAPTCHAを表示しない
        $scope = self::getBypassScopeForForm($formName);
        $bypassActive = CaptchaBypassService::shouldSkipCaptcha($scope);

        $settings = self::getSettings();

        // フォーム固有の設定チェック
        $formCaptchaEnabled = $formName ? self::isEnabledForForm($formName) : true;

        if ($bypassActive) {
            return false;
        }

        // 基本的なCAPTCHA有効性チェック
        if (! $settings['enabled']) {
            return false;
        }

        if (! $formCaptchaEnabled) {
            return false;
        }

        // 認証テスト結果チェック
        if (! $settings['authentication_result']) {
            return false;
        }

        return true;
    }

    /**
     * フォーム名からバイパススコープを取得
     */
    protected static function getBypassScopeForForm(?string $formName): string
    {
        return match ($formName) {
            'admin_login' => 'admin_login',
            default => 'all',
        };
    }

    /**
     * 緊急バイパスがアクティブかチェック
     */
    public static function isBypassActive(?string $scope = null): bool
    {
        return CaptchaBypassService::isActive($scope);
    }

    /**
     * CAPTCHAが有効かどうかチェック（基本設定のみ）
     */
    public static function isEnabled(): bool
    {
        $settings = self::getSettings();

        return $settings['enabled'] &&
               ! empty($settings['site_key']) &&
               ! empty($settings['secret_key']) &&
               $settings['authentication_result'];
    }

    /**
     * 現在のCAPTCHAドライバーを取得
     * フェイルオーバー中の場合は一時的なアクティブプロバイダーを返す
     */
    public static function getDriver(): string
    {
        return CaptchaFailoverService::getActiveProvider();
    }

    /**
     * サイトキーを取得
     * フェイルオーバー中の場合はアクティブプロバイダーのキーを返す
     */
    public static function getSiteKey(): string
    {
        $activeProvider = CaptchaFailoverService::getActiveProvider();
        $config = CaptchaFailoverService::getProviderConfig($activeProvider);

        // プロバイダー固有のキーがあればそれを使用
        if (! empty($config['site_key'])) {
            return $config['site_key'];
        }

        // フォールバック: 統一キー
        return SecuritySetting::get('captcha_site_key', '');
    }

    /**
     * シークレットキーを取得
     * フェイルオーバー中の場合はアクティブプロバイダーのキーを返す
     */
    public static function getSecretKey(): string
    {
        $activeProvider = CaptchaFailoverService::getActiveProvider();
        $config = CaptchaFailoverService::getProviderConfig($activeProvider);

        // プロバイダー固有のキーがあればそれを使用
        if (! empty($config['secret_key'])) {
            return $config['secret_key'];
        }

        // フォールバック: 統一キー
        return SecuritySetting::get('captcha_secret_key', '');
    }

    /**
     * Google reCAPTCHAのバージョンを取得
     */
    public static function getGoogleVersion(): string
    {
        return SecuritySetting::get('captcha_google_version', 'v3');
    }

    /**
     * Google reCAPTCHAの最小スコアを取得
     */
    public static function getGoogleMinScore(): float
    {
        return (float) SecuritySetting::get('captcha_google_min_score', 0.5);
    }

    /**
     * Google reCAPTCHA EnterpriseのプロジェクトIDを取得
     */
    public static function getGoogleProjectId(): string
    {
        return SecuritySetting::get('captcha_google_project_id', '');
    }

    /**
     * CAPTCHA認証テスト結果を取得
     */
    public static function getTestResult(): bool
    {
        $captchaTestService = app(CaptchaTestService::class);

        return $captchaTestService->getTestResult();
    }

    /**
     * 指定されたフォームでCAPTCHAが有効かチェック
     *
     * admin_login, admin_password_resetの場合はSecuritySettingから読み込む
     * user_login, user_register, user_password_resetの場合はDixlaseUsersUserSettingから読み込む
     * その他のフォームはプラグイン側で独自に管理する
     */
    public static function isEnabledForForm(string $formName): bool
    {
        // CaptchaServiceを使用してフォームの有効状態をチェック
        $captchaService = app(\App\Services\CaptchaService::class);

        return $captchaService->isEnabled($formName);
    }

    /**
     * 旧バージョンとの互換性のため残す（非推奨）
     *
     * @deprecated Use isEnabledForForm() instead
     */
    protected static function isEnabledForFormLegacy(string $formName): bool
    {
        // 管理画面のフォーム
        if (in_array($formName, ['admin_login', 'admin_password_reset'])) {
            $settingKey = match ($formName) {
                'admin_login' => 'captcha_admin_login_enabled',
                'admin_password_reset' => 'captcha_password_reset_enabled',
            };

            return filter_var(
                SecuritySetting::get($settingKey, false),
                FILTER_VALIDATE_BOOLEAN
            );
        }

        // ユーザープラグインのフォーム
        if (in_array($formName, ['user_login', 'user_register', 'user_password_reset'])) {
            // DixlaseUsersUserSettingモデルが存在するか確認
            if (class_exists('\Plugins\DixlaseUsers\App\Models\DixlaseUsersUserSetting')) {
                $settingKey = match ($formName) {
                    'user_login' => 'captcha_login_enabled',
                    'user_register' => 'captcha_register_enabled',
                    'user_password_reset' => 'captcha_password_reset_enabled',
                };

                return filter_var(
                    \Plugins\DixlaseUsers\App\Models\DixlaseUsersUserSetting::getValue($settingKey, false),
                    FILTER_VALIDATE_BOOLEAN
                );
            }
        }

        // その他のフォームはプラグイン側で独自に管理するため、ここではfalseを返す
        // プラグインは独自の設定テーブルからCAPTCHA有効/無効を判定すること
        return false;
    }

    /**
     * 汎用的なCAPTCHA有効チェック（設定モデルクラスとキーを指定）
     *
     * @param  string  $formName  フォーム名（バイパススコープ判定用）
     * @param  string  $settingModelClass  設定モデルのクラス名
     * @param  string  $settingKey  設定キー名
     * @param  mixed  $defaultValue  デフォルト値
     */
    public static function isEnabledForFormWithModel(
        string $formName,
        string $settingModelClass,
        string $settingKey,
        $defaultValue = false
    ): bool {
        // 基本的なCAPTCHA設定をチェック
        if (! self::isEnabled()) {
            return false;
        }

        // 緊急バイパスがアクティブな場合はCAPTCHAを無効化
        $scope = self::getBypassScopeForForm($formName);
        if (CaptchaBypassService::shouldSkipCaptcha($scope)) {
            return false;
        }

        // 認証テスト結果チェック
        $settings = self::getSettings();
        if (! $settings['authentication_result']) {
            return false;
        }

        // 設定モデルから値を取得
        if (class_exists($settingModelClass)) {
            if (method_exists($settingModelClass, 'getValue')) {
                $value = $settingModelClass::getValue($settingKey, $defaultValue);
            } elseif (method_exists($settingModelClass, 'get')) {
                $value = $settingModelClass::get($settingKey, $defaultValue);
            } else {
                return false;
            }

            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }

        return false;
    }

    /**
     * CAPTCHAウィジェットを生成
     *
     * @param  string  $action  CAPTCHAアクション名
     * @return string|null ウィジェットHTML（CAPTCHAが無効な場合はnull）
     */
    public static function renderWidget(string $action): ?string
    {
        try {
            $captchaDriverInstance = app(\App\Captcha\CaptchaDriver::class);

            return $captchaDriverInstance->renderWidget(['action' => $action]);
        } catch (\Exception $e) {
            Log::error('Failed to render CAPTCHA widget', [
                'action' => $action,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * CAPTCHAを検証
     *
     * @param  \Illuminate\Http\Request  $request  リクエスト
     * @param  string  $action  CAPTCHAアクション名
     * @return \App\Captcha\CaptchaResult|null 検証結果（CAPTCHAが無効な場合はnull）
     */
    public static function verify(\Illuminate\Http\Request $request, string $action): ?\App\Captcha\CaptchaResult
    {
        // CAPTCHAが無効な場合はnullを返す（検証スキップ）
        if (! self::shouldShowCaptcha($action)) {
            return null;
        }

        try {
            $captchaDriverInstance = app(\App\Captcha\CaptchaDriver::class);

            return $captchaDriverInstance->verify($request);
        } catch (\Exception $e) {
            Log::error('Failed to verify CAPTCHA', [
                'action' => $action,
                'error' => $e->getMessage(),
            ]);

            // エラー時は失敗として扱う
            return new \App\Captcha\CaptchaResult(
                false,
                __('auth.captcha_verification_failed')
            );
        }
    }

    /**
     * 指定されたフォームでCAPTCHAが有効かチェック（設定取得関数を指定）
     *
     * @param  string  $formName  フォーム名
     * @param  callable  $settingGetter  設定取得関数
     */
    public static function isEnabledForFormWithSettings(string $formName, callable $settingGetter): bool
    {
        // 基本的なCAPTCHA設定をチェック
        if (! self::isEnabled()) {
            return false;
        }

        // 緊急バイパスがアクティブな場合はCAPTCHAを無効化
        $scope = self::getBypassScopeForForm($formName);
        if (CaptchaBypassService::shouldSkipCaptcha($scope)) {
            return false;
        }

        // フォーム固有の設定をチェック
        $formEnabled = $settingGetter($formName);

        return filter_var($formEnabled, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * CAPTCHA設定の妥当性をチェック
     */
    public static function validateSettings(): array
    {
        $settings = self::getSettings();
        $errors = [];

        if ($settings['enabled']) {
            if (empty($settings['site_key'])) {
                $errors[] = 'Site key is required when CAPTCHA is enabled';
            }

            if (empty($settings['secret_key'])) {
                $errors[] = 'Secret key is required when CAPTCHA is enabled';
            }

            if (! $settings['authentication_result']) {
                $errors[] = 'CAPTCHA authentication test must be completed';
            }

            if ($settings['driver'] === 'google_enterprise' && empty($settings['google_project_id'])) {
                $errors[] = 'Project ID is required for Google reCAPTCHA Enterprise';
            }
        }

        return $errors;
    }

    /**
     * CAPTCHA設定をログ出力用に安全な形式で取得
     */
    public static function getSettingsForLogging(): array
    {
        $settings = self::getSettings();

        return [
            'enabled' => $settings['enabled'],
            'driver' => $settings['driver'],
            'site_key' => $settings['site_key'] ? substr($settings['site_key'], 0, 10).'...' : 'Not set',
            'secret_key' => $settings['secret_key'] ? substr($settings['secret_key'], 0, 10).'...' : 'Not set',
            'google_version' => $settings['google_version'],
            'google_min_score' => $settings['google_min_score'],
            'authentication_result' => $settings['authentication_result'],
        ];
    }
}
