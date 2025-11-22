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
use App\Models\Theme;

class ThemesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // theme.jsonから情報を読み込む
        $themeJsonPath = base_path('themes/DixlaseDefaultTheme/theme.json');
        
        if (file_exists($themeJsonPath)) {
            $themeJson = json_decode(file_get_contents($themeJsonPath), true);
            
            // 日本語の説明を取得（フォールバック: 英語）
            $description = $themeJson['description']['ja'] ?? $themeJson['description']['en'] ?? null;
            
            Theme::create([
                'name' => $themeJson['name'] ?? 'DixlaseDefaultTheme',
                'package_name' => $themeJson['package_name'] ?? null,
                'directory' => 'DixlaseDefaultTheme',
                'slug' => $themeJson['slug'] ?? 'dixlase-default-theme',
                'namespace' => $themeJson['namespace'] ?? null,
                'description' => $description,
                'license' => $themeJson['license'] ?? null,
                'author' => $themeJson['author'] ?? null,
                'email' => $themeJson['email'] ?? null,
                'url' => $themeJson['url'] ?? null,
                'version' => $themeJson['version'] ?? '1.0.0',
                'config' => [
                    'supports' => $themeJson['supports'] ?? [],
                    'customizable' => $themeJson['customizable'] ?? [],
                    'tags' => $themeJson['tags'] ?? [],
                    'requires' => $themeJson['requires'] ?? [],
                ],
                'installed_at' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ]);
        } else {
            // theme.jsonが存在しない場合は最小限の情報で作成
            Theme::create([
                'name' => 'DixlaseDefaultTheme',
                'slug' => 'dixlase-default-theme',
                'directory' => 'DixlaseDefaultTheme',
                'version' => '1.0.0',
                'installed_at' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}
