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

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FrontController extends Controller
{
    //

    //変数を宣言する
    protected $siteName;
    protected $appearance = 'light';
    protected $viewParams = [];
    //protected $currentTheme = 'default';

    public function __construct()
    {
        /*
        $this->siteName = config('custom.site_name');
        $this->appearance = config('custom.appearance');
        $this->currentTheme = config('custom.default_theme');
        $this->viewParams = [
            'site_name' => $this->site_name,
            'appearance' => $this->appearance,
        ];
        */
    }

    /*
    //ビューのパスを取得する
    public function getViewPath($view_name)
    {
        $view_path = $this->current_theme . '.' . $view_name;
        if (!file_exists(str_replace('.', '/', resource_path('views/' . $view_path . '.blade.php')))) {
            $view_path = 'themes.' . config('custom.default_theme') . '.' . $view_name;
        }
        return $view_path;
    }
    */
}
