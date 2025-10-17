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
use App\Models\Media;
use Illuminate\Support\Facades\DB;
use Themes\DixlaseDefaultTheme\App\Http\Requests\UpdateThemeSettingsRequest;

class AdminThemeSettingsController extends AdminLoggedInController
{
    /**
     * テーマ設定画面を表示
     */
    public function settings()
    {
        // テーマ設定を取得
        $settings = DB::table('thm_dixlase_default_theme_settings')->first();
        
        // 設定が存在しない場合はデフォルト値を使用
        if (!$settings) {
            $settings = (object)[
                // Header
                'logo_url' => null,
                'logo_text' => config('app.name', 'Dixlase'),
                
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
        
        // ヒーロー背景画像のメディアを取得
        $heroBackgroundImage = null;
        if (isset($settings->hero_background_image_id)) {
            $heroBackgroundImage = Media::find($settings->hero_background_image_id);
        }
        
        $this->viewParams['settings'] = $settings;
        $this->viewParams['heroBackgroundImage'] = $heroBackgroundImage;
        
        return view('themes::admin.settings.themes.settings', $this->viewParams);
    }
    
    /**
     * テーマ設定を更新
     */
    public function update(UpdateThemeSettingsRequest $request)
    {
        $validated = $request->validated();
        
        // 設定を更新または作成
        $exists = DB::table('thm_dixlase_default_theme_settings')->exists();
        
        $data = array_merge($validated, ['updated_at' => now()]);
        
        if ($exists) {
            DB::table('thm_dixlase_default_theme_settings')->update($data);
        } else {
            $data['created_at'] = now();
            DB::table('thm_dixlase_default_theme_settings')->insert($data);
        }
        
        return redirect()
            ->route('admin.settings.themes.settings')
            ->with('success', __('themes::admin.settings.updated_successfully'));
    }
}
