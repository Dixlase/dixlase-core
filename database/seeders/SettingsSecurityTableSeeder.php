<?php

/**
 * This file is part of Your Software Name.
 *
 * Copyright (C) 2024 exc-D inc.
 * Website: https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SettingSecurity;

class SettingsSecurityTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultSettings = [
            ['name' => 'admin_url', 'value' => 'admin'],
            ['name' => 'enable_allowed_admin_ips', 'value' => '0'], // 特定のIPアドレスのみ管理画面へのアクセス許可
            ['name' => 'allowed_admin_ips', 'value' => '127.0.0.1'], // 初期はローカルホストのみ
            ['name' => 'enable_blocked_admin_ips', 'value' => '0'], // 特定のIPアドレスを管理画面へのアクセス禁止
            ['name' => 'blocked_admin_ips', 'value' => ''], // 初期は空
            ['name' => 'enable_allowed_front_ips', 'value' => 'false'], // 特定のIPアドレスのみフロントへのアクセス許可
            ['name' => 'allowed_front_ips', 'value' => ''], // 初期は空
            ['name' => 'enable_blocked_front_ips', 'value' => 'false'], // 特定のIPアドレスをフロントへのアクセス禁止
            ['name' => 'blocked_front_ips', 'value' => ''],
            ['name' => 'force_ssl', 'value' => '0'],

        ];

        foreach ($defaultSettings as $setting) {
            SettingSecurity::updateOrCreate(['name' => $setting['name']], ['value' => $setting['value']]);
        }
    }
}
