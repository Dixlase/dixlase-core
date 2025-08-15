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
            ['name' => 'maintenance_message', 'value' => '現在メンテナンス中です。しばらくお待ちください。'],
            // メール接続テスト関連
            ['name' => 'mail_connection_tested', 'value' => 0],
            ['name' => 'mail_connection_test_date', 'value' => null],
            ['name' => 'mail_send_tested', 'value' => 0],
            ['name' => 'mail_send_test_date', 'value' => null],
            ['name' => 'mail_receive_tested', 'value' => 0],
            ['name' => 'mail_receive_test_date', 'value' => null],
            ['name' => 'mail_verification_token', 'value' => null],
        ];

        foreach ($settings as $setting) {
            DB::table('base_settings')->updateOrInsert(
                ['name' => $setting['name']],
                ['value' => $setting['value'], 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
