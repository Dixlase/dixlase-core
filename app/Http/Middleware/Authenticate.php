<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as BaseAuthenticate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Closure;
use Illuminate\Support\Facades\Log;

class Authenticate extends BaseAuthenticate
{

    /**
     * Handle an incoming request.
     *
     * Laravel 10/11 では、$guards パラメータが "[guard1, guard2, ...]" の形で渡ってくる
     * 何も指定しないとデフォルトガード
     */
    public function handle($request, Closure $next, ...$guards)
    {
        // まず "親クラス" の handle() に任せる
        // 親クラスでは「$this->authenticate($request, $guards)」をコールし、未認証なら unauthenticated() を呼ぶ
        // → unauthenticated() は redirectTo($request) を呼びだす


        $this->authenticate($request, $guards);

        return $next($request);
    }


    /**
     * 指定ガードで未認証だった場合にどこへリダイレクトするか
     *
     * 親クラスの unauthenticated() が呼ぶ
     *  → throw new AuthenticationException(..., $this->redirectTo($request));
     */

    protected function redirectTo(Request $request)
    {
        // JSONリクエストなら 401 (Unauthorized) レスポンスにする
        if ($request->expectsJson()) {
            return null;
        }

        // 管理画面URL("/admin"等)へアクセス時は "admin.login" へ

        if ($request->is('admin') || $request->is('admin/*')) {
            return route('admin.login');
        }


        // それ以外は "/login" へ
        return route('admin.login');
    }
}
