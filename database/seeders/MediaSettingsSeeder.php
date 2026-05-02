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
 *       Dixlase Plugin and Theme Exception (see LICENSE
 *       for full exception terms); or
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
            // 基本設定
            ['name' => 'allowed_file_types', 'value' => '["jpg","png","gif","webp","mp4"]'], // 注：PDF/DOCX/SVG/ZIPはセキュリティリスクのため初期値には含めない
            ['name' => 'max_file_size', 'value' => '2048'], // 単位: KB（レガシー互換用）
            ['name' => 'storage_disk', 'value' => 'public'],
            ['name' => 'generate_thumbnails', 'value' => '1'], // 1: 有効, 0: 無効

            // ファイルタイプ別サイズ上限（KB）
            ['name' => 'max_file_size_image', 'value' => '10240'],      // 10MB
            ['name' => 'max_file_size_video', 'value' => '307200'],     // 300MB
            ['name' => 'max_file_size_document', 'value' => '30720'],   // 30MB
            ['name' => 'max_file_size_archive', 'value' => '102400'],   // 100MB

            // セキュリティ設定
            ['name' => 'svg_sanitization_enabled', 'value' => '1'],     // SVGサニタイズ有効
            ['name' => 'zip_security_enabled', 'value' => '1'],         // ZIPセキュリティチェック有効
            ['name' => 'mime_validation_enabled', 'value' => '1'],      // MIME実体検証有効

            // ZIP詳細設定
            ['name' => 'zip_max_compression_ratio', 'value' => '100'],  // 最大圧縮率（ZIP爆弾対策）
            ['name' => 'zip_max_file_count', 'value' => '1000'],        // ZIP内最大ファイル数
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
