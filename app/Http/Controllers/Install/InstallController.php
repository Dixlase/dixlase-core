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
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Exception;
use Illuminate\Support\Facades\Log;
use App\Models\SecuritySetting;
use Illuminate\Support\Facades\Crypt;



class InstallController extends Controller
{
    // 最初の画面
    public function index()
    {
        // ✅ インストール開始時にセッションデータを削除
        session()->forget('install_data');

        // ✅ 設定をクリアし、新しい `.env` を適用
        Artisan::call('config:clear');
        Artisan::call('config:cache');


        $requirements = $this->checkServerRequirements();
        return view('install.index', compact('requirements'));
    }

    // **ステップ 1: 基本設定**
    public function create()
    {
        return view('install.settings')->with('errors', session('errors') ?? new \Illuminate\Support\MessageBag());
    }

    public function storeSettings(Request $request)
    {
        $request->validate([
            'site_name' => 'required|string|max:255',
            'admin_name' => [
                'required',
                'string',
                'alpha_num', // 半角英数字のみ
                'min:3',
                'max:20',
            ],
            'admin_email' => 'required|email',
            'admin_password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/[A-Z]/',  // ✅ 大文字を1文字以上含む
                'regex:/[a-z]/',  // ✅ 小文字を1文字以上含む
                'regex:/[0-9]/',  // ✅ 数字を1文字以上含む
            ],
        ], [
            'admin_name.required' => __('install.validation.admin_name_required'),
            'admin_name.alpha_num' => __('install.validation.admin_name_alpha_num'),
            'admin_name.min' => __('install.validation.admin_name_length'),
            'admin_name.max' => __('install.validation.admin_name_length'),
            'admin_password.regex' => __('install.password_requirements_error'), // エラーメッセージを設定
            'site_name.required' => __('validation.required', ['attribute' => __('validation.attributes.site_name')]),
            'admin_email.required' => __('validation.required', ['attribute' => __('validation.attributes.admin_email')]),
            'admin_email.email' => __('validation.email', ['attribute' => __('validation.attributes.admin_email')]),
            'admin_password.required' => __('validation.required', ['attribute' => __('validation.attributes.admin_password')]),
            'admin_password.min' => __('validation.min.string', ['attribute' => __('validation.attributes.admin_password'), 'min' => 8]),
            'admin_password.regex' => __('install.password_requirements_error'), // 事前に言語ファイルに登録
        ]);

        session([
            'install_data.site_name' => $request->site_name,
            'install_data.admin_name' => $request->admin_name,
            'install_data.admin_email' => $request->admin_email,
            'install_data.admin_password' => Crypt::encryptString($request->admin_password), // ✅ 暗号化
        ]);

