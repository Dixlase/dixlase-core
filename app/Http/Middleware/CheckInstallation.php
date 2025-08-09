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


    /**
     * ルート名からステップ番号を取得
     */
    private function getStepFromRoute($routeName)
    {
        $steps = [
            'install.index' => 0,
            'install.settings' => 1,
            'install.settings.store' => 1,
            'install.environment' => 2,
            'install.environment.store' => 2,
            'install.security' => 3,
            'install.security.store' => 3,
            'install.database' => 4,
            'install.database.store' => 4,
            'install.confirm' => 5,
            'install.confirm.store' => 5,
            'install.complete' => 6,
        ];
        
        return $steps[$routeName] ?? 0;
    }
    
    /**
     * 各ステップが完了しているか確認
     */
    private function isStepCompleted($step, $installData)
    {
        // Check if install_data is set in the session
        if (empty($installData)) {
            return false;
        }
        
        // Get the actual install data
        $data = $installData;
        
        switch ($step) {
            case 1: // 基本設定
                return isset($data['site_name']) && 
                       isset($data['admin_name']) && 
                       isset($data['admin_email']) && 
                       isset($data['admin_password']);
                
            case 2: // 環境設定
                return isset($data['app_env']) && 
                       isset($data['app_url']) && 
                       isset($data['app_timezone']);
                
            case 3: // セキュリティ設定
                return isset($data['admin_url']);
                
            case 4: // データベース設定
                return isset($data['db_connection']) && 
                       isset($data['db_host']) && 
                       isset($data['db_port']) && 
                       isset($data['db_database']) && 
                       isset($data['db_username']);
                
            default:
                return false;
        }
    }
    
    /**
     * ステップ番号に対応するルート名を取得
     */
    private function getRouteForStep($step)
    {
        $routes = [
            1 => 'install.index',     // 基本設定
            2 => 'install.environment', // 環境設定
            3 => 'install.security',  // セキュリティ設定
            4 => 'install.database',  // データベース設定
            5 => 'install.confirm',   // 確認画面
            6 => 'install.complete'   // 完了画面
        ];
        
        return $routes[$step] ?? 'install.index';
    }

    public function handle(Request $request, Closure $next): Response
    {
        // セッションから言語を設定
        if (session()->has('install_locale')) {
            app()->setLocale(session('install_locale'));
        }
        // `.env` ファイルのパスを取得
        $envPath = base_path('.env');

        // .envファイルが存在しない場合、.env.example からコピーして生成
        if (!file_exists(base_path('.env'))) {
            $copied = copy(base_path('.env.example'), base_path('.env'));
            if ($copied) {
                // ファイルのパーミッションを設定（必要に応じて）
                chmod(base_path('.env'), 0664);
                \Log::info('.env file was created from .env.example');
            } else {
                \Log::error('Failed to create .env file from .env.example');
                \Log::error('Current directory: ' . getcwd());
                \Log::error('Source exists: ' . (file_exists(base_path('.env.example')) ? 'yes' : 'no'));
                \Log::error('Destination writable: ' . (is_writable(base_path()) ? 'yes' : 'no'));
            }
        }

        // ✅ `.env` ファイルの内容を直接取得
        $envContent = file_get_contents($envPath);
        $appKeyExists = preg_match('/^APP_KEY=(.+)$/m', $envContent, $matches);

        // ✅ `APP_KEY` の設定がない、または空の場合のみ新規生成
        if (!$appKeyExists || empty(trim($matches[1] ?? ''))) {
            \Log::info('Generating new APP_KEY');
            $newKey = 'base64:' . base64_encode(random_bytes(32));
            $result = $this->updateAppKey($newKey);
            if ($result) {
                \Log::info('Successfully updated APP_KEY');
            } else {
                \Log::error('Failed to update APP_KEY');
            }
        } else {
            \Log::info('APP_KEY already exists');
        }

        // インストール済みであるかどうかを確認
        if (env('INSTALLED') !== true) {


            // 現在のルート名を取得
            $currentRoute = $request->route() ? $request->route()->getName() : null;
            
            // インストール関連のルート以外はインデックスにリダイレクト
            if (!$request->is('install*') && !$request->is('install/*')) {
                return redirect()->route('install.index');
            }

            
            // インストール関連のルートの場合、ステップの検証を行う
            if ($currentRoute && $request->is('install*')) {
                $currentStep = $this->getStepFromRoute($currentRoute);
                
                // インデックスページは常に許可
                if ($currentStep > 0) {
                    $installData = session()->all();
                    
                    // リクエストがPOSTの場合は、バリデーション前にステップチェックをスキップ
                    if ($request->isMethod('post')) {
                        return $next($request);
                    }
                    
                    // 必要な前のステップが完了しているか確認
                    for ($i = 1; $i < $currentStep; $i++) {
                        if (!$this->isStepCompleted($i, $installData)) {
                            // 不足しているステップに応じて適切なルートにリダイレクト
                            $targetRoute = $this->getRouteForStep($i);
                            return redirect()->route($targetRoute)
                                ->with('error', __('install.please_complete_previous_steps'));
                        }
                    }
                    
                    // 現在のステップが完了しているか確認（リロード防止）
                    if ($currentStep > 1 && !$this->isStepCompleted($currentStep, $installData)) {
                        $targetRoute = $this->getRouteForStep($currentStep);
                        return redirect()->route($targetRoute)
                            ->with('error', __('install.please_complete_this_step'));
                    }
                }
            }

            // ✅ `/install/finalize` だけはスルー（サイトへ移動時の処理）
            if ($request->is('install/finalize')) {
                return $next($request);
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
