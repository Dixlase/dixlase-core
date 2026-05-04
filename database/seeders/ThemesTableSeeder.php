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

use App\Models\Theme;
use Illuminate\Database\Seeder;

class ThemesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Load information from theme.json
        $themeJsonPath = base_path('themes/DixlaseOnePage/theme.json');

        if (file_exists($themeJsonPath)) {
            $themeJson = json_decode(file_get_contents($themeJsonPath), true);

            // Get Japanese description (fallback: English)
            $description = $themeJson['description']['ja'] ?? $themeJson['description']['en'] ?? null;

            Theme::create([
                'name' => $themeJson['name'] ?? 'DixlaseOnePage',
                'package_name' => $themeJson['package_name'] ?? null,
                'directory' => 'DixlaseOnePage',
                'slug' => $themeJson['slug'] ?? 'dixlase-one-page',
                'namespace' => $themeJson['namespace'] ?? null,
                'description' => $description,
                'license' => $themeJson['license'] ?? null,
                'author' => $themeJson['author'] ?? null,
                'email' => $themeJson['email'] ?? null,
                'url' => $themeJson['url'] ?? null,
                'version' => $themeJson['version'] ?? '1.0.0',
                'has_settings' => true,
                'config' => [
                    'supports' => $themeJson['supports'] ?? [],
                    'customizable' => $themeJson['customizable'] ?? [],
                    'tags' => $themeJson['tags'] ?? [],
                    'requires' => $themeJson['requires'] ?? [],
                ],
                'installed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            // Create with minimal information if theme.json does not exist
            Theme::create([
                'name' => 'DixlaseOnePage',
                'slug' => 'dixlase-one-page',
                'directory' => 'DixlaseOnePage',
                'version' => '1.0.0',
                'has_settings' => true,
                'installed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
