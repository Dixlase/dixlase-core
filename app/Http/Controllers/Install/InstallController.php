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
use App\Traits\MailTestTrait;
use App\Http\Requests\MailServerRequest;

/**
 * インストールコントローラー
 */
class InstallController extends Controller
{
    use MailTestTrait;
    
    // 利用可能な言語のリスト
    protected $availableLocales;
    private $total_steps = 5;

    public function __construct()
    {
        $this->availableLocales = array_keys(config('language.languages', []));
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
            'availableLocales' => $this->availableLocales,
            'current_step' => 1,
            'total_steps' => $this->total_steps
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
            'availableLocales' => $this->availableLocales,
            'current_step' => 2,
            'total_steps' => $this->total_steps
        ]);
    }

    public function storeEnvironment(Request $request)
    {
        $data = $request->validate([
            'app_env' => 'required|in:local,staging,production',
            'app_debug' => 'nullable|boolean',
            'app_url' => 'required|string',
            'admin_url' => 'required|string|max:255',
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

        return redirect()->route('install.database');
    }


    



    // **ステップ 3: データベース設定**
    public function database()
    {
        $locale = $this->getCurrentLocale();
        app()->setLocale($locale);
        
        return view('install.database', [
            'currentLocale' => $locale,
            'availableLocales' => $this->availableLocales,
            'current_step' => 3,
            'total_steps' => $this->total_steps
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

        $data = $request->only([
            'db_connection',
            'db_host',
            'db_port',
            'db_database',
            'db_username',
            'db_password',
        ]);

        if ($request->filled('db_password')) {
            $data['db_password'] = Crypt::encryptString($request->db_password);
        }

        $data['preserve_data'] = $request->has('preserve_data');

        session(['install_data' => array_merge(session('install_data', []), $data)]);

        return redirect()->route('install.mail');
    }


    // **ステップ 4: メールサーバー設定**
    public function mail()
    {
        $locale = $this->getCurrentLocale();
        app()->setLocale($locale);

        $installData = session('install_data', []);
        $adminEmail = $installData['admin_email'] ?? '';

        // メールテスト結果をセッションから取得
        $testStatus = [
            'connection_tested' => (bool) ($installData['mail_connection_tested'] ?? false),
            'connection_test_date' => $installData['mail_connection_test_date'] ?? null,
            'send_tested' => (bool) ($installData['mail_send_tested'] ?? false),
            'send_test_date' => $installData['mail_send_test_date'] ?? null,
            'receive_tested' => (bool) ($installData['mail_receive_tested'] ?? false),
            'receive_test_date' => $installData['mail_receive_test_date'] ?? null,
        ];

        // デバッグ用ログ
        \Log::info('メールページ表示時のセッションデータ', [
            'install_data_keys' => array_keys($installData),
            'test_status' => $testStatus,
            'session_id' => session()->getId()
        ]);

        return view('install.mail', [
            'currentLocale' => $locale,
            'availableLocales' => $this->availableLocales,
            'current_step' => 4,
            'total_steps' => $this->total_steps,
            'admin_email' => $adminEmail,
            'testStatus' => $testStatus
        ]);
    }

    public function storeMail(Request $request)
    {
        $validated = $request->validate([
            'mail_mailer' => 'nullable|string',
            'mail_host' => 'nullable|string',
            'mail_port' => 'nullable|integer',
            'mail_username' => 'nullable|string',
            'mail_password' => 'nullable|string',
            'mail_encryption' => 'nullable|string',
            'mail_from_address' => 'nullable|email',
        ]);

        if ($request->filled('mail_password')) {
            $validated['mail_password'] = Crypt::encryptString($request->mail_password);
        }

        session(['install_data' => array_merge(session('install_data', []), $validated)]);

        return redirect()->route('install.security');
    }


    // **ステップ 4: セキュリティ設定**
    public function security()
    {
        $locale = $this->getCurrentLocale();
        app()->setLocale($locale);
        
        return view('install.security', [
            'currentLocale' => $locale,
            'availableLocales' => $this->availableLocales,
            'current_step' => 5,
            'total_steps' => $this->total_steps
        ]);
    }

    public function storeSecurity(Request $request)
    {
        $validated = $request->validate([
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

        return redirect()->route('install.confirm');
    }

    /**
     * メールサーバー接続テスト（インストール時）
     */
    public function testMailConnection(MailServerRequest $request)
    {
        $locale = $this->getCurrentLocale();
        app()->setLocale($locale);
        return $this->performConnectionTest($request, 'install');
    }

    /**
     * メール送信テスト（インストール時）
     */
    public function testMailSend(MailServerRequest $request)
    {
        $locale = $this->getCurrentLocale();
        app()->setLocale($locale);
        return $this->performMailTest($request, 'install');
    }

    /**
     * メール受信確認（インストール時）
     */
    public function verifyMail($token)
    {
        $locale = $this->getCurrentLocale();
        app()->setLocale($locale);
        return $this->performMailVerification($token, 'install');
    }

    /**
     * メールテスト結果をリセット（インストール時）
     */
    public function resetMailTests()
    {
        $installData = session('install_data', []);
        
        // メールテスト関連のセッションデータをクリア
        unset($installData['mail_connection_tested']);
        unset($installData['mail_connection_test_date']);
        unset($installData['mail_send_tested']);
        unset($installData['mail_send_test_date']);
        unset($installData['mail_receive_tested']);
        unset($installData['mail_receive_test_date']);
        
        // セッションを強制的に保存
        session(['install_data' => $installData]);
        session()->save();
        
        \Log::info('メールテスト結果リセット完了', [
            'reset_data' => array_keys($installData),
            'session_id' => session()->getId()
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'メールテスト結果をリセットしました',
            'debug' => [
                'session_keys' => array_keys($installData),
                'has_connection_tested' => isset($installData['mail_connection_tested']),
                'has_send_tested' => isset($installData['mail_send_tested']),
                'has_receive_tested' => isset($installData['mail_receive_tested'])
            ]
        ]);
    }

    // 入力内容の確認画面
    public function confirm()
    {
        $locale = $this->getCurrentLocale();
        app()->setLocale($locale);
        
        $data = session('install_data', []);
        
        // デバッグ情報をログに出力
        Log::info('確認画面表示時のセッションデータ', [
            'session_id' => session()->getId(),
            'session_keys' => array_keys($data),
            'has_site_name' => isset($data['site_name']),
            'has_admin_name' => isset($data['admin_name']),
            'has_admin_email' => isset($data['admin_email']),
            'has_admin_password' => isset($data['admin_password']),
            'has_app_env' => isset($data['app_env']),
            'has_app_url' => isset($data['app_url']),
            'has_admin_url' => isset($data['admin_url']),
            'has_app_timezone' => isset($data['app_timezone']),
            'has_db_connection' => isset($data['db_connection']),
            'has_db_host' => isset($data['db_host']),
            'has_db_port' => isset($data['db_port']),
            'has_db_database' => isset($data['db_database']),
            'has_db_username' => isset($data['db_username']),
            'full_data_keys' => $data ? array_keys($data) : 'empty'
        ]);
        
        // 必須フィールドのチェックと不足フィールドに基づく適切なステップへのリダイレクト
        $steps = [
            // 基本設定
            'settings' => ['site_name', 'admin_name', 'admin_email', 'admin_password'],
            // 環境設定
            'environment' => ['app_env', 'app_url','admin_url', 'app_timezone'],
            // データベース設定
            'database' => ['db_connection', 'db_host', 'db_port', 'db_database', 'db_username']
        ];
        
        // セッションデータが完全に空の場合は最初からやり直し
        if (empty($data)) {
            Log::error('セッションデータが完全に空です - インストールを最初からやり直してください');
            return redirect()->route('install.index')
                ->with('error', 'セッションデータが失われました。インストールを最初からやり直してください。');
        }

        // 各ステップの必須フィールドをチェック
        $missingFields = [];
        foreach ($steps as $step => $fields) {
            foreach ($fields as $field) {
                if (empty($data[$field])) {
                    $missingFields[] = ['step' => $step, 'field' => $field];
                }
            }
        }

        // 不足フィールドがある場合
        if (!empty($missingFields)) {
            $firstMissing = $missingFields[0];
            Log::error('必須フィールドが不足しています', [
                'missing_fields' => $missingFields,
                'session_keys' => array_keys($data),
                'session_id' => session()->getId(),
                'redirecting_to_step' => $firstMissing['step']
            ]);
            
            $route = 'install.' . ($firstMissing['step'] === 'settings' ? 'index' : $firstMissing['step']);
            return redirect()->route($route)
                ->with('error', __('install.missing_required_fields') . " (不足フィールド: {$firstMissing['field']})");
        }
        
        // メールテスト結果をセッションから取得
        $mailTestStatus = [
            'connection_tested' => (bool) ($data['mail_connection_tested'] ?? false),
            'connection_test_date' => $data['mail_connection_test_date'] ?? null,
            'send_tested' => (bool) ($data['mail_send_tested'] ?? false),
            'send_test_date' => $data['mail_send_test_date'] ?? null,
            'receive_tested' => (bool) ($data['mail_receive_tested'] ?? false),
            'receive_test_date' => $data['mail_receive_test_date'] ?? null,
        ];

        return view('install.confirm', [
            'data' => $data,
            'mailTestStatus' => $mailTestStatus,
            'currentLocale' => $locale,
            'availableLocales' => $this->availableLocales
        ]);
    }

    // 確認画面の処理
    public function confirmStore()
    {
        try {
            Log::info('=== インストール開始 ===');
            
            $data = session('install_data');
            Log::info('セッションデータ取得完了', [
                'keys' => array_keys($data ?? []),
                'session_id' => session()->getId(),
                'full_data' => $data
            ]);

            // セッションデータが空の場合は確認画面にリダイレクト
            if (empty($data)) {
                Log::error('セッションデータが空です - 確認画面にリダイレクト');
                return redirect()->route('install.confirm')
                    ->with('error', 'セッションデータが失われました。再度お試しください。');
            }

            // ✅ 暗号化された管理者パスワードを取得して復号化
            $adminPassword = isset($data['admin_password']) ? Crypt::decryptString($data['admin_password']) : null;
            Log::info('管理者パスワード復号化完了');

            // ✅ DBパスワードを復号化
            $dbPassword = isset($data['db_password']) ? Crypt::decryptString($data['db_password']) : null;
            Log::info('DBパスワード復号化完了');

            // ✅ メールパスワードを復号化
            $mailPassword = isset($data['mail_password']) ? Crypt::decryptString($data['mail_password']) : null;
            Log::info('メールパスワード復号化完了');

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
                'APP_LOCALE' => $data['app_locale'] ?? 'ja',
                'APP_TIMEZONE' => $data['app_timezone'] ?? 'Asia/Tokyo',
                'INSTALLED' => 'false', // ✅ ここでは false にする
                'FORCE_SSL' => $data['force_ssl'] ? 'true' : 'false',
                'MAINTENANCE_MODE' => 'false',
                
                // Session settings
                'SESSION_DRIVER' => 'database',
                'SESSION_LIFETIME' => '120',
                'SESSION_ENCRYPT' => 'false',

                // メール設定
                'MAIL_MAILER' => $data['mail_mailer'] ?? 'smtp',
                'MAIL_HOST' => $data['mail_host'] ?? 'localhost',
                'MAIL_PORT' => $data['mail_port'] ?? 1025,
                'MAIL_USERNAME' => $data['mail_username'] ?? 'null',
                'MAIL_PASSWORD' => $mailPassword ?? 'null',
                'MAIL_ENCRYPTION' => $data['mail_encryption'] ?? 'null',
                'MAIL_FROM_ADDRESS' => $data['mail_from_address'] ?? $data['admin_email'],
                'MAIL_FROM_NAME' => "\"{$data['site_name']}\"",

                // DB設定
                'DB_CONNECTION' => $data['db_connection'],
                'DB_HOST' => $data['db_host'],
                'DB_PORT' => $data['db_port'],
                'DB_DATABASE' => $data['db_database'],
                'DB_USERNAME' => $data['db_username'],
                'DB_PASSWORD' => $dbPassword ?? '', // ✅ 復号化して `.env` に適用
            ];


            // .env ファイル更新
            Log::info('.envファイル更新開始');
            $this->updateEnv($envData);
            Log::info('.envファイル更新完了');

            // ✅ 設定をクリアし、新しい `.env` を適用
            Log::info('設定キャッシュクリア開始');
            Artisan::call('config:clear');
            Log::info('設定キャッシュクリア完了');
            
            Log::info('設定キャッシュ再構築開始');
            Artisan::call('config:cache');
            Log::info('設定キャッシュ再構築完了');

            // データベースをリセットするかどうかを確認
            if (empty($data['preserve_data'])) {
                // データベースをリセットしてマイグレーションを実行
                Log::info('データベースリセット＆マイグレーション開始');
                Artisan::call('migrate:fresh', ['--force' => true]);
                Log::info('データベースリセット＆マイグレーション完了');
            } else {
                // データベースをリセットせずにマイグレーションのみを実行
                Log::info('マイグレーション開始（データ保持）');
                Artisan::call('migrate', ['--force' => true]);
                Log::info('マイグレーション完了（データ保持）');
            }
            
            // 常にメンバーロールパーミッションシーダーを実行
            // 既存のデータを保持するため、テーブルが空の場合のみ実行
            Log::info('シーダー実行チェック開始');
            if (!DB::table('members_role_permissions')->exists()) {
                Log::info('DatabaseSeeder実行開始');
                Artisan::call('db:seed', [
                    '--class' => 'DatabaseSeeder',
                    '--force' => true
                ]);
                Log::info('DatabaseSeeder実行完了');
            } else {
                Log::info('members_role_permissionsテーブルが既に存在するため、シーダーをスキップ');
            }

            // 初期データの投入
            Log::info('初期データ投入開始');
            $this->initializeDatabase($data, $adminPassword);
            Log::info('初期データ投入完了');

            // ストレージのシンボリックリンクを作成
            // ✅ ストレージのシンボリックリンクを作成
            Log::info('ストレージシンボリックリンク作成開始');
            Artisan::call('storage:link');
            Log::info('ストレージシンボリックリンク作成完了');

            // ✅ アクティブなテーマのシンボリックリンクを作成
            Log::info('テーマシンボリックリンク作成開始');
            $activeTheme = DB::table('theme_settings')
                ->join('themes', 'theme_settings.active_theme_id', '=', 'themes.id')
                ->select('themes.directory')
                ->first();

            if ($activeTheme) {
                try {
                    update_theme_symlink($activeTheme->directory);
                    Log::info('テーマシンボリックリンク作成完了', ['theme' => $activeTheme->directory]);
                } catch (\Exception $e) {
                    Log::error("シンボリックリンクの作成に失敗しました: {$e->getMessage()}");
                }
            } else {
                Log::warning("アクティブなテーマが見つかりません。シンボリックリンクは作成されませんでした。");
            }

            // セッションデータを削除
            session()->forget('install_data');
            Log::info('=== インストール完了 ===');
            Log::info('install.completeルートにリダイレクト中...');

            return redirect()->route('install.complete');
            
        } catch (\Exception $e) {
            Log::error('=== インストールエラー ===', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'session_data_exists' => !empty($data),
                'session_keys' => array_keys($data ?? [])
            ]);
            
            // セッションデータが失われていないことを確認
            if (empty(session('install_data'))) {
                Log::warning('セッションデータが失われています - 復元を試行');
                if (!empty($data)) {
                    session(['install_data' => $data]);
                    Log::info('セッションデータを復元しました');
                }
            }
            
            // ユーザーフレンドリーなエラーメッセージを作成
            $errorMessage = $this->getInstallationErrorMessage($e);
            
            return redirect()->route('install.confirm')
                ->with('error', $errorMessage)
                ->with('error_details', $e->getMessage());
        }
    }

    /**
     * インストールエラーのユーザーフレンドリーなエラーメッセージを取得
     */
    private function getInstallationErrorMessage(\Exception $e)
    {
        // エラーメッセージを取得
        $errorMessage = $e->getMessage();
        
        // エラーがデータベース関連の場合
        if (strpos($errorMessage, 'database') !== false) {
            return __('install.error.database');
        }
        
        // エラーがファイルシステム関連の場合
        if (strpos($errorMessage, 'file') !== false || strpos($errorMessage, 'directory') !== false) {
            return __('install.error.file_system');
        }
        
        // エラーが環境変数関連の場合
        if (strpos($errorMessage, 'env') !== false || strpos($errorMessage, 'environment') !== false) {
            return __('install.error.environment');
        }
        
        // エラーが不明な場合
        return __('install.error.unknown');
    }

    /**
     * 完了画面
     */
    public function complete()
    {
        Log::info('=== InstallController::complete() 開始 ===');
        
        // 現在の環境変数とセッション状態をログ出力
        $installed = env('INSTALLED');
        $installCompleted = session('install_completed', false);
        
        Log::info('INSTALLED環境変数の値: ' . var_export($installed, true));
        Log::info('install_completedセッション値: ' . var_export($installCompleted, true));
        
        // インストール状態の判定
        $isInstalled = (env('INSTALLED') === 'true' || env('INSTALLED') === true);
        
        if ($isInstalled && $installCompleted) {
            Log::info('2回目以降のアクセス: フロントページにリダイレクト');
            // 2回目以降のアクセスはフロントページにリダイレクト
            return redirect('/')->with('message', 'インストールは既に完了しています。');
        } elseif ($isInstalled) {
            Log::info('初回完了画面表示: セッションフラグを設定');
            // 初回表示時のみセッションにフラグを設定
            session(['install_completed' => true]);
        } else {
            Log::info('インストール未完了: 初回インストール完了処理');
        }

        // ✅ セッションデータから管理画面URLを先に取得
        $adminSlug = session('install_data.admin_url', 'admin');
        
        // ✅ `.env` の `APP_URL` を確実に取得する
        $envAppUrl = env('APP_URL');
        if (!$envAppUrl) {
            // フォールバック: リクエストから現在のURLを構築
            $scheme = request()->isSecure() ? 'https' : 'http';
            $host = request()->getHost();
            $port = request()->getPort();
            
            if (($scheme === 'http' && $port != 80) || ($scheme === 'https' && $port != 443)) {
                $envAppUrl = $scheme . '://' . $host . ':' . $port;
            } else {
                $envAppUrl = $scheme . '://' . $host;
            }
        }
        
        config()->set('app.url', $envAppUrl);

        // ✅ アプリケーションURLの取得
        $appUrl = rtrim(config('app.url'), '/');
        // ✅ 管理画面URLを取得
        $adminUrl = rtrim($appUrl . '/' . $adminSlug, '/');
        $adminLoginUrl = $adminUrl . '/login';

        // ✅ セッションデータを削除（install_completedは保持）
        session()->forget('install_data');

        // 完了フラグを設定（初回表示時）
        if (!$installCompleted) {
            session(['install_completed' => true]);
        }

        Log::info('完了画面を表示: appUrl=' . $appUrl . ', adminLoginUrl=' . $adminLoginUrl);
        Log::info('APP_URL取得結果: ' . $envAppUrl);
        Log::info('=== InstallController::complete() 終了 ===');
        
        // 完了画面では INSTALLED=true を設定せず、表示のみ行う
        // INSTALLED=true の設定は finalize メソッドで行う
        
        return view('install.complete', compact('appUrl', 'adminUrl', 'adminLoginUrl'));
    }

    /**
     * インストール最終化（INSTALLED=trueを設定）
     */
    public function finalize(Request $request)
    {
        Log::info('=== InstallController::finalize() 開始 ===');
        
        // INSTALLED=trueを設定
        Log::info('INSTALLED=trueを設定中...');
        $this->updateEnv(['INSTALLED' => 'true']);
        Artisan::call('config:cache');
        Artisan::call('config:clear');
        Log::info('INSTALLED=true設定完了');
        
        Log::info('=== InstallController::finalize() 終了 ===');
        
        // リダイレクト先を取得
        $redirectTo = $request->input('redirect_to');
        
        if ($redirectTo) {
            // リダイレクト先が指定されている場合
            return redirect($redirectTo)->with('message', 'インストールが完了しました。');
        } else {
            // AJAX呼び出しの場合はJSONレスポンス
            return response()->json(['success' => true, 'message' => 'インストールが完了しました。']);
        }
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
                $env = preg_replace(
                    "/^{$key}=.*/m",
                    "{$key}={$value}",
                    $env
                );
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
        
        // `base_settings` に基本設定を保存（.envとの同期用）
        Log::info('initializeDatabase - base_settings更新開始');
        
        $baseSettings = [
            // App settings
            'app_name' => $data['site_name'],
            'locale' => $data['app_locale'] ?? 'ja',
            'timezone' => $data['app_timezone'] ?? 'Asia/Tokyo',
            
            // Mail settings
            'mail_mailer' => $data['mail_mailer'] ?? 'smtp',
            'mail_host' => $data['mail_host'] ?? 'localhost',
            'mail_port' => (string) ($data['mail_port'] ?? 587),
            'mail_username' => $data['mail_username'] ?? '',
            'mail_password' => $data['mail_password'] ?? '',
            'mail_encryption' => $data['mail_encryption'] ?? '',
            'mail_from_address' => $data['mail_from_address'] ?? $data['admin_email'],
            
            // Other settings
            'maintenance_mode' => '0', // デフォルト: 無効
            'maintenance_message' => '現在メンテナンス中です。しばらくお待ちください。',
            'system_admin_email' => $data['admin_email'], // 管理者メールアドレスを設定
            
            // Legacy site_name for backward compatibility
            'site_name' => $data['site_name'],
        ];
        
        foreach ($baseSettings as $name => $value) {
            if (!DB::connection('mysql')->table('base_settings')->where('name', $name)->exists()) {
                DB::connection('mysql')->table('base_settings')->insert([
                    'name' => $name,
                    'value' => $value,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                Log::info("initializeDatabase - {$name}新規作成: {$value}");
            } else {
                // 既存の設定を更新
                DB::connection('mysql')->table('base_settings')
                    ->where('name', $name)
                    ->update(['value' => $value, 'updated_at' => now()]);
                Log::info("initializeDatabase - {$name}更新: {$value}");
            }
        }

        // `base_settings` に管理画面URLを追加 (存在しない場合のみ)
        if (!DB::connection('mysql')->table('base_settings')->where('name', 'admin_url')->exists()) {
            DB::connection('mysql')->table('base_settings')->insert([
                'name' => 'admin_url',
                'value' => $data['admin_url'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            Log::info('initializeDatabase - admin_url新規作成: ' . $data['admin_url']);
        } else {
            // 既存の管理画面URLを更新
            DB::connection('mysql')->table('base_settings')
                ->where('name', 'admin_url')
                ->update(['value' => $data['admin_url'], 'updated_at' => now()]);
            Log::info('initializeDatabase - admin_url更新: ' . $data['admin_url']);
        }

        // メールテスト結果をbase_settingsに保存
        Log::info('initializeDatabase - メールテスト結果保存開始');
        $mailTestFields = [
            'mail_connection_tested' => $data['mail_connection_tested'] ?? 0,
            'mail_connection_test_date' => $data['mail_connection_test_date'] ?? null,
            'mail_send_tested' => $data['mail_send_tested'] ?? 0,
            'mail_send_test_date' => $data['mail_send_test_date'] ?? null,
            'mail_receive_tested' => $data['mail_receive_tested'] ?? 0,
            'mail_receive_test_date' => $data['mail_receive_test_date'] ?? null,
        ];

        foreach ($mailTestFields as $fieldName => $fieldValue) {
            if ($fieldValue !== null) {
                if (!DB::connection('mysql')->table('base_settings')->where('name', $fieldName)->exists()) {
                    DB::connection('mysql')->table('base_settings')->insert([
                        'name' => $fieldName,
                        'value' => $fieldValue,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    Log::info("initializeDatabase - {$fieldName}新規作成: {$fieldValue}");
                } else {
                    DB::connection('mysql')->table('base_settings')
                        ->where('name', $fieldName)
                        ->update(['value' => $fieldValue, 'updated_at' => now()]);
                    Log::info("initializeDatabase - {$fieldName}更新: {$fieldValue}");
                }
            }
        }
        Log::info('initializeDatabase - メールテスト結果保存完了');


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
            
            // Session settings (フォールバック用)
            'session_driver' => 'database',
            'session_lifetime' => '120',
            'session_encrypt' => '0',
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
        
        // インストール時に選択された言語を取得
        $installLocale = $data['app_locale'] ?? session('install_locale', 'ja');
        Log::info('initializeDatabase - インストール言語設定: ' . $installLocale);
        
        if ($admin) {
            // 既存の管理者を更新 - 正しいロール値を使用
            Log::info('initializeDatabase - 既存管理者更新: ID=' . $admin->id);
            DB::connection('mysql')->table('members')
                ->where('id', $admin->id)
                ->update([
                    'name' => $data['admin_name'],
                    'locale' => $installLocale, // インストール時の言語設定を反映
                    'password' => Hash::make($adminPassword),
                    'role' => 10, // super_admin
                    'status' => 1, // active
                    'updated_at' => now(),
                ]);
            Log::info('initializeDatabase - 既存管理者更新完了（言語設定: ' . $installLocale . '）');
        } else {
            // 新しい管理者を作成
            Log::info('initializeDatabase - 新規管理者作成開始');
            $memberId = DB::connection('mysql')->table('members')->insertGetId([
                'name' => $data['admin_name'],
                'email' => $data['admin_email'],
                'locale' => $installLocale, // インストール時の言語設定を反映
                'role' => 10, // super_admin
                'password' => Hash::make($adminPassword),
                'status' => 1, // active
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            Log::info('initializeDatabase - 新規管理者作成完了: ID=' . $memberId . '（言語設定: ' . $installLocale . '）');
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
