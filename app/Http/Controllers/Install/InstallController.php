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
    // 利用可能な言語のリスト
    protected $availableLocales;

    public function __construct()
    {
        $this->availableLocales = array_keys(config('language.languages', []));
    }

    /**
     * 言語切り替えと.envの更新
     */
    public function setLanguage($locale)
    {
        // 有効なロケードのみを許可
        if (in_array($locale, $this->availableLocales)) {
            // セッションに保存（複数のキーで保存して確実に保持）
            session([
                'install_locale' => $locale,
                'app.locale' => $locale,
                'locale' => $locale
            ]);
            
            // 現在のリクエストのロケールも即時変更
            app()->setLocale($locale);
            
            // .envファイルを同期的に更新
            $envPath = base_path('.env');
            if (file_exists($envPath) && is_writable($envPath)) {
                $envContent = file_get_contents($envPath);
                $updates = [
                    'APP_LOCALE' => $locale,
                    'APP_FALLBACK_LOCALE' => $locale,
                    'APP_FAKER_LOCALE' => $locale . '_' . strtoupper($locale)
                ];
                
                $updated = false;
                
                foreach ($updates as $key => $value) {
                    if (str_contains($envContent, $key . '=')) {
                        $newContent = preg_replace(
                            '/^' . $key . '=.*/m',
                            $key . '=' . $value,
                            $envContent,
                            -1,
                            $count
                        );
                        
                        if ($count > 0) {
                            $envContent = $newContent;
                            $updated = true;
                        }
                    } else {
                        $envContent .= "\n" . $key . '=' . $value;
                        $updated = true;
                    }
                }
                
                if ($updated) {
                    file_put_contents($envPath, $envContent);
                }
            }
            
            // レスポンス用の設定
            $response = [
                'success' => true,
                'locale' => $locale,
                'message' => __('install.language_changed')
            ];
            
            // 常にJSONで返す（リダイレクトなし）＋ クッキーで永続化
            return response()->json($response)
                ->cookie('install_locale', $locale, 60 * 24 * 30);
        } else {
            return response()->json([
                'success' => false,
                'message' => '無効な言語が選択されました。'
            ], 400);
        }
        
        // 元のページにリダイレクト
        return redirect()->back();
    }

    // 最初の画面
    public function index()
    {
        // ✅ インストール開始時にセッションデータを削除（言語設定は保持）
        $installData = session('install_data', []);
        session()->forget('install_data');
        session(['install_data' => $installData]);

        // 言語設定をセッション/クッキーから取得、デフォルトはブラウザの言語設定を考慮
        
        $browserLocale = substr(request()->server('HTTP_ACCEPT_LANGUAGE', 'en'), 0, 2);
        $cookieLocale = request()->cookie('install_locale');
        $sessionLocale = session('install_locale');
        $candidate = $sessionLocale ?: $cookieLocale;
        $locale = $candidate && in_array($candidate, $this->availableLocales)
            ? $candidate
            : (in_array($browserLocale, $this->availableLocales) ? $browserLocale : 'en');
        app()->setLocale($locale);
        
        // ✅ 設定をクリアし、新しい `.env` を適用
        //Artisan::call('config:clear');
        //Artisan::call('config:cache');

        $requirements = $this->checkServerRequirements();
        
        return view('install.index', [
            'requirements' => $requirements,
            'currentLocale' => $locale,
            'availableLocales' => $this->availableLocales
        ]);
    }

    /**
     * 現在のロケールを取得
     */
    protected function getCurrentLocale()
    {
        $browserLocale = substr(request()->server('HTTP_ACCEPT_LANGUAGE', 'en'), 0, 2);
        $cookieLocale = request()->cookie('install_locale');
        $sessionLocale = session('install_locale');
        $candidate = $sessionLocale ?: $cookieLocale;
        return $candidate && in_array($candidate, $this->availableLocales)
            ? $candidate
            : (in_array($browserLocale, $this->availableLocales) ? $browserLocale : 'en');
    }

    // **ステップ 1: 基本設定**
    public function create()
    {
        $locale = $this->getCurrentLocale();
        app()->setLocale($locale);
        
        return view('install.settings', [
            'errors' => session('errors') ?? new \Illuminate\Support\MessageBag(),
            'currentLocale' => $locale,
            'availableLocales' => $this->availableLocales
        ]);
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
        $locale = $this->getCurrentLocale();
        app()->setLocale($locale);
        
        return view('install.environment', [
            'currentLocale' => $locale,
            'availableLocales' => $this->availableLocales
        ]);
    }

    public function storeEnvironment(Request $request)
    {
        $data = $request->validate([
            'app_env' => 'required|in:local,staging,production',
            'app_debug' => 'nullable|boolean',
            'app_url' => 'required|string',
            'app_timezone' => 'required|timezone',
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
        $locale = $this->getCurrentLocale();
        app()->setLocale($locale);
        
        return view('install.security', [
            'currentLocale' => $locale,
            'availableLocales' => $this->availableLocales
        ]);
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
        $locale = $this->getCurrentLocale();
        app()->setLocale($locale);
        
        return view('install.database', [
            'currentLocale' => $locale,
            'availableLocales' => $this->availableLocales
        ]);
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
            'preserve_data' => 'nullable|boolean',
        ]);

        session([
            'install_data.db_connection' => $request->db_connection,
            'install_data.db_host' => $request->db_host,
            'install_data.db_port' => $request->db_port,
            'install_data.db_database' => $request->db_database,
            'install_data.db_username' => $request->db_username,
            'install_data.db_password' => $request->db_password ? Crypt::encryptString($request->db_password) : null, // ✅ 暗号化
            'install_data.preserve_data' => $request->has('preserve_data'),
        ]);

        return redirect()->route('install.confirm');
    }


    // 入力内容の確認画面
    public function confirm()
    {
        $locale = $this->getCurrentLocale();
        app()->setLocale($locale);
        
        $data = session('install_data', []);
        
        // 必須フィールドのチェックと不足フィールドに基づく適切なステップへのリダイレクト
        $steps = [
            // 基本設定
            'settings' => ['site_name', 'admin_name', 'admin_email', 'admin_password'],
            // 環境設定
            'environment' => ['app_env', 'app_url', 'app_timezone'],
            // セキュリティ設定
            'security' => ['admin_url'],
            // データベース設定
            'database' => ['db_connection', 'db_host', 'db_port', 'db_database', 'db_username']
        ];
        
        // 各ステップの必須フィールドをチェック
        foreach ($steps as $step => $fields) {
            foreach ($fields as $field) {
                if (empty($data[$field])) {
                    // 不足しているフィールドがあるステップにリダイレクト
                    $route = 'install.' . ($step === 'settings' ? 'index' : $step);
                    return redirect()->route($route)
                        ->with('error', __('install.missing_required_fields'));
                }
            }
        }
        
        return view('install.confirm', [
            'data' => $data,
            'currentLocale' => $locale,
            'availableLocales' => $this->availableLocales
        ]);
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
        // セッションから言語設定を取得、デフォルトは 'en'
        $locale = session('install_locale', 'en');
        
        $envData = [
            'APP_NAME' => $data['site_name'],
            'APP_ENV' => $data['app_env'],
            'APP_DEBUG' => $data['app_debug'] ? 'true' : 'false',
            'APP_URL' => $appUrl,
            'APP_LOCALE' => $locale,
            'FALLBACK_LOCALE' => 'en',
            'APP_TIMEZONE' => $data['app_timezone'] ?? 'Asia/Tokyo',
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

        // データベースをリセットするかどうかを確認
        if (empty($data['preserve_data'])) {
            // データベースをリセットしてマイグレーションを実行
            Artisan::call('migrate:fresh', ['--force' => true]);
        } else {
            // データベースをリセットせずにマイグレーションのみを実行
            Artisan::call('migrate', ['--force' => true]);
        }
        
        // 常にメンバーロールパーミッションシーダーを実行
        // 既存のデータを保持するため、テーブルが空の場合のみ実行
        if (!DB::table('member_role_permissions')->exists()) {
            Artisan::call('db:seed', [
                '--class' => 'DatabaseSeeder',
                '--force' => true
            ]);
        }

        // 初期データの投入
        $this->initializeDatabase($data, $adminPassword);

        // ストレージのシンボリックリンクを作成
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



        // ✅ `.env` の `APP_URL` を確実に取得する
        config()->set('app.url', env('APP_URL', 'http://localhost'));

        // ✅ アプリケーションURLの取得
        $appUrl = rtrim(config('app.url'), '/');
        // ✅ 管理画面URLを取得（セッションから取得、デフォルトは 'admin'）
        $adminSlug = session('install_data.admin_url', 'admin');
        $adminUrl = rtrim($appUrl . '/' . $adminSlug, '/');
        $adminLoginUrl = $adminUrl . '/login';

        // ✅ キャッシュをクリア
        Artisan::call('config:clear');
        Artisan::call('config:cache');

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
        Log::info('initializeDatabase - 開始: admin_email=' . $data['admin_email'] . ', admin_name=' . $data['admin_name']);
        
        // `base_settings` にサイト名を追加 (存在しない場合のみ)
        Log::info('initializeDatabase - base_settings更新開始');
        if (!DB::connection('mysql')->table('base_settings')->where('name', 'site_name')->exists()) {
            DB::connection('mysql')->table('base_settings')->insert([
                'name' => 'site_name',
                'value' => $data['site_name'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            Log::info('initializeDatabase - site_name新規作成: ' . $data['site_name']);
        } else {
            // 既存のサイト名を更新
            DB::connection('mysql')->table('base_settings')
                ->where('name', 'site_name')
                ->update(['value' => $data['site_name'], 'updated_at' => now()]);
            Log::info('initializeDatabase - site_name更新: ' . $data['site_name']);
        }

        // `security_settings` の各設定を更新または作成
        $securitySettings = [
            'admin_url' => $data['admin_url'],
            'enable_allowed_admin_ips' => $data['enable_allowed_admin_ips'] ?? 0,
            'allowed_admin_ips' => ($data['enable_allowed_admin_ips'] ?? 0) ? ($data['allowed_admin_ips'] ?? '') : '',
            'enable_blocked_admin_ips' => $data['enable_blocked_admin_ips'] ?? 0,
            'blocked_admin_ips' => ($data['enable_blocked_admin_ips'] ?? 0) ? ($data['blocked_admin_ips'] ?? '') : '',
            'enable_allowed_front_ips' => $data['enable_allowed_front_ips'] ?? 0,
            'allowed_front_ips' => ($data['enable_allowed_front_ips'] ?? 0) ? ($data['allowed_front_ips'] ?? '') : '',
            'enable_blocked_front_ips' => $data['enable_blocked_front_ips'] ?? 0,
            'blocked_front_ips' => ($data['enable_blocked_front_ips'] ?? 0) ? ($data['blocked_front_ips'] ?? '') : '',
        ];

        foreach ($securitySettings as $name => $value) {
            if (DB::table('security_settings')->where('name', $name)->exists()) {
                DB::table('security_settings')
                    ->where('name', $name)
                    ->update(['value' => $value, 'updated_at' => now()]);
            } else {
                DB::table('security_settings')->insert([
                    'name' => $name,
                    'value' => $value,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // `members` テーブルに管理者が存在するか確認
        Log::info('initializeDatabase - 管理者アカウント処理開始');
        $admin = DB::connection('mysql')->table('members')->where('email', $data['admin_email'])->first();
        
        if ($admin) {
            // 既存の管理者を更新 - 正しいロール値を使用
            Log::info('initializeDatabase - 既存管理者更新: ID=' . $admin->id);
            DB::connection('mysql')->table('members')
                ->where('id', $admin->id)
                ->update([
                    'name' => $data['admin_name'],
                    'password' => Hash::make($adminPassword),
                    'role' => 10, // super_admin
                    'status' => 1, // active
                    'updated_at' => now(),
                ]);
            Log::info('initializeDatabase - 既存管理者更新完了');
        } else {
            // 新しい管理者を作成
            Log::info('initializeDatabase - 新規管理者作成開始');
            $memberId = DB::connection('mysql')->table('members')->insertGetId([
                'name' => $data['admin_name'],
                'email' => $data['admin_email'],
                'role' => 10, // super_admin
                'password' => Hash::make($adminPassword),
                'status' => 1, // active
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            Log::info('initializeDatabase - 新規管理者作成完了: ID=' . $memberId);
        }
        
        // 作成後の確認
        $memberCount = DB::connection('mysql')->table('members')->count();
        Log::info('initializeDatabase - membersテーブル総レコード数: ' . $memberCount);
        Log::info('initializeDatabase - 完了');
    }

    /**
     * サーバーが Laravel 12 の要件を満たしているか確認
     * 
     * @return array
     */
    protected function checkServerRequirements()
    {
        // 必須の拡張機能
        $requiredExtensions = [
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
        ];

        // オプションの拡張機能
        $optionalExtensions = [
            'BCMath' => extension_loaded('bcmath'),
        ];

        $allExtensions = array_merge($requiredExtensions, $optionalExtensions);

        return [
            'php' => version_compare(PHP_VERSION, '8.2.0', '>='),
            'extensions' => $allExtensions,
            'required_extensions' => $requiredExtensions,
            'optional_extensions' => $optionalExtensions,
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
