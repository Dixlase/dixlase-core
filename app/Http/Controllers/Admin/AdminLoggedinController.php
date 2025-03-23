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
use Illuminate\Support\Facades\Route;



class AdminLoggedInController extends AdminController
{
    protected $member;

    public function __construct()
    {
        parent::__construct();

        // ミドルウェア
        $this->middleware(function ($request, $next) {
            // ログインユーザー情報を取得
            $this->setMember();

            // 管理画面アクセスログ
            /*
            if (Auth::check() && Auth::user()->is_admin) {
                Log::channel('admin_activity')->info('管理画面アクセス', [
                    'id' => Auth::id(),
                    'name' => Auth::user()->name,
                    'method' => $request->method(),
                    'route' => Route::currentRouteName(),
                    'controller' => Route::currentRouteAction(),
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'time' => now()->toDateTimeString(),
                ]);
            }
            */

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
