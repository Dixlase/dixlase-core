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

return [
    // 例: スタブのデフォルト格納先
    'default_stub_directory' => base_path('vendor/laravel/framework/src/Illuminate/Routing/Console/stubs'),

    // 独自のカスタムスタブディレクトリを複数設定したい場合
    'custom_stub_paths' => [
        base_path('stubs/custom'),
        base_path('stubs/overrides'),
    ],

    // 他にも繰り返し使うような定数など
    'license_txt' => base_path('license.txt'),
    'license_json' => base_path('license-info.json'),
];