        return redirect()->route('install.environment');
    }

    public function environment(Request $request)
    {
        return view('install.environment');
    }

    public function storeEnvironment(Request $request)
    {
        $data = $request->validate([
            'app_env' => 'required|in:local,staging,production',
            'app_debug' => 'nullable|boolean',
            'app_url' => 'required|string',
            'force_ssl' => 'nullable|boolean',
        ]);

        // ✅ `force_ssl` の値を取得（チェックなしなら false）
        $data['force_ssl'] = $request->has('force_ssl') ? true : false;

        // 1) プロトコル除去
        $data['app_url'] = preg_replace('/^(http:\/\/|https:\/\/)/', '', $data['app_url']);

        // 2) ドメイン形式か簡易チェック
        if (!preg_match('/^[\w.\-]+(:\d+)?$/', $data['app_url'])) {
            return back()->withErrors([
                'app_url' => __('validation.url', ['attribute' => __('install.app_url')])
            ])->withInput();
        }

        // 本番環境なら `APP_DEBUG` は `false` 固定
        if ($data['app_env'] === 'production') {
            $data['app_debug'] = false;
        }

        // 設定をセッションに保存（.env への書き出しはインストール完了時）
        session(['install_data' => array_merge(session('install_data', []), $data)]);

        return redirect()->route('install.security');
    }


    // **ステップ 3: システム設定**
    public function security()
    {
        return view('install.security');
    }

    public function storeSecurity(Request $request)
    {
        $validated = $request->validate([
            'admin_url' => 'required|string|max:255',
            'enable_allowed_admin_ips' => 'nullable|boolean',
            'allowed_admin_ips' => 'nullable|string',
            'enable_blocked_admin_ips' => 'nullable|boolean',
            'blocked_admin_ips' => 'nullable|string',
            'enable_allowed_front_ips' => 'nullable|boolean',
            'allowed_front_ips' => 'nullable|string',
            'enable_blocked_front_ips' => 'nullable|boolean',
            'blocked_front_ips' => 'nullable|string',
        ]);

        // ✅ チェックがない場合は false にする
        $validated['enable_allowed_admin_ips'] = $request->has('enable_allowed_admin_ips') ? '1' : '0';
        $validated['enable_blocked_admin_ips'] = $request->has('enable_blocked_admin_ips') ? '1' : '0';
        $validated['enable_allowed_front_ips'] = $request->has('enable_allowed_front_ips') ? '1' : '0';
        $validated['enable_blocked_front_ips'] = $request->has('enable_blocked_front_ips') ? '1' : '0';


        session(['install_data' => array_merge(session('install_data', []), $validated)]);

        return redirect()->route('install.database');
    }


    // **ステップ 4: データベース設定**
    public function database()
    {
        return view('install.database');
    }

    public function storeDatabase(Request $request)
    {
        $request->validate([
            'db_connection' => 'required|string',
            'db_host' => 'required|string',
            'db_port' => 'required|integer',
            'db_database' => 'required|string',
            'db_username' => 'required|string',
            'db_password' => 'nullable|string',
        ]);

        session([
            'install_data.db_connection' => $request->db_connection,
            'install_data.db_host' => $request->db_host,
            'install_data.db_port' => $request->db_port,
            'install_data.db_database' => $request->db_database,
            'install_data.db_username' => $request->db_username,
            'install_data.db_password' => $request->db_password ? Crypt::encryptString($request->db_password) : null, // ✅ 暗号化
        ]);

        return redirect()->route('install.confirm');
    }


    // 入力内容の確認画面
    public function confirm()
    {
        $data = session('install_data');
        return view('install.confirm', compact('data'));
    }

    // 確認画面の処理
    public function confirmStore()
    {
        $data = session('install_data');

        // ✅ 暗号化された管理者パスワードを取得して復号化
        $adminPassword = isset($data['admin_password']) ? Crypt::decryptString($data['admin_password']) : null;

        // ✅ DBパスワードを復号化
        $dbPassword = isset($data['db_password']) ? Crypt::decryptString($data['db_password']) : null;

        // ✅ `force_ssl` の値を取得（チェックなしなら false）
        $forceSslBool = !empty($data['force_ssl']);

        // ✅ `force_ssl` に基づいて `APP_URL` のプロトコルを決定
        $protocol = $forceSslBool ? 'https://' : 'http://';
        $appUrl = $protocol . $data['app_url'];

        // .envファイルの更新やインストール処理
        // 環境変数の更新
        $envData = [
            'APP_NAME' => $data['site_name'],
            'APP_ENV' => $data['app_env'],
            'APP_DEBUG' => $data['app_debug'] ? 'true' : 'false',
            'APP_URL' => $appUrl,
            'INSTALLED' => 'false', // ✅ ここでは false にする
            'FORCE_SSL' => $data['force_ssl'] ? 'true' : 'false',
            'SESSION_DRIVER' => 'database',
            'DB_CONNECTION' => $data['db_connection'],
            'DB_HOST' => $data['db_host'],
            'DB_PORT' => $data['db_port'],
            'DB_DATABASE' => $data['db_database'],
            'DB_USERNAME' => $data['db_username'],
            'DB_PASSWORD' => $dbPassword ?? '', // ✅ 復号化して `.env` に適用
        ];

        // .env ファイル更新
        $this->updateEnv($envData);

        // ✅ 設定をクリアし、新しい `.env` を適用
        Artisan::call('config:clear');
        Artisan::call('config:cache');

        // マイグレーション
        Artisan::call('migrate', ['--force' => true]);

        // シーダーを実行して初期データを挿入
        Artisan::call('db:seed', ['--force' => true]);

        // 初期データの投入
        $this->initializeDatabase($data, $adminPassword);

        // ✅ ストレージのシンボリックリンクを作成
        Artisan::call('storage:link');

        // ✅ アクティブなテーマのシンボリックリンクを作成
        $activeTheme = DB::table('theme_settings')
            ->join('themes', 'theme_settings.active_theme_id', '=', 'themes.id')
            ->select('themes.directory')
            ->first();

        if ($activeTheme) {
            try {
                update_theme_symlink($activeTheme->directory);
            } catch (\Exception $e) {
                Log::error("シンボリックリンクの作成に失敗しました: {$e->getMessage()}");
            }
        } else {
            Log::warning("アクティブなテーマが見つかりません。シンボリックリンクは作成されませんでした。");
        }

        // セッションデータを削除
        session()->forget('install_data');

        return redirect()->route('install.complete');
    }



    // 完了画面
    public function complete()
    {

        // ✅ セッションデータを削除
        session()->forget('install_data');

        // ✅ `.env` を `INSTALLED=true` に更新
        $this->updateEnv(['INSTALLED' => 'true']);

        // ✅ キャッシュをクリア
        Artisan::call('config:clear');
        Artisan::call('config:cache');

        // ✅ `.env` の `APP_URL` を確実に取得する
        config()->set('app.url', env('APP_URL', 'http://localhost'));

        // ✅ アプリケーションURLの取得
        $appUrl = rtrim(config('app.url'), '/');
        // ✅ 管理画面URLを取得（デフォルト値は 'admin'）
        $adminSlug = getAdminUrl();
        $adminUrl = rtrim($appUrl . '/' . $adminSlug, '/');
        $adminLoginUrl = $adminUrl . '/login';





        return view('install.complete', compact('appUrl', 'adminUrl', 'adminLoginUrl'));
    }

    /**
     * `.env` ファイルを更新する
     */
    private function updateEnv(array $values)
    {
        $envPath = base_path('.env');

        // `.env` がない場合は `.env.example` からコピー
        if (!File::exists($envPath)) {
            File::copy(base_path('.env.example'), $envPath);
        }

        $env = File::get($envPath);


        foreach ($values as $key => $value) {
            if (preg_match("/^{$key}=/m", $env)) {
                // 既存の値を更新
                $env = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $env);
            } else {
                // `.env` に存在しない場合は末尾に追加
                $env .= "\n{$key}={$value}";
            }
        }

        File::put($envPath, $env);
    }

    /**
     * 初期データをデータベースに追加する
     */
    private function initializeDatabase(array $data, string $adminPassword)
    {
        // `base_settings` にサイト名を追加
        DB::table('base_settings')->insert([
            'name' => 'site_name',
            'value' => $data['site_name'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // `security_settings` に管理画面URLとSSL設定を追加
        DB::table('security_settings')->insert([
            ['name' => 'admin_url', 'value' => $data['admin_url'], 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'enable_allowed_admin_ips', 'value' => $data['enable_allowed_admin_ips'], 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'allowed_admin_ips', 'value' => $data['enable_allowed_admin_ips'] ? $data['allowed_admin_ips'] : '', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'enable_blocked_admin_ips', 'value' => $data['enable_blocked_admin_ips'], 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'blocked_admin_ips', 'value' => $data['enable_blocked_admin_ips'] ? $data['blocked_admin_ips'] : '', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'enable_allowed_front_ips', 'value' => $data['enable_allowed_front_ips'], 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'allowed_front_ips', 'value' => $data['enable_allowed_front_ips'] ? $data['allowed_front_ips'] : '', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'enable_blocked_front_ips', 'value' => $data['enable_blocked_front_ips'], 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'blocked_front_ips', 'value' => $data['enable_blocked_front_ips'] ? $data['blocked_front_ips'] : '', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // `members` に管理者を追加
        DB::table('members')->insert([
            'name' => $data['admin_name'],
            'email' => $data['admin_email'],
            'role' => 'super_manager',
            'password' => Hash::make($adminPassword),
            'status' => 1, // active
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * サーバーが Laravel 12 の要件を満たしているか確認
     */
    protected function checkServerRequirements()
    {
        return [
            'php' => version_compare(PHP_VERSION, '8.2.0', '>='),
            'extensions' => [
                'BCMath' => extension_loaded('bcmath'),
                'Ctype' => extension_loaded('ctype'),
                'cURL' => extension_loaded('curl'),
                'DOM' => extension_loaded('dom'),
                'Fileinfo' => extension_loaded('fileinfo'),
                'JSON' => extension_loaded('json'),
                'Mbstring' => extension_loaded('mbstring'),
                'OpenSSL' => extension_loaded('openssl'),
                'PCRE' => extension_loaded('pcre'),
                'PDO' => extension_loaded('pdo'),
                'Tokenizer' => extension_loaded('tokenizer'),
                'XML' => extension_loaded('xml'),
            ],
            'permissions' => [
                'storage' => is_writable(storage_path()),
                'bootstrap/cache' => is_writable(base_path('bootstrap/cache')),
            ],
        ];
    }


    /**
     * データベース接続をテストするメソッド
     */
    public function testDatabaseConnection(Request $request)
    {
        $dbConnection = $request->db_connection;
        $dbHost = $request->db_host;
        $dbPort = $request->db_port;
        $dbDatabase = $request->db_database;
        $dbUsername = $request->db_username;
        $dbPassword = $request->db_password;

        try {
            config([
                'database.connections.test_connection' => [
                    'driver' => $dbConnection,
                    'host' => $dbHost,
                    'port' => $dbPort,
                    'database' => $dbDatabase,
                    'username' => $dbUsername,
                    'password' => $dbPassword,
                    'charset' => 'utf8mb4',
                    'collation' => 'utf8mb4_unicode_ci',
                ]
            ]);

            DB::connection('test_connection')->getPdo();

            return response()->json([
                'success' => true,
                'message' => __('install.db_connection_success')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('install.db_connection_error', ['error' => $e->getMessage()])
            ]);
        }
    }
}
