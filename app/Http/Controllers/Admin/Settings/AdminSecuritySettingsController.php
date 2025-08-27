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
            'enable_allowed_admin_ips' => SecuritySetting::get('enable_allowed_admin_ips', false),
            'allowed_admin_ips' => SecuritySetting::get('allowed_admin_ips', ''),
            'enable_blocked_admin_ips' => SecuritySetting::get('enable_blocked_admin_ips', false),
            'blocked_admin_ips' => SecuritySetting::get('blocked_admin_ips', ''),
            'enable_allowed_front_ips' => SecuritySetting::get('enable_allowed_front_ips', false),
            'allowed_front_ips' => SecuritySetting::get('allowed_front_ips', ''),
            'enable_blocked_front_ips' => SecuritySetting::get('enable_blocked_front_ips', false),
            'blocked_front_ips' => SecuritySetting::get('blocked_front_ips', ''),
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

        SecuritySetting::set('enable_allowed_admin_ips', $request->boolean('enable_allowed_admin_ips'));
        SecuritySetting::set('allowed_admin_ips', $request->input('allowed_admin_ips'));
        SecuritySetting::set('enable_blocked_admin_ips', $request->boolean('enable_blocked_admin_ips'));
        SecuritySetting::set('blocked_admin_ips', $request->input('blocked_admin_ips'));
        SecuritySetting::set('enable_allowed_front_ips', $request->boolean('enable_allowed_front_ips'));
        SecuritySetting::set('allowed_front_ips', $request->input('allowed_front_ips'));
        SecuritySetting::set('enable_blocked_front_ips', $request->boolean('enable_blocked_front_ips'));
        SecuritySetting::set('blocked_front_ips', $request->input('blocked_front_ips'));
        
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

        return redirect()->route('admin.settings.security')
            ->with('success', __('admin.settings.security.controller_messages.settings_updated'));
    }
}
