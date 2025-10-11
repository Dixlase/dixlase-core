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
        // テーマ設定を取得（例: theme_settingsテーブルから）
        $settings = DB::table('dixlase_default_theme_settings')->first();
        
        // 設定が存在しない場合はデフォルト値を使用
        if (!$settings) {
            $settings = (object)[
                'primary_color' => '#3b82f6',
                'secondary_color' => '#6b7280',
                'accent_color' => '#10b981',
                'logo_text' => config('app.name', 'Dixlase'),
                'show_search' => true,
                'footer_text' => '© ' . date('Y') . ' ' . config('app.name', 'Dixlase'),
            ];
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
            'primary_color' => 'required|string|max:7',
            'secondary_color' => 'required|string|max:7',
            'accent_color' => 'required|string|max:7',
            'logo_text' => 'required|string|max:255',
            'show_search' => 'boolean',
            'footer_text' => 'nullable|string|max:500',
        ]);
        
        // show_searchのチェックボックス処理
        $validated['show_search'] = $request->has('show_search') ? 1 : 0;
        
        // 設定を更新または作成
        $exists = DB::table('dixlase_default_theme_settings')->exists();
        
        if ($exists) {
            DB::table('dixlase_default_theme_settings')->update([
                'primary_color' => $validated['primary_color'],
                'secondary_color' => $validated['secondary_color'],
                'accent_color' => $validated['accent_color'],
                'logo_text' => $validated['logo_text'],
                'show_search' => $validated['show_search'],
                'footer_text' => $validated['footer_text'],
                'updated_at' => now(),
            ]);
        } else {
            DB::table('dixlase_default_theme_settings')->insert([
                'primary_color' => $validated['primary_color'],
                'secondary_color' => $validated['secondary_color'],
                'accent_color' => $validated['accent_color'],
                'logo_text' => $validated['logo_text'],
                'show_search' => $validated['show_search'],
                'footer_text' => $validated['footer_text'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        return redirect()
            ->route('admin.settings.themes.settings')
            ->with('success', __('Theme settings updated successfully'));
    }
}
