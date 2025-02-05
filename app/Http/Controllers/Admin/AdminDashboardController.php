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

use App\Http\Controllers\Admin\AdminController;
use Illuminate\Support\Facades\Lang;

class AdminDashboardController extends AdminController
{
    //初期設定を行う
    public function __construct()
    {
        parent::__construct();
    }
    //
    public function index()
    {
        // 配列全体を取得
        $translations = Lang::get('event-plugin::admin');

        // 出力
        //dd($translations);

        $this->viewParams['heading'] = 'admin.features.dashboard.heading';
        return view('admin::dashboard', $this->viewParams);
    }
}
