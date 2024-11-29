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

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;

class AdminController extends Controller
{
    //変数を宣言する
    protected $site_name;
    protected $theme = 'light';
    protected $theme_class;
    protected $title = '';
    protected $view_params = [];
    protected $route_name = '';
    protected $settings = [];

    //
    //初期設定を行う
    public function __construct()
    {


        // データベースからサイト名を取得。取得できなかった場合は.envからデフォルト値を使用
        $this->site_name = DB::table('settings_system')->where('name', 'site_name')->value('value')
            ?? env('APP_NAME', 'EventManagementSystem');

        // データベースからテーマ情報を取得。取得できなかった場合はコンフィグからデフォルト値を使用
        $this->theme = DB::table('settings_system')->where('name', 'admin_theme')->value('value')
            ?? Config::get('admin.theme', 'light'); // デフォルト値を 'light' に設定

        // データベースからシステム設定を取得
        $this->settings = DB::table('settings_system')->get()->keyBy('name')->toArray();




        //ビューパラメータにサイト名を設定する
        $this->view_params['site_name'] = $this->site_name;

        //ビューパラメータにシステム設定を設定する
        $this->view_params['settings'] = $this->settings;


        //テーマを設定する
        $this->setTheme();

        // ルート名を取得
        $this->route_name = Route::currentRouteName();
        $this->view_params['route_name'] = $this->route_name;
    }
    public function setTheme()
    {
        //テーマクラスを設定する
        if ($this->theme == 'light') {
            $this->theme_class = config('app.theme_class_light');
        } else {
            $this->theme_class = config('app.theme_class_dark');
        }

        //ビューパラメータにテーマを設定する
        $this->view_params['theme'] = $this->theme;
        $this->view_params['theme_class'] = $this->theme_class;
        $this->view_params['isDark'] = $this->theme == 'dark';
    }
}
