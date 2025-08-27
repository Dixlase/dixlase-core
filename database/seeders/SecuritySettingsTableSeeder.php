<?php

/**
 * This file is part of MySoftware.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
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


namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SecuritySetting;
use App\Enums\LogLevel;

class SecuritySettingsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // System error notification settings
        SecuritySetting::updateOrCreate(
            ['name' => 'notification_enabled'],
            ['value' => '1']
        );

        SecuritySetting::updateOrCreate(
            ['name' => 'notification_log_levels'],
            ['value' => implode(',', LogLevel::getDefaultNotificationLevels())]
        );

        // reCAPTCHA settings
        SecuritySetting::updateOrCreate(
            ['name' => 'captcha_enabled'],
            ['value' => '0']
        );

        SecuritySetting::updateOrCreate(
            ['name' => 'captcha_driver'],
            ['value' => 'google']
        );

        SecuritySetting::updateOrCreate(
            ['name' => 'captcha_google_site_key'],
            ['value' => '']
        );

        SecuritySetting::updateOrCreate(
            ['name' => 'captcha_google_secret_key'],
            ['value' => '']
        );

        SecuritySetting::updateOrCreate(
            ['name' => 'captcha_google_version'],
            ['value' => 'v3']
        );

        SecuritySetting::updateOrCreate(
            ['name' => 'captcha_google_min_score'],
            ['value' => '0.5']
        );

        // IP Restriction settings
        SecuritySetting::updateOrCreate(
            ['name' => 'enable_allowed_admin_ips'],
            ['value' => '0']
        );
        SecuritySetting::updateOrCreate(
            ['name' => 'allowed_admin_ips'],
            ['value' => '']
        );
        SecuritySetting::updateOrCreate(
            ['name' => 'enable_blocked_admin_ips'],
            ['value' => '0']
        );
        SecuritySetting::updateOrCreate(
            ['name' => 'blocked_admin_ips'],
            ['value' => '']
        );
        SecuritySetting::updateOrCreate(
            ['name' => 'enable_allowed_front_ips'],
            ['value' => '0']
        );
        SecuritySetting::updateOrCreate(
            ['name' => 'allowed_front_ips'],
            ['value' => '']
        );
        SecuritySetting::updateOrCreate(
            ['name' => 'enable_blocked_front_ips'],
            ['value' => '0']
        );
        SecuritySetting::updateOrCreate(
            ['name' => 'blocked_front_ips'],
            ['value' => '']
        );

        // Turnstile settings
        SecuritySetting::updateOrCreate(
            ['name' => 'captcha_turnstile_site_key'],
            ['value' => '']
        );

        SecuritySetting::updateOrCreate(
            ['name' => 'captcha_turnstile_secret_key'],
            ['value' => '']
        );
    }
}
