<?php

namespace Themes\DixlaseDefaultTheme\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DixlaseDefaultThemeSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 既存のデータがあるかチェック
        if (DB::table('thm_dixlase_default_theme_settings')->exists()) {
            $this->command->info('Dixlase Default Theme settings already exist. Skipping...');
            return;
        }

        DB::table('thm_dixlase_default_theme_settings')->insert([
            // Hero Section
            'hero_background_image_id' => null,
            'hero_main_title' => 'Welcome to ' . config('app.name', 'Dixlase'),
            'hero_sub_title' => 'Modern CMS Platform for Building Amazing Websites',
            'hero_button_text' => 'Get Started',
            'hero_button_link' => '#',
            'hero_button_secondary_text' => 'Learn More',
            'hero_button_secondary_link' => '#features',
            
            // Footer
            'footer_description' => 'Powered by Dixlase CMS',
            'footer_links' => json_encode([]),
            'footer_copyright' => '© ' . date('Y') . ' ' . config('app.name', 'Dixlase') . '. All rights reserved.',
            
            // SNS Links
            'footer_sns_instagram' => null,
            'footer_sns_x' => null,
            'footer_sns_facebook' => null,
            'footer_sns_tiktok' => null,
            'footer_sns_bluesky' => null,
            'footer_sns_threads' => null,
            'footer_sns_linkedin' => null,
            'footer_sns_youtube' => null,
            'footer_sns_pinterest' => null,
            'footer_sns_discord' => null,
            
            // Colors
            'primary_color' => '#3b82f6',
            'secondary_color' => '#6b7280',
            'accent_color' => '#10b981',
            
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->command->info('Dixlase Default Theme settings created successfully!');
    }
}
