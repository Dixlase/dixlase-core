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

use Illuminate\Database\Seeder;
use App\Models\ApiSetting;

class ApiSettingsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // API機能の有効/無効
        ApiSetting::updateOrCreate(
            ['name' => 'api_enabled'],
            ['value' => '0']
        );

        // デフォルトレート制限（1分あたりのリクエスト数）
        ApiSetting::updateOrCreate(
            ['name' => 'api_rate_limit'],
            ['value' => '60']
        );

        // 署名検証を必須にするか
        ApiSetting::updateOrCreate(
            ['name' => 'api_signature_required'],
            ['value' => '1']
        );
    }
}
