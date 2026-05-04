<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
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

namespace Database\Seeders;

use App\Enums\SettingScope;
use App\Services\Site\SettingDefinitionRegistry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SiteSettingsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // App settings (for fallback)
            ['name' => 'app_name', 'value' => config('app.name', 'MySoftware')],
            ['name' => 'locale', 'value' => config('app.locale', 'ja')],
            ['name' => 'timezone', 'value' => config('app.timezone', 'Asia/Tokyo')],

            // Admin panel URL settings
            ['name' => 'admin_url', 'value' => 'admin'],
            ['name' => 'force_ssl', 'value' => '0'],

            // Maintenance mode
            ['name' => 'maintenance_mode', 'value' => config('app.maintenance_mode', false) ? '1' : '0'],
            ['name' => 'maintenance_message', 'value' => 'Currently under maintenance. Please wait a moment.'],
            ['name' => 'maintenance_auto_release', 'value' => '0'], // 0: manual release, 1: automatic release
            ['name' => 'maintenance_start_at', 'value' => null], // Start datetime (immediate start if null)
            ['name' => 'maintenance_release_at', 'value' => null], // End datetime (null for manual release)

            // Mail settings (for fallback)
            ['name' => 'mail_mailer', 'value' => config('mail.default', 'smtp')],
            ['name' => 'mail_host', 'value' => config('mail.mailers.smtp.host', 'smtp.example.com')],
            ['name' => 'mail_port', 'value' => (string) config('mail.mailers.smtp.port', 587)],
            ['name' => 'mail_username', 'value' => config('mail.mailers.smtp.username', '')],
            ['name' => 'mail_password', 'value' => config('mail.mailers.smtp.password', '')],
            ['name' => 'mail_encryption', 'value' => config('mail.mailers.smtp.encryption', 'tls')],
            ['name' => 'mail_from_address', 'value' => config('mail.from.address', 'no-reply@example.com')],

            // Mail connection test related
            ['name' => 'mail_connection_tested', 'value' => 0],
            ['name' => 'mail_connection_test_date', 'value' => null],
            ['name' => 'mail_send_tested', 'value' => 0],
            ['name' => 'mail_send_test_date', 'value' => null],
            ['name' => 'mail_receive_tested', 'value' => 0],
            ['name' => 'mail_receive_test_date', 'value' => null],
            ['name' => 'mail_verification_token', 'value' => null],

            // System administrator email address
            ['name' => 'system_admin_email', 'value' => ''],

            // OGP/SEO settings
            ['name' => 'default_ogp_image_id', 'value' => null],
            ['name' => 'site_description', 'value' => ''],
            ['name' => 'site_keywords', 'value' => ''],
            ['name' => 'twitter_card_type', 'value' => 'summary_large_image'],
        ];

        // Route each seeded value to the right storage tier based on its
        // registered SettingScope:
        //   - Global keys     -> global_settings (network-wide)
        //   - Overridable     -> global_settings (network-wide default;
        //                       sites override per-site as needed)
        //   - PerSite         -> site_settings for the primary site (id=1)
        // Unregistered keys are skipped to keep the registry authoritative.
        $primarySiteId = 1;
        $registry = app(SettingDefinitionRegistry::class);
        $now = now();

        foreach ($settings as $setting) {
            $definition = $registry->get($setting['name']);
            if ($definition === null) {
                continue;
            }

            if ($definition->scope === SettingScope::PerSite) {
                DB::table('site_settings')->updateOrInsert(
                    ['name' => $setting['name'], 'site_id' => $primarySiteId],
                    ['value' => $setting['value'], 'created_at' => $now, 'updated_at' => $now]
                );
            } else {
                DB::table('global_settings')->updateOrInsert(
                    ['name' => $setting['name']],
                    ['value' => $setting['value'], 'created_at' => $now, 'updated_at' => $now]
                );
            }
        }
    }
}
