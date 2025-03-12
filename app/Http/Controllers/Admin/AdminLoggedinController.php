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

use App\Http\Controllers\Admin\AdminController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AdminLoggedInController extends AdminController
{
    protected $member;

    public function __construct()
    {
        parent::__construct();

        // ミドルウェアが適用された後に `setMember()` を実行
        $this->middleware(function ($request, $next) {
            $this->setMember();
            return $next($request);
        });
    }

    //管理者情報を取得
    protected function setMember()
    {
        $this->member = Auth::guard('member')->user();
        $this->viewParams['member'] = $this->member;
    }
}
