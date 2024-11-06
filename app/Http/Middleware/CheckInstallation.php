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

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Artisan;

class CheckInstallation
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /*
        // .envファイルが存在しないかつ現在のURLがインストールURLでない場合にリダイレクト
        if (!file_exists(base_path('.env')) && !$request->is('install', 'install/*')) {
            return redirect('/install');
        }
        */




        // インストール済みであるかどうかを確認
        if (env('INSTALLED') !== true) {
            // .envファイルが存在しない場合、.env.example からコピーして生成
            if (!file_exists(base_path('.env'))) {
                copy(base_path('.env.example'), base_path('.env'));
            }

            // 仮のAPP_KEYが設定されていない場合、生成して追加
            if (empty(env('APP_KEY'))) {
                $temporaryKey = 'base64:' . base64_encode(random_bytes(32));
                file_put_contents(base_path('.env'), "\nAPP_KEY={$temporaryKey}", FILE_APPEND);

                // 環境設定を再適用
                Artisan::call('config:clear');
                Artisan::call('config:cache');
            }

            // インストール画面にリダイレクト
            if (!$request->is('install', 'install/*')) {
                return redirect('/install');
            }
        } else {
            // インストール済みの場合、インストール画面にはアクセスできないようにする
            if ($request->is('install', 'install/*')) {
                return redirect('/')->with('message', 'このアプリケーションは既にインストールされています。');
            }
        }

        return $next($request);
    }
}
