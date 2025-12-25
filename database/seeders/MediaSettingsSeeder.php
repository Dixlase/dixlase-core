<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\MediaSetting;

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

        foreach ($defaultSettings as $setting) {
            MediaSetting::updateOrCreate(['name' => $setting['name']], $setting);
        }
    }
}
