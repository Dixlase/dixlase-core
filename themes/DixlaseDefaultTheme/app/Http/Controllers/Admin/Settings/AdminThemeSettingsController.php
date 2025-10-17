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
use Themes\DixlaseDefaultTheme\App\Http\Requests\UpdateThemeSettingsRequest;
use Themes\DixlaseDefaultTheme\App\Models\ThemeSetting;

class AdminThemeSettingsController extends AdminLoggedInController
{
    /**
     * テーマ設定画面を表示
     */
    public function settings()
    {
        // すべての設定をキーバリュー形式で取得
        $settingsData = ThemeSetting::getValues([
            'header_logo_id',
            'favicon_id',
            'hero_background_image_id',
            'hero_main_title',
            'hero_sub_title',
            'hero_button_text',
            'hero_button_link',
            'hero_button_secondary_text',
            'hero_button_secondary_link',
            'footer_description',
            'footer_links',
            'footer_copyright',
            'footer_sns_instagram',
            'footer_sns_x',
            'footer_sns_facebook',
            'footer_sns_tiktok',
            'footer_sns_bluesky',
            'footer_sns_threads',
            'footer_sns_linkedin',
            'footer_sns_youtube',
            'footer_sns_pinterest',
            'footer_sns_discord',
            'primary_color',
            'secondary_color',
            'accent_color',
        ]);
        
        // デフォルト値を設定
        $defaults = [
            'header_logo_id' => null,
            'favicon_id' => null,
            'hero_background_image_id' => null,
            'hero_main_title' => 'Welcome to ' . config('app.name', 'Dixlase'),
            'hero_sub_title' => 'Modern CMS Platform for Building Amazing Websites',
            'hero_button_text' => 'Get Started',
            'hero_button_link' => '#',
            'hero_button_secondary_text' => 'Learn More',
            'hero_button_secondary_link' => '#features',
            'footer_description' => 'Powered by Dixlase CMS',
            'footer_links' => '[]',
            'footer_copyright' => '© ' . date('Y') . ' ' . config('app.name', 'Dixlase') . '. All rights reserved.',
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
            'primary_color' => '#3b82f6',
            'secondary_color' => '#6b7280',
            'accent_color' => '#10b981',
        ];
        
        // デフォルト値とマージ
        $settingsData = array_merge($defaults, array_filter($settingsData, fn($v) => $v !== null));
        
        // オブジェクトに変換
        $settings = (object) $settingsData;
        
        // JSON文字列をデコード
        if (isset($settings->footer_links) && is_string($settings->footer_links)) {
            $settings->footer_links = json_decode($settings->footer_links, true) ?? [];
        }
        
        // メディアを取得
        $headerLogo = null;
        if (isset($settings->header_logo_id)) {
            $headerLogo = Media::find($settings->header_logo_id);
        }
        
        $favicon = null;
        if (isset($settings->favicon_id)) {
            $favicon = Media::find($settings->favicon_id);
        }
        
        $heroBackgroundImage = null;
        if (isset($settings->hero_background_image_id)) {
            $heroBackgroundImage = Media::find($settings->hero_background_image_id);
        }
        
        $this->viewParams['settings'] = $settings;
        $this->viewParams['headerLogo'] = $headerLogo;
        $this->viewParams['favicon'] = $favicon;
        $this->viewParams['heroBackgroundImage'] = $heroBackgroundImage;
        
        return view('themes::admin.settings.themes.settings', $this->viewParams);
    }
    
    /**
     * テーマ設定を更新
     */
    public function update(UpdateThemeSettingsRequest $request)
    {
        $validated = $request->validated();
        
        // キーバリュー形式で保存
        ThemeSetting::setValues($validated);
        
        // キャッシュをクリア
        ThemeSetting::clearAllCache();
        
        return redirect()
            ->route('admin.settings.themes.settings')
            ->with('success', __('themes::admin.settings.updated_successfully'));
    }
}
