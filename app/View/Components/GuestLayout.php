<?php

/**
 * This file is part of MySoftware.
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

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

class GuestLayout extends Component
{
    protected $site_name;
    protected $appearance = 'light';
    protected $theme_class;
    protected $title = '';
    protected $view_params = [];
    protected $theme = '';

    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {

        // データベースからサイト名を取得。取得できなかった場合は.envからデフォルト値を使用
        $this->site_name = DB::table('base_settings')->where('name', 'site_name')->value('value')
            ?? env('APP_NAME', 'EventManagementSystem');

        // データベースからテーマ情報を取得。取得できなかった場合はコンフィグからデフォルト値を使用
        $this->theme = DB::table('base_settings')->where('name', 'admin_theme')->value('value')
            ?? Config::get('admin.theme', 'light'); // デフォルト値を 'light' に設定

        //テーマクラスを設定する
        if ($this->theme == 'light') {
            $this->theme_class = config('admin.theme_class_light');
        } else {
            $this->theme_class = config('admin.theme_class_dark');
        }
        $isDark = $this->theme === 'dark';


        $this->view_params = [
            'site_name' => $this->site_name,
            'theme' => $this->theme,
            'theme_class' => $this->theme_class,
            'isDark' => $isDark,
        ];


        return view(
            'layouts.guest',
            $this->view_params
        );
    }
}
