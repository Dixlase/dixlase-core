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
 *       (see LICENSE.commercial, or contact info@dixlase.org).
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

use App\Models\MediaSetting;
use Illuminate\Database\Seeder;

class MediaSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultSettings = [
            // Basic settings
            ['name' => 'allowed_file_types', 'value' => '["jpg","png","gif","webp","mp4"]'], // Note: PDF/DOCX/SVG/ZIP are not included in default values due to security risks
            ['name' => 'max_file_size', 'value' => '2048'], // Unit: KB (for legacy compatibility)
            ['name' => 'storage_disk', 'value' => 'public'],
            ['name' => 'generate_thumbnails', 'value' => '1'], // 1: enabled, 0: disabled

            // Size limit per file type (KB)
            ['name' => 'max_file_size_image', 'value' => '10240'],      // 10MB
            ['name' => 'max_file_size_video', 'value' => '307200'],     // 300MB
            ['name' => 'max_file_size_document', 'value' => '30720'],   // 30MB
            ['name' => 'max_file_size_archive', 'value' => '102400'],   // 100MB

            // Security settings
            ['name' => 'svg_sanitization_enabled', 'value' => '1'],     // SVG sanitization enabled
            ['name' => 'zip_security_enabled', 'value' => '1'],         // ZIP security check enabled
            ['name' => 'mime_validation_enabled', 'value' => '1'],      // MIME content verification enabled

            // ZIP advanced settings
            ['name' => 'zip_max_compression_ratio', 'value' => '100'],  // Maximum compression ratio (ZIP bomb protection)
            ['name' => 'zip_max_file_count', 'value' => '1000'],        // Maximum file count inside ZIP
        ];

        $primarySiteId = 1;

        foreach ($defaultSettings as $setting) {
            MediaSetting::withoutGlobalScope('belongs_to_site')->updateOrCreate(
                ['name' => $setting['name'], 'site_id' => $primarySiteId],
                ['value' => $setting['value']]
            );
        }
    }
}
