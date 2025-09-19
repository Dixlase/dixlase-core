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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\App;
use App\Helpers\ConfigHelper;
use App\Enums\Locale;

class SetMemberLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            // .envファイルが存在し、インストール済みかつ管理画面でログイン済みの場合のみ処理
            if (file_exists(base_path('.env')) && env('INSTALLED', false) && $request->is('admin*') && Auth::guard('member')->check()) {
                $member = Auth::guard('member')->user();
                
                // メンバーの個別言語設定があればそれを使用、なければシステムデフォルト
                $locale = $member->locale ?? ConfigHelper::getAppLocale();
                
                // 利用可能な言語かチェック
                if (Locale::isValid($locale)) {
                    App::setLocale($locale);
                }
            } elseif ($request->is('install*')) {
                // インストール画面の場合はセッションから言語を取得
                try {
                    $installLocale = session('install_locale', 'ja');
                    if (Locale::isValid($installLocale)) {
                        App::setLocale($installLocale);
                    }
                } catch (\Exception $sessionError) {
                    // セッションエラーの場合はデフォルト言語を使用
                    App::setLocale('ja');
                }
            }
        } catch (\Exception $e) {
            // データベースエラーやその他のエラーが発生した場合はログに記録してスキップ
            \Log::warning('SetMemberLocale middleware error: ' . $e->getMessage());
            
            // フォールバック: デフォルト言語を設定
            App::setLocale(config('app.locale', 'ja'));
        }

        return $next($request);
    }
}
