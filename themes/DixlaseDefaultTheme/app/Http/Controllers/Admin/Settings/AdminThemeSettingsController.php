<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * https://exc-d.com
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

namespace Themes\DixlaseDefaultTheme\App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminLoggedInController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminThemeSettingsController extends AdminLoggedInController
{
    /**
     * テーマ設定画面を表示
     */
    public function settings()
    {
        // テーマ設定を取得
        $settings = DB::table('dixlase_default_theme_settings')->first();
        
        // 設定が存在しない場合はデフォルト値を使用
        if (!$settings) {
            $settings = (object)[
                // Header
                'logo_url' => null,
                'logo_text' => config('app.name', 'Dixlase'),
                
                // Hero Section
                'hero_background_image' => null,
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
                'footer_sns_facebook' => null,
                'footer_sns_twitter' => null,
                'footer_sns_instagram' => null,
                'footer_sns_linkedin' => null,
                'footer_sns_youtube' => null,
                
                // Colors
                'primary_color' => '#3b82f6',
                'secondary_color' => '#6b7280',
                'accent_color' => '#10b981',
            ];
        }
        
        // JSON文字列をデコード
        if (isset($settings->footer_links) && is_string($settings->footer_links)) {
            $settings->footer_links = json_decode($settings->footer_links, true) ?? [];
        }
        
        $this->viewParams['settings'] = $settings;
        
        return view('themes::admin.settings.themes.settings', $this->viewParams);
    }
    
    /**
     * テーマ設定を更新
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            // Header
            'logo_url' => 'nullable|string|max:500',
            'logo_text' => 'required|string|max:255',
            
            // Hero Section
            'hero_background_image' => 'nullable|string|max:500',
            'hero_main_title' => 'required|string|max:255',
            'hero_sub_title' => 'nullable|string|max:1000',
            'hero_button_text' => 'nullable|string|max:100',
            'hero_button_link' => 'nullable|string|max:500',
            'hero_button_secondary_text' => 'nullable|string|max:100',
            'hero_button_secondary_link' => 'nullable|string|max:500',
            
            // Footer
            'footer_description' => 'nullable|string|max:1000',
            'footer_links' => 'nullable|array',
            'footer_links.*.title' => 'required|string|max:100',
            'footer_links.*.url' => 'required|string|max:500',
            'footer_copyright' => 'nullable|string|max:500',
            'footer_sns_facebook' => 'nullable|url|max:500',
            'footer_sns_twitter' => 'nullable|url|max:500',
            'footer_sns_instagram' => 'nullable|url|max:500',
            'footer_sns_linkedin' => 'nullable|url|max:500',
            'footer_sns_youtube' => 'nullable|url|max:500',
            
            // Colors
            'primary_color' => 'required|string|max:7',
            'secondary_color' => 'required|string|max:7',
            'accent_color' => 'required|string|max:7',
        ]);
        
        // footer_linksをJSON文字列に変換
        if (isset($validated['footer_links'])) {
            $validated['footer_links'] = json_encode($validated['footer_links']);
        }
        
        // 設定を更新または作成
        $exists = DB::table('dixlase_default_theme_settings')->exists();
        
        $data = array_merge($validated, ['updated_at' => now()]);
        
        if ($exists) {
            DB::table('dixlase_default_theme_settings')->update($data);
        } else {
            $data['created_at'] = now();
            DB::table('dixlase_default_theme_settings')->insert($data);
        }
        
        return redirect()
            ->route('admin.settings.themes.settings')
            ->with('success', __('themes::admin.settings.updated_successfully'));
    }
}
