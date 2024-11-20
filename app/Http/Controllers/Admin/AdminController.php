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

class AdminController extends Controller
{
    //変数を宣言する
    protected $theme = 'light';
    protected $theme_class;
    protected $viewParams = [];

    //
    //初期設定を行う
    public function __construct()
    {
        //テーマを設定する
        $this->setTheme();
    }
    public function setTheme()
    {
        //テーマをコンフィグから取得する
        $this->theme = config('app.theme');
        //テーマクラスを設定する
        if ($this->theme == 'light') {
            $this->theme_class = config('app.theme_class_light');
        } else {
            $this->theme_class = config('app.theme_class_dark');
        }

        //ビューパラメータにテーマを設定する
        $this->viewParams['theme'] = $this->theme;
        $this->viewParams['theme_class'] = $this->theme_class;
        $this->viewParams['isDark'] = $this->theme == 'dark';
    }
}
