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
        // インストール済みであるかどうかを確認
        if (env('INSTALLED') !== true) {
            // .envファイルが存在しない場合、.env.example からコピーして生成
            if (!file_exists(base_path('.env'))) {
                copy(base_path('.env.example'), base_path('.env'));
            }

            // 仮のAPP_KEYが設定されていない場合、生成して追加
            if (empty(env('APP_KEY'))) {
                $newKey = 'base64:' . base64_encode(random_bytes(32));
                $this->updateAppKey($newKey);
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

    protected function updateAppKey($newKey)
    {
        // .envファイルの読み込み
        $envPath = base_path('.env');
        $envContent = file_exists($envPath) ? file_get_contents($envPath) : '';

        // APP_KEYの設定が存在する場合、置換
        if (preg_match("/^APP_KEY=.*$/m", $envContent)) {
            $envContent = preg_replace("/^APP_KEY=.*$/m", "APP_KEY={$newKey}\n", $envContent);
        } else {
            // APP_KEYの設定が存在しない場合、追加
            $envContent .= "APP_KEY={$newKey}\n";
        }
        // .envファイルに書き込み
        file_put_contents($envPath, $envContent);

        Artisan::call('config:clear');
        Artisan::call('config:cache');
    }
}
