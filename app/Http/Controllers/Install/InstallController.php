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

namespace App\Http\Controllers\Install;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;



class InstallController extends Controller
{
    // 最初の画面
    public function index()
    {
        return view('install.welcome');
    }

    // サイト設定の入力画面
    public function create()
    {
        return view('install.settings');
    }

    // サイト設定の入力処理
    public function store(Request $request)
    {
        $request->validate([
            'site_name' => 'required|string|max:255',
            'admin_email' => 'required|email',
            'admin_password' => 'required|string|min:8|confirmed',
            'db_host' => 'required|string',
            'db_database' => 'required|string',
            'db_username' => 'required|string',
            'db_password' => 'nullable|string',
        ]);

        $installData = [
            'db_connection' => $request->db_connection,
            'site_name' => $request->site_name,
            'admin_email' => $request->admin_email,
            'admin_password' => $request->admin_password,
        ];

        if ($request->db_connection === 'mysql') {
            $installData['db_host'] = $request->db_host;
            $installData['db_database'] = $request->db_database;
            $installData['db_username'] = $request->db_username;
            $installData['db_password'] = $request->db_password;
        } elseif ($request->db_connection === 'sqlite') {
            $installData['db_database'] = $request->db_database_sqlite;
        }

        // 入力内容をセッションに保存
        session(['install_data' => $installData]);

        return redirect()->route('install.confirm');
    }

    // 入力内容の確認画面
    public function confirm()
    {
        $data = session('install_data');
        return view('install.confirm', compact('data'));
    }

    // 確認画面の処理
    public function storeConfirm()
    {
        $data = session('install_data');

        // .envファイルの更新やインストール処理
        $envData = [
            'APP_NAME' => $data['site_name'],
            'DB_CONNECTION' => $data['db_connection'],
            'DB_DATABASE' => $data['db_database'],
            'INSTALLED' => 'true',
        ];

        if ($data['db_connection'] === 'mysql') {
            $envData['DB_HOST'] = $data['db_host'];
            $envData['DB_USERNAME'] = $data['db_username'];
            $envData['DB_PASSWORD'] = $data['db_password'];
        }

        $this->updateEnv($envData);

        // その他のインストール処理（例：マイグレーション、管理者作成など）
        Artisan::call('migrate', ['--force' => true]);

        return redirect()->route('install.complete');
    }

    // 完了画面
    public function complete()
    {
        return view('install.complete');
    }

    // 環境変数を更新するメソッド
    protected function updateEnv($data)
    {
        // .envファイルの読み込み
        $envPath = base_path('.env');
        $envContent = file_exists($envPath) ? file_get_contents($envPath) : '';

        foreach ($data as $key => $value) {
            // 存在するキーを検索し、更新
            if (preg_match("/^{$key}=.*$/m", $envContent)) {
                $envContent = preg_replace("/^{$key}=.*$/m", "{$key}={$value}\n", $envContent);
            } else {
                // 存在しないキーを追加
                $envContent .= "\n{$key}={$value}";
            }
        }

        // 上書きする前に余分な空行を削除
        $envContent = preg_replace("/\n+/", "\n", $envContent);

        // .envファイルに書き込み
        file_put_contents($envPath, $envContent);

        // キャッシュをクリアして再適用
        Artisan::call('config:clear');
        Artisan::call('config:cache');
    }



    /*


    public function showForm()
    {
        // インストール済みかどうかを確認
        if (env('INSTALLED') === 'true') {
            return redirect('/')->with('message', 'すでにインストールされています。');
        }

        // インストールページを表示し、$errors を渡す
        return view('install')->with('errors', session('errors'));
    }

    public function processForm(Request $request)
    {
        //Log::info('InstallController@processForm');
        // バリデーション
        $request->validate([
            'site_name' => 'required|string|max:255',
            'admin_email' => 'required|email',
            'admin_password' => 'required|string|min:8|confirmed',
            'db_host' => 'required|string',
            'db_database' => 'required|string',
            'db_username' => 'required|string',
            'db_password' => 'nullable|string',
            'mail_host' => 'required|string',
            'mail_port' => 'required|numeric',
            'mail_username' => 'required|string',
            'mail_password' => 'nullable|string',
        ]);

        // .env ファイルにデータベースやメール情報を書き込む
        $this->updateEnv([
            'DB_HOST' => $request->db_host,
            'DB_DATABASE' => $request->db_database,
            'DB_USERNAME' => $request->db_username,
            'DB_PASSWORD' => $request->db_password,
            'MAIL_HOST' => $request->mail_host,
            'MAIL_PORT' => $request->mail_port,
            'MAIL_USERNAME' => $request->mail_username,
            'MAIL_PASSWORD' => $request->mail_password,
            'APP_NAME' => $request->site_name,
            'APP_URL' => url('/'),
        ]);

        // APP_KEYを生成
        try {
            Artisan::call('key:generate', ['--force' => true]);
        } catch (\Exception $e) {
            return redirect('/install')->with('error', 'APP_KEYの生成中にエラーが発生しました: ' . $e->getMessage());
        }

        // マイグレーションを実行
        try {
            Artisan::call('migrate', ['--force' => true]);

            // 初期管理者の作成
            DB::table('admins')->insert([
                'name' => 'Admin',
                'email' => $request->admin_email,
                'password' => bcrypt($request->admin_password),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return redirect('/')->with('success', 'インストールが完了しました！');
        } catch (\Exception $e) {
            return redirect('/install')->with('error', 'インストール中にエラーが発生しました: ' . $e->getMessage());
        }
    }

    */

    /**
     * .env ファイルを更新するメソッド
     */

    /*
    protected function updateEnv($data)
    {
        $envPath = base_path('.env');

        // .envファイルが存在しない場合、.env.example からコピー
        if (!file_exists($envPath)) {
            copy(base_path('.env.example'), $envPath);
        }

        $envContent = file_get_contents($envPath);

        foreach ($data as $key => $value) {
            $escaped = preg_quote("={$value}", '/'); // = をエスケープ
            if (preg_match("/^{$key}=.*$/m", $envContent)) {
                // 既存のキーを更新
                $envContent = preg_replace("/^{$key}=.*$/m", "{$key}={$value}", $envContent);
            } else {
                // 新しいキーを追加
                $envContent .= "\n{$key}={$value}";
            }
        }

        file_put_contents($envPath, $envContent);

        // 環境変数を再読み込み
        Artisan::call('config:clear');
        Artisan::call('config:cache');
    }
        */
}
