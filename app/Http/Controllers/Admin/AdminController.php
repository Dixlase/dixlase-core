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

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use App\Traits\RoleCheck;

class AdminController extends Controller
{
    //変数を宣言する
    protected $siteName;
    protected $appearance = 'light';
    protected $heading = '';
    protected $viewParams = [];
    protected $routeName = '';
    protected $settings = [];
    protected $member = null;

    //トレイトを使用する
    use AuthorizesRequests;
    use RoleCheck;

    //初期設定を行う
    public function __construct()
    {

        $this->initialize();
    }

    /**
     * 初期化処理
     */
    public function initialize()
    {
        // ログイン中の管理者情報を取得
        $this->member = Auth::guard('member')->user();

        // データベースからサイト名を取得。取得できなかった場合は.envからデフォルト値を使用
        $this->siteName = DB::table('settings_system')->where('name', 'site_name')->value('value')
            ?? env('APP_NAME', 'EventManagementSystem');

        // データベースからサイト名を取得
        $this->siteName = DB::table('settings_system')->where('name', 'site_name')->value('value')
            ?? env('APP_NAME', 'EventManagementSystem');

        // 外観モードを取得
        $this->appearance = $this->member->appearance ?? 0;

        // システム設定を取得
        $this->settings = DB::table('settings_system')->get()->keyBy('name')->toArray();

        // ビューパラメータに必要な値を設定
        $this->viewParams = [
            'site_name' => $this->siteName,
            'settings' => $this->settings,
            'appearance' => $this->appearance,
            'member' => $this->member,
            'route_name' => Route::currentRouteName(),
        ];
    }
}
