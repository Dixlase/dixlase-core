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

namespace App\Http\Controllers\Mypage;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

class MypageController extends Controller
{
    //
    //変数を宣言する
    protected $siteName;
    protected $appearance = 'light';
    protected $heading = '';
    protected $viewParams = [];
    protected $routeName = '';
    protected $settings = [];

    //初期設定を行う
    public function __construct()
    {
        // データベースからサイト名を取得。取得できなかった場合は.envからデフォルト値を使用
        $this->siteName = DB::table('base_settings')->where('name', 'site_name')->value('value')
            ?? env('APP_NAME', 'Dixlase');

        //ログイン中の管理者情報を取得
        $admin = auth('web')->user();

        //ログイン中の管理者の外観モードをDBから取得
        $this->appearance = $admin->appearance ?? 0;

        // データベースからシステム設定を取得
        $this->settings = DB::table('base_settings')->get()->keyBy('name')->toArray();

        //ビューパラメータにサイト名を設定する
        $this->viewParams['site_name'] = $this->siteName;

        //ビューパラメータにシステム設定を設定する
        $this->viewParams['settings'] = $this->settings;

        //ビューパラメータにテーマを設定する
        $this->viewParams['appearance'] = $this->appearance;

        //セッションにテーマを保存する
        session(['appearance' => $this->appearance]);

        // ルート名を取得
        $this->routeName = Route::currentRouteName();
        $this->viewParams['route_name'] = $this->routeName;
    }
}
