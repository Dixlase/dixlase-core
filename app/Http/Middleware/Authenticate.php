<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE.commercial, or contact office@exc-d.com).
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the AGPL terms below.
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

use Closure;
use Illuminate\Auth\Middleware\Authenticate as BaseAuthenticate;
use Illuminate\Http\Request;

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
            return;
        }

        // 管理画面URL（動的に生成された管理画面URLにも対応）へアクセス時は "admin.login" へ
        if (\App\Helpers\AdminHelper::isAdminRequest($request)) {
            return route('admin.login');
        }

        // それ以外は "/login" へ
        return route('admin.login');
    }
}
