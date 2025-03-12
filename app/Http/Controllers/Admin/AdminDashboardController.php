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

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\AdminLoggedInController;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AdminDashboardController extends AdminLoggedInController
{
    //初期設定を行う
    public function __construct()
    {
        parent::__construct();
    }
    //
    public function index()
    {
        Log::info('管理者情報:', [
            'ID' => $this->member->id,
            '名前' => $this->member->name,
            'メール' => $this->member->email,
            '役割' => $this->member->role,
            '外観設定' => $this->member->appearance,
            'ステータス' => $this->member->status,
        ]);

        return view('admin::dashboard', $this->viewParams);
    }
}
