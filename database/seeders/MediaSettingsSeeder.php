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
            ['name' => 'allowed_file_types', 'value' => '["jpg","png","gif","mp4","pdf","docx"]'],
            ['name' => 'max_file_size', 'value' => '2048'], // 単位: KB
            ['name' => 'storage_disk', 'value' => 'public'],
            ['name' => 'generate_thumbnails', 'value' => '1'], // 1: 有効, 0: 無効
        ];

        foreach ($defaultSettings as $setting) {
            MediaSetting::updateOrCreate(['name' => $setting['name']], $setting);
        }
    }
}
