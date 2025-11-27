<?php

/**
 * This file is part of Dixlase.
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

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BaseSettingsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // App settings (フォールバック用)
            ['name' => 'app_name', 'value' => config('app.name', 'MySoftware')],
            ['name' => 'locale', 'value' => config('app.locale', 'ja')],
            ['name' => 'timezone', 'value' => config('app.timezone', 'Asia/Tokyo')],
            
            // メンテナンスモード
            ['name' => 'maintenance_mode', 'value' => config('app.maintenance_mode', false) ? '1' : '0'],
            ['name' => 'maintenance_message', 'value' => '現在メンテナンス中です。しばらくお待ちください。'],

            // Mail settings (フォールバック用)
            ['name' => 'mail_mailer', 'value' => config('mail.default', 'smtp')],
            ['name' => 'mail_host', 'value' => config('mail.mailers.smtp.host', 'smtp.example.com')],
            ['name' => 'mail_port', 'value' => (string) config('mail.mailers.smtp.port', 587)],
            ['name' => 'mail_username', 'value' => config('mail.mailers.smtp.username', '')],
            ['name' => 'mail_password', 'value' => config('mail.mailers.smtp.password', '')],
            ['name' => 'mail_encryption', 'value' => config('mail.mailers.smtp.encryption', 'tls')],
            ['name' => 'mail_from_address', 'value' => config('mail.from.address', 'no-reply@example.com')],
            
            // メール接続テスト関連
            ['name' => 'mail_connection_tested', 'value' => 0],
            ['name' => 'mail_connection_test_date', 'value' => null],
            ['name' => 'mail_send_tested', 'value' => 0],
            ['name' => 'mail_send_test_date', 'value' => null],
            ['name' => 'mail_receive_tested', 'value' => 0],
            ['name' => 'mail_receive_test_date', 'value' => null],
            ['name' => 'mail_verification_token', 'value' => null],
            
            // システム管理者メールアドレス
            ['name' => 'system_admin_email', 'value' => ''],
            
            // OGP・SEO設定
            ['name' => 'default_ogp_image_id', 'value' => null],
            ['name' => 'site_description', 'value' => ''],
            ['name' => 'site_keywords', 'value' => ''],
            ['name' => 'twitter_card_type', 'value' => 'summary_large_image'],
            
            // 多言語設定
            ['name' => 'multilingual_enabled', 'value' => '0'],
            // 有効な言語（JSON配列形式、デフォルトは英語のみ）
            ['name' => 'enabled_locales', 'value' => json_encode(['en'])],
        ];

        foreach ($settings as $setting) {
            DB::table('base_settings')->updateOrInsert(
                ['name' => $setting['name']],
                ['value' => $setting['value'], 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
