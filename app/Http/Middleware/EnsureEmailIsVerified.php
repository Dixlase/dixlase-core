<?php

/**
 * This file is part of Dixlase.
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

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\URL;

class EnsureEmailIsVerified
{
    /**
     * Specify the redirect route for the middleware.
     *
     * @param  string  $route
     * @return string
     */
    public static function redirectTo($route)
    {
        return static::class . ':' . $route;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $redirectToRoute
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse|null
     */
    public function handle($request, Closure $next, $redirectToRoute = null)
    {
        // 管理画面の場合はmemberガードを使用
        // ※マイページ（user）の場合はプラグイン側のEnsureUserEmailIsVerifiedを使用
        $guard = $request->is('admin/*') ? 'member' : 'web';
        $user = auth($guard)->user();
        
        if (
            ! $user ||
            ($user instanceof MustVerifyEmail &&
                ! $user->hasVerifiedEmail())
        ) {
            // 未認証の場合
            if ($request->expectsJson()) {
                return abort(403, 'Your email address is not verified.');
            }
            
            // 管理画面の場合は専用の未認証ページへ
            if ($request->is('admin/*')) {
                return redirect()->route('admin.verification.notice');
            }
            
            // カスタムルートが指定されている場合
            if ($redirectToRoute) {
                return Redirect::guest(URL::route($redirectToRoute));
            }
            
            // デフォルトはホームへリダイレクト
            return Redirect::guest('/');
        }

        return $next($request);
    }
}
