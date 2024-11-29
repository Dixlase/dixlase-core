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
use Illuminate\Support\Facades\DB;

class SettingsSystemTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            ['name' => 'site_name', 'value' => 'My Site'],
            ['name' => 'is_member_site', 'value' => 'false'],
            ['name' => 'allow_external_registration', 'value' => 'false'],
            ['name' => 'allow_guest_registration', 'value' => 'false'],
            ['name' => 'required_fields', 'value' => '{"address": false, "phone": false, "gender": false, "birthday": false}'],
            ['name' => 'admin_theme', 'value' => 'light'],
            ['name' => 'language', 'value' => 'ja'],
            ['name' => 'maintenance_mode', 'value' => 'false'],
            ['name' => 'maintenance_message', 'value' => '現在メンテナンス中です。しばらくお待ちください。'],
        ];

        foreach ($settings as $setting) {
            DB::table('settings_system')->updateOrInsert(
                ['name' => $setting['name']],
                ['value' => $setting['value'], 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }
}
