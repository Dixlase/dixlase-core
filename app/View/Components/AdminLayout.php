<?php

/**
 * This file is part of Your Software Name.
 *
 * Copyright (C) 2024 exc-D inc.
 * Website: https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

class AdminLayout extends Component
{
    protected $siteName;
    protected $theme = 'light';
    protected $theme_class;
    protected $title = '';
    protected $viewParams = [];


    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        // データベースからサイト名を取得。取得できなかった場合は.encからデフォルト値を使用
        $this->siteName = DB::table('settings_system')->where('name', 'site_name')->value('value')
            ?? env('APP_NAME', 'EventManagementSystem');

        // データベースからテーマ情報を取得。取得できなかった場合はコンフィグからデフォルト値を使用
        $this->theme = DB::table('settings_system')->where('name', 'admin_theme')->value('value')
            ?? Config::get('admin.theme', 'light'); // デフォルト値を 'light' に設定

        //テーマクラスを設定する
        if ($this->theme == 'light') {
            $this->theme_class = config('admin.theme_class_light');
        } else {
            $this->theme_class = config('admin.theme_class_dark');
        }
        $isDark = $this->theme === 'dark';


        $this->viewParams = [
            'siteName' => $this->siteName,
            'theme' => $this->theme,
            'theme_class' => $this->theme_class,
            'isDark' => $isDark,
        ];

        return view('admin.partials.layout', $this->viewParams);
    }
    /**
     * サブメニューが存在するかをチェックする関数
     *
     * @param array $item メニュー項目
     * @return bool サブメニューが存在する場合は true
     */
    function hasSubmenu(array $item): bool
    {
        return isset($item['children']) && is_array($item['children']);
    }
}
