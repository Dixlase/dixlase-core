<?php

/**
 * This file is part of MySoftware.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
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


namespace App\Http\Controllers\Admin;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use App\Traits\RoleCheckTrait;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;

class AdminController extends Controller
{
    //変数を宣言する
    protected $siteName;
    protected $appearance = 'light';
    protected $heading = '';
    protected $viewParams = [];
    protected $routeName = '';
    protected $settings = [];

    //トレイトを使用する
    use AuthorizesRequests;
    use RoleCheckTrait;

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
        // データベースからサイト名を取得。取得できなかった場合は.envからデフォルト値を使用
        $this->getSiteName();

        // 外観モードを取得
        $this->getAppearance();

        // 基本設定を取得
        $this->getBaseSettings();

        // ルート名を取得
        $this->setRouteName();

        // ヘッダーを設定
        $this->setHeading();
    }



    //データベースからサイト名を取得。取得できなかった場合は.envからデフォルト値を使用
    protected function getSiteName()
    {
        $this->siteName = DB::table('base_settings')->where('name', 'site_name')->value('value')
            ?? env('APP_NAME', 'EventManagementSystem');
        $this->viewParams['site_name'] = $this->siteName;
    }

    //外観モードを取得
    protected function getAppearance()
    {
        $this->appearance = $this->viewParams['member']['appearance'] ?? 0;
        $this->viewParams['appearance'] = $this->appearance;
    }

    //システム設定を取得
    protected function getBaseSettings()
    {
        $this->settings = DB::table('base_settings')->get()->keyBy('name')->toArray();
        $this->viewParams['settings'] = $this->settings;
    }

    //　ルート名を取得
    protected function setRouteName()
    {
        $this->routeName = Route::currentRouteName();
        $this->viewParams['route_name'] = $this->routeName;
    }

    //ヘッダーを設定する
    protected function setHeading()
    {
        $routeName = Route::currentRouteName();
        $keys = explode('.', $routeName); // 例: ['admin', 'media', 'index']
        array_shift($keys); // 'admin'部分を削除

        $headingKey = implode('.', $keys) . '.heading';
        $heading = Lang::get('admin.' . $headingKey);

        $this->heading = $heading ?: 'No Heading';
        $this->viewParams['heading'] = $this->heading;
    }
}
