<?php

/**
 * This file is part of MySoftware.
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

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Models\SecuritySetting;
use App\Models\CaptchaFormSetting;
use App\Http\Requests\Admin\Settings\Security\AdminSettngsSecurityUpdateRequest;
use App\Enums\LogLevel;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;



class AdminSecuritySettingsController extends AdminLoggedInController
{

    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {


        $settings = [
            'admin_url' => SecuritySetting::get('admin_url', 'member'),
            'enable_allowed_admin_ips' => SecuritySetting::get('enable_allowed_admin_ips', false),
            'allowed_admin_ips' => SecuritySetting::get('allowed_admin_ips', ''),
            'enable_blocked_admin_ips' => SecuritySetting::get('enable_blocked_admin_ips', false),
            'blocked_admin_ips' => SecuritySetting::get('blocked_admin_ips', ''),
            'force_ssl' => SecuritySetting::get('force_ssl', false),
            // reCAPTCHA settings
            'captcha_enabled' => SecuritySetting::get('captcha_enabled', false),
            'captcha_driver' => SecuritySetting::get('captcha_driver', 'google'),
            'captcha_google_site_key' => SecuritySetting::get('captcha_google_site_key', ''),
            'captcha_google_secret_key' => SecuritySetting::get('captcha_google_secret_key', ''),
            'captcha_google_version' => SecuritySetting::get('captcha_google_version', 'v3'),
            'captcha_google_min_score' => SecuritySetting::get('captcha_google_min_score', '0.5'),
            // Turnstile settings
            'captcha_turnstile_site_key' => SecuritySetting::get('captcha_turnstile_site_key', ''),
            'captcha_turnstile_secret_key' => SecuritySetting::get('captcha_turnstile_secret_key', ''),
            // Notification settings
            'notification_enabled' => SecuritySetting::get('notification_enabled', true),
            'notification_log_levels' => array_map('intval', array_filter(explode(',', SecuritySetting::get('notification_log_levels', implode(',', LogLevel::getDefaultNotificationLevels()))))),
        ];

        // 動的reCAPTCHAフォーム設定を取得
        $captchaFormSettings = CaptchaFormSetting::getOrderedForms();

        $this->viewParams['settings'] = $settings;
        $this->viewParams['captchaFormSettings'] = $captchaFormSettings;

        return view('admin.settings.security.index', $this->viewParams);
    }

    public function update(AdminSettngsSecurityUpdateRequest $request)
    {


        // 現在の管理画面URLを取得
        $currentAdminUrl = SecuritySetting::get('admin_url', config('security.admin_url'));

        SecuritySetting::set('admin_url', $request->input('admin_url'));
        SecuritySetting::set('allowed_admin_ips', $request->input('allowed_admin_ips'));
        SecuritySetting::set('blocked_admin_ips', $request->input('blocked_admin_ips'));
        SecuritySetting::set('force_ssl', $request->boolean('force_ssl'));
        
        // Save reCAPTCHA settings
        SecuritySetting::set('captcha_enabled', $request->boolean('captcha_enabled'));
        SecuritySetting::set('captcha_driver', $request->input('captcha_driver', 'google'));
        SecuritySetting::set('captcha_google_site_key', $request->input('captcha_google_site_key', ''));
        SecuritySetting::set('captcha_google_secret_key', $request->input('captcha_google_secret_key', ''));
        SecuritySetting::set('captcha_google_version', $request->input('captcha_google_version', 'v3'));
        SecuritySetting::set('captcha_google_min_score', $request->input('captcha_google_min_score', '0.5'));
        // Save Turnstile settings
        SecuritySetting::set('captcha_turnstile_site_key', $request->input('captcha_turnstile_site_key', ''));
        SecuritySetting::set('captcha_turnstile_secret_key', $request->input('captcha_turnstile_secret_key', ''));
        
        // Save notification settings
        SecuritySetting::set('notification_enabled', $request->boolean('notification_enabled'));
        $notificationLogLevels = $request->input('notification_log_levels', []);


        
        // チェックされた値のみを取得し、0を除外してLogLevel enumの値のみを保存
        $validLogLevels = array_filter(
            array_map('intval', $notificationLogLevels),
            function($value) {
                return $value > 0 && in_array($value, \App\Enums\LogLevel::getNotificationLevels());
            }
        );
        
        SecuritySetting::set('notification_log_levels', implode(',', $validLogLevels));
        
        // Save dynamic captcha form settings
        $captchaFormSettings = CaptchaFormSetting::all();
        foreach ($captchaFormSettings as $formSetting) {
            $inputKey = 'captcha_form_' . $formSetting->key;
            $formSetting->enabled = $request->boolean($inputKey);
            $formSetting->save();
        }

        // 新しい管理画面URLを取得
        $newAdminUrl = $request->input('admin_url', config('security.admin_url'));

        // SSLを強制しているかどうかを確認
        $forceSsl = $request->boolean('force_ssl');

        // 管理画面のURLが変更された場合
        if ($newAdminUrl !== $currentAdminUrl) {
            // ユーザーをログアウト
            Auth::guard('admin')->logout();
            Session::flush();

            $newAdminLoginUrl = url($newAdminUrl . '/login');

            //SSLを強制している場合、HTTPSにリダイレクト
            if ($forceSsl) {
                $newAdminLoginUrl = str_replace('http://', 'https://', $newAdminLoginUrl);
            }

            // 新しいURLのログイン画面にリダイレクト
            return redirect($newAdminLoginUrl)
                ->with('success', '管理画面URLが変更されました。新しいURLでログインしてください。');
        }

        //通常のリダイレクト。セキュリティ設定のURLを取得
        $securityUrl = url($newAdminUrl . '/settings/security');

        //SSLを強制している場合、HTTPSに変換
        if ($forceSsl) {
            $securityUrl = str_replace('http://', 'https://', $securityUrl);
        }

        //リダイレクト
        return redirect($securityUrl)
            ->with('success', 'セキュリティ設定が更新されました。');
    }
}
