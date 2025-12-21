<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * https://exc-d.com
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
use App\Models\MemberSetting;
use App\Services\CaptchaTestService;
use App\Services\CaptchaFailoverService;
use App\Services\CaptchaBypassService;
use Illuminate\Support\Facades\Log;

class CaptchaHelper
{
    /**
     * CAPTCHA設定を一括取得
     */
    public static function getSettings(): array
    {
        return [
            'enabled' => filter_var(SecuritySetting::get('captcha_enabled', false), FILTER_VALIDATE_BOOLEAN),
            'driver' => SecuritySetting::get('captcha_driver', 'google'),
            'site_key' => SecuritySetting::get('captcha_site_key', ''),
            'secret_key' => SecuritySetting::get('captcha_secret_key', ''),
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
        if (CaptchaBypassService::shouldSkipCaptcha($scope)) {
            return false;
        }

        $settings = self::getSettings();
        
        // 基本的なCAPTCHA有効性チェック
        if (!$settings['enabled']) {
            return false;
        }

        // フォーム固有の設定チェック
        $formCaptchaEnabled = $formName ? self::isEnabledForForm($formName) : true;
        if (!$formCaptchaEnabled) {
            return false;
        }

        // 認証テスト結果チェック
        if (!$settings['authentication_result']) {
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
               !empty($settings['site_key']) && 
               !empty($settings['secret_key']) &&
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
        if (!empty($config['site_key'])) {
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
        if (!empty($config['secret_key'])) {
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
     * admin_loginの場合はMemberSettingから読み込む
     * その他のフォームはプラグイン側で独自に管理する
     */
    public static function isEnabledForForm(string $formName): bool
    {
        // 管理画面ログインの場合はMemberSettingから読み込む
        if ($formName === 'admin_login') {
            return filter_var(
                MemberSetting::get('captcha_admin_login_enabled', false),
                FILTER_VALIDATE_BOOLEAN
            );
        }
        
        // その他のフォームはプラグイン側で独自に管理するため、ここではfalseを返す
        // プラグインは独自の設定テーブルからCAPTCHA有効/無効を判定すること
        return false;
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

            if (!$settings['authentication_result']) {
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
            'site_key' => $settings['site_key'] ? substr($settings['site_key'], 0, 10) . '...' : 'Not set',
            'secret_key' => $settings['secret_key'] ? substr($settings['secret_key'], 0, 10) . '...' : 'Not set',
            'google_version' => $settings['google_version'],
            'google_min_score' => $settings['google_min_score'],
            'authentication_result' => $settings['authentication_result'],
        ];
    }
}
