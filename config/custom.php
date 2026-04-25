<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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

    /*
    |--------------------------------------------------------------------------
    | Custom Files Directory
    |--------------------------------------------------------------------------
    |
    | カスタムファイルを保存するディレクトリ
    |
    */
    'custom_files_dir' => env('CUSTOM_FILES_DIR', 'custom'),

    /*
    |--------------------------------------------------------------------------
    | Default Merge Mode
    |--------------------------------------------------------------------------
    |
    | カスタムファイルのデフォルトマージモード
    |
    */
    'default_merge_mode' => env('DEFAULT_MERGE_MODE', 'merge'),

    /*
    |--------------------------------------------------------------------------
    | Default License
    |--------------------------------------------------------------------------
    |
    | デフォルトのライセンス
    |
    */
    'default_license' => 'agpl',
];
