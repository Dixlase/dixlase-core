<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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

namespace App\Http\Controllers\Install;

use App\Helpers\GitExcludeHelper;
use App\Helpers\GitIgnoreHelper;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * インストール - 確認画面
 */
class InstallConfirmController extends BaseInstallController
{
    /**
     * 入力内容の確認画面を表示
     */
    public function show()
    {
        $locale = $this->getCurrentLocale();
        app()->setLocale($locale);

        $data = session('install_data', []);

        // デバッグ情報をログに出力
        Log::channel('install')->info('確認画面表示時のセッションデータ', [
            'session_id' => session()->getId(),
            'session_keys' => array_keys($data),
            'has_site_name' => isset($data['site_name']),
            'has_admin_account_name' => isset($data['admin_account_name']),
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
            'full_data_keys' => $data ? array_keys($data) : 'empty',
        ]);

        // 必須フィールドのチェックと不足フィールドに基づく適切なステップへのリダイレクト
        $steps = [
            'settings' => ['site_name', 'admin_account_name', 'admin_email', 'admin_password'],
            'environment' => ['app_env', 'app_url', 'admin_url', 'app_timezone'],
            'database' => ['db_connection', 'db_host', 'db_port', 'db_database', 'db_username'],
        ];

        // セッションデータが完全に空の場合は最初からやり直し
        if (empty($data)) {
            Log::channel('install')->error('セッションデータが完全に空です');

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
        if (! empty($missingFields)) {
            $firstMissing = $missingFields[0];
            Log::channel('install')->error('必須フィールドが不足しています', [
                'missing_fields' => $missingFields,
                'session_keys' => array_keys($data),
                'session_id' => session()->getId(),
                'redirecting_to_step' => $firstMissing['step'],
            ]);

            $route = 'install.'.($firstMissing['step'] === 'settings' ? 'create' : $firstMissing['step'].'.create');

            return redirect()->route($route)
                ->with('error', __('install/common.missing_required_fields')." (不足フィールド: {$firstMissing['field']})");
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
            'availableLocales' => $this->availableLocales,
        ]);
    }

    /**
     * インストール実行
     */
    public function store()
    {
        try {
            Log::channel('install')->info('=== インストール開始 ===');

            $data = session('install_data');
            Log::channel('install')->info('セッションデータ取得完了', [
                'keys' => array_keys($data ?? []),
                'session_id' => session()->getId(),
                'full_data' => $data,
            ]);

            // セッションデータが空の場合は確認画面にリダイレクト
            if (empty($data)) {
                Log::channel('install')->error('セッションデータが空です - 確認画面にリダイレクト');

                return redirect()->route('install.confirm')
                    ->with('error', '');
            }

            // 管理者パスワードを復号化
            $adminPassword = Crypt::decryptString($data['admin_password']);
            Log::channel('install')->info('管理者パスワード復号化完了');

            // DBパスワードを復号化（空文字列の場合は復号化しない）
            $dbPassword = (! empty($data['db_password'])) ? Crypt::decryptString($data['db_password']) : '';
            Log::channel('install')->info('DBパスワード復号化完了');

            // メールパスワードを復号化（空文字列の場合は復号化しない）
            $mailPassword = (! empty($data['mail_password'])) ? Crypt::decryptString($data['mail_password']) : '';
            Log::channel('install')->info('メールパスワード復号化完了');

            // force_sslの値を取得
            $forceSslBool = ! empty($data['force_ssl']);

            // force_sslに基づいてAPP_URLのプロトコルを決定
            $protocol = $forceSslBool ? 'https://' : 'http://';
            $appUrl = $protocol.$data['app_url'];

            // セッションから言語設定を取得
            $locale = session('install_locale', 'en');

            $envData = [
                'APP_NAME' => $data['site_name'],
                'APP_ENV' => $data['app_env'],
                'APP_DEBUG' => $data['app_debug'] ? 'true' : 'false',
                'APP_URL' => $appUrl,
                'APP_LOCALE' => $data['app_locale'] ?? 'ja',
                'APP_TIMEZONE' => $data['app_timezone'] ?? 'Asia/Tokyo',
                'INSTALLED' => 'false',
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
                'DB_PASSWORD' => $dbPassword ?? '',
            ];

            // .envファイル更新
            Log::channel('install')->info('.envファイル更新開始');
            $this->updateEnv($envData);
            Log::channel('install')->info('.envファイル更新完了');

            // 設定をクリアし、新しい.envを適用
            Log::channel('install')->info('設定キャッシュクリア開始');
            Artisan::call('config:clear');
            Log::channel('install')->info('設定キャッシュクリア完了');

            // マイグレーション実行中はセッションドライバーを一時的にfileに変更
            $envPath = base_path('.env');
            $envContent = file_get_contents($envPath);

            // 元のSESSION_DRIVERを保存
            preg_match('/SESSION_DRIVER=(.+)/', $envContent, $matches);
            $originalSessionDriver = $matches[1] ?? 'guard-aware-database';

            // SESSION_DRIVERをfileに変更
            $envContent = preg_replace('/SESSION_DRIVER=.+/', 'SESSION_DRIVER=file', $envContent);
            file_put_contents($envPath, $envContent);

            // 設定を再読み込み
            Artisan::call('config:clear');
            Log::channel('install')->info('セッションドライバーを一時的にfileに変更', ['original' => $originalSessionDriver]);

            // データベースをリセットするかどうかを確認
            if (empty($data['preserve_data'])) {
                Log::channel('install')->info('データベースリセット＆マイグレーション開始');
                Artisan::call('migrate:fresh', ['--force' => true]);
                Log::channel('install')->info('データベースリセット＆マイグレーション完了');
            } else {
                Log::channel('install')->info('マイグレーション開始（データ保持）');
                Artisan::call('migrate', ['--force' => true]);
                Log::channel('install')->info('マイグレーション完了（データ保持）');
            }

            // セッションドライバーを元に戻す
            $envContent = file_get_contents($envPath);
            $envContent = preg_replace('/SESSION_DRIVER=.+/', 'SESSION_DRIVER='.$originalSessionDriver, $envContent);
            file_put_contents($envPath, $envContent);

            // 設定を再読み込み
            Artisan::call('config:clear');
            Log::channel('install')->info('セッションドライバーを復元', ['driver' => $originalSessionDriver]);

            // DB接続を再確立（テーブルプレフィックスを正しく適用するため）
            DB::purge();
            DB::reconnect();

            // デバッグ: 現在のテーブルプレフィックスと全テーブル一覧を取得
            $prefix = DB::connection()->getTablePrefix();
            $tables = DB::select('SHOW TABLES');
            $tableNames = array_map(function ($table) {
                return array_values((array) $table)[0];
            }, $tables);
            Log::channel('install')->info('DB接続を再確立しました', [
                'prefix' => $prefix,
                'tables_count' => count($tableNames),
                'sample_tables' => array_slice($tableNames, 0, 5),
            ]);

            // シーダー実行（テーブル存在チェックをスキップして無条件で実行）
            Log::channel('install')->info('DatabaseSeeder実行開始');
            Artisan::call('db:seed', [
                '--class' => 'DatabaseSeeder',
                '--force' => true,
            ]);
            Log::channel('install')->info('DatabaseSeeder実行完了');

            // テーマのマイグレーションを実行
            Log::channel('install')->info('DixlaseOnePage マイグレーション開始');
            Artisan::call('migrate', [
                '--path' => 'themes/DixlaseOnePage/database/migrations',
                '--force' => true,
            ]);
            Log::channel('install')->info('DixlaseOnePage マイグレーション完了');

            // テーマシーダーを実行
            // composer.local.json 未反映環境にも対応するため、動的にPSR-4を登録
            Log::channel('install')->info('DixlaseOnePage DatabaseSeeder実行開始');
            $this->registerThemeAutoload('DixlaseOnePage');
            Artisan::call('db:seed', [
                '--class' => 'Themes\\DixlaseOnePage\\Database\\Seeders\\DatabaseSeeder',
                '--force' => true,
            ]);
            Log::channel('install')->info('DixlaseOnePage DatabaseSeeder実行完了（設定 + 権限）');

            // 初期データの投入
            Log::channel('install')->info('初期データ投入開始');
            $this->initializeDatabase($data, $adminPassword);
            Log::channel('install')->info('初期データ投入完了');

            // ストレージのシンボリックリンクを作成
            Log::channel('install')->info('ストレージシンボリックリンク作成開始');
            Artisan::call('storage:link');
            Log::channel('install')->info('ストレージシンボリックリンク作成完了');

            // アクティブなテーマのシンボリックリンクを作成
            Log::channel('install')->info('テーマシンボリックリンク作成開始');
            $themeSetting = DB::table('theme_settings')
                ->where('key', 'enabled_theme_id')
                ->first();

            $activeTheme = null;
            if ($themeSetting && $themeSetting->value) {
                $activeTheme = DB::table('themes')
                    ->where('id', $themeSetting->value)
                    ->select('directory')
                    ->first();
            }

            if ($activeTheme) {
                try {
                    Artisan::call('dls:theme:symlink', [
                        'action' => 'create',
                        'theme' => $activeTheme->directory,
                    ]);
                    Log::channel('install')->info('テーマシンボリックリンク作成完了', ['theme' => $activeTheme->directory]);

                    GitExcludeHelper::addThemeExclusion($activeTheme->directory);
                    GitIgnoreHelper::addThemeExclusion($activeTheme->directory);
                    Log::channel('install')->info('テーマGit除外ルール追加完了', ['theme' => $activeTheme->directory]);
                } catch (\Exception $e) {
                    Log::channel('install')->error("シンボリックリンクの作成に失敗しました: {$e->getMessage()}");
                }
            } else {
                Log::channel('install')->warning('アクティブなテーマが見つかりません。シンボリックリンクは作成されませんでした。');
            }

            // ファイル整合性ベースラインを生成
            Log::channel('install')->info('ファイル整合性ベースライン生成開始');
            try {
                $fileIntegrityService = app(\App\Services\FileIntegrityService::class);
                $baseline = $fileIntegrityService->generateCoreBaseline();
                $fileIntegrityService->saveBaselineArray($baseline);

                \App\Models\FileIntegrityAudit::create([
                    'scope' => \App\Models\FileIntegrityAudit::SCOPE_CORE,
                    'trigger' => \App\Models\FileIntegrityAudit::TRIGGER_INSTALL,
                    'initiated_by_type' => \App\Models\FileIntegrityAudit::INITIATED_BY_SYSTEM,
                    'status' => \App\Models\FileIntegrityAudit::STATUS_OK,
                    'hash_algo' => 'sha256',
                    'baseline_version' => $baseline['meta']['app_version'] ?? null,
                    'total_files_scanned' => count($baseline['files']),
                    'started_at' => now(),
                    'finished_at' => now(),
                    'duration_ms' => 0,
                    'summary' => __('command.integrity.baseline_generated'),
                ]);

                Log::channel('install')->info('ファイル整合性ベースライン生成完了', [
                    'files_count' => count($baseline['files']),
                    'version' => $baseline['meta']['app_version'] ?? 'unknown',
                ]);
            } catch (\Exception $e) {
                Log::channel('install')->warning('ファイル整合性ベースライン生成に失敗しましたが、インストールは続行します', [
                    'error' => $e->getMessage(),
                ]);
            }

            // バンドルテーマのセキュリティ監査を実行
            $this->auditBundledThemes();

            // セッションデータを削除
            session()->forget('install_data');
            Log::channel('install')->info('=== インストール完了 ===');
            Log::channel('install')->info('install.completeルートにリダイレクト中...');

            return redirect()->route('install.complete');
        } catch (\Exception $e) {
            Log::channel('install')->error('=== インストールエラー ===', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'session_data_exists' => ! empty($data),
                'session_keys' => array_keys($data ?? []),
            ]);

            // セッションデータが失われていないことを確認
            if (empty(session('install_data'))) {
                Log::channel('install')->warning('セッションデータが失われています - 復元を試行');
                if (! empty($data)) {
                    session(['install_data' => $data]);
                    Log::channel('install')->info('セッションデータを復元しました');
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
        $errorMessage = $e->getMessage();

        if (strpos($errorMessage, 'database') !== false) {
            return __('install/error.database_general');
        }

        if (strpos($errorMessage, 'file') !== false || strpos($errorMessage, 'directory') !== false) {
            return __('install/error.file_system');
        }

        if (strpos($errorMessage, 'env') !== false || strpos($errorMessage, 'environment') !== false) {
            return __('install/error.environment');
        }

        return __('install/error.unknown');
    }

    /**
     * テーマの PSR-4 オートロードを動的に登録
     *
     * composer.local.json が未反映の環境（Docker ビルド時に --no-scripts で
     * sync-local-autoload.php が実行されなかった場合等）でも、インストール中に
     * テーマのシーダー等を読み込めるようにする。
     */
    private function registerThemeAutoload(string $themeDirectory): void
    {
        $loaders = \Composer\Autoload\ClassLoader::getRegisteredLoaders();
        if (empty($loaders)) {
            return;
        }

        $loader = reset($loaders);
        $baseNs = "Themes\\{$themeDirectory}\\";
        $basePath = base_path("themes/{$themeDirectory}");

        $loader->addPsr4($baseNs.'App\\', $basePath.'/app');
        $loader->addPsr4($baseNs.'Database\\Factories\\', $basePath.'/database/factories');
        $loader->addPsr4($baseNs.'Database\\Seeders\\', $basePath.'/database/seeders');
    }

    /**
     * 初期データをデータベースに追加する
     */
    private function initializeDatabase(array $data, string $adminPassword)
    {
        Log::channel('install')->info('initializeDatabase - 開始: admin_email='.$data['admin_email'].', admin_account_name='.$data['admin_account_name']);

        Log::channel('install')->info('initializeDatabase - base_settings更新開始');

        $baseSettings = [
            'app_name' => $data['site_name'],
            'locale' => $data['app_locale'] ?? 'ja',
            'timezone' => $data['app_timezone'] ?? 'Asia/Tokyo',
            'mail_mailer' => $data['mail_mailer'] ?? 'smtp',
            'mail_host' => $data['mail_host'] ?? 'localhost',
            'mail_port' => (string) ($data['mail_port'] ?? 587),
            'mail_username' => $data['mail_username'] ?? '',
            'mail_password' => $data['mail_password'] ?? '',
            'mail_encryption' => $data['mail_encryption'] ?? '',
            'mail_from_address' => $data['mail_from_address'] ?? $data['admin_email'],
            'maintenance_mode' => '0',
            'maintenance_message' => '現在メンテナンス中です。しばらくお待ちください。',
            'system_admin_email' => $data['admin_email'],
            'site_name' => $data['site_name'],
            'admin_mode' => (string) ($data['install_mode'] ?? 0),
        ];

        foreach ($baseSettings as $name => $value) {
            if (! DB::connection('mysql')->table('base_settings')->where('name', $name)->exists()) {
                DB::connection('mysql')->table('base_settings')->insert([
                    'name' => $name,
                    'value' => $value,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                Log::channel('install')->info("initializeDatabase - {$name}新規作成: {$value}");
            } else {
                DB::connection('mysql')->table('base_settings')
                    ->where('name', $name)
                    ->update(['value' => $value, 'updated_at' => now()]);
                Log::channel('install')->info("initializeDatabase - {$name}更新: {$value}");
            }
        }

        // 管理画面URLを追加
        if (! DB::connection('mysql')->table('base_settings')->where('name', 'admin_url')->exists()) {
            DB::connection('mysql')->table('base_settings')->insert([
                'name' => 'admin_url',
                'value' => $data['admin_url'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            Log::channel('install')->info('initializeDatabase - admin_url新規作成: '.$data['admin_url']);
        } else {
            DB::connection('mysql')->table('base_settings')
                ->where('name', 'admin_url')
                ->update(['value' => $data['admin_url'], 'updated_at' => now()]);
            Log::channel('install')->info('initializeDatabase - admin_url更新: '.$data['admin_url']);
        }

        // メールテスト結果を保存
        Log::channel('install')->info('initializeDatabase - メールテスト結果保存開始');
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
                if (! DB::connection('mysql')->table('base_settings')->where('name', $fieldName)->exists()) {
                    DB::connection('mysql')->table('base_settings')->insert([
                        'name' => $fieldName,
                        'value' => $fieldValue,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    Log::channel('install')->info("initializeDatabase - {$fieldName}新規作成: {$fieldValue}");
                } else {
                    DB::connection('mysql')->table('base_settings')
                        ->where('name', $fieldName)
                        ->update(['value' => $fieldValue, 'updated_at' => now()]);
                    Log::channel('install')->info("initializeDatabase - {$fieldName}更新: {$fieldValue}");
                }
            }
        }
        Log::channel('install')->info('initializeDatabase - メールテスト結果保存完了');

        // security_settingsの各設定を更新または作成
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

        // 管理者アカウント処理
        Log::channel('install')->info('initializeDatabase - 管理者アカウント処理開始');
        $admin = DB::connection('mysql')->table('members')->where('email', $data['admin_email'])->first();

        $installLocale = $data['app_locale'] ?? session('install_locale', 'ja');
        Log::channel('install')->info('initializeDatabase - インストール言語設定: '.$installLocale);

        if ($admin) {
            Log::channel('install')->info('initializeDatabase - 既存管理者更新: ID='.$admin->id);
            DB::connection('mysql')->table('members')
                ->where('id', $admin->id)
                ->update([
                    'account_name' => $data['admin_account_name'],
                    'display_name' => $data['admin_display_name'] ?? null,
                    'locale' => $installLocale,
                    'password' => Hash::make($adminPassword),
                    'role' => 10,
                    'status' => 1,
                    'email_verified_at' => now(),
                    'updated_at' => now(),
                ]);
            Log::channel('install')->info('initializeDatabase - 既存管理者更新完了（言語設定: '.$installLocale.'）');
        } else {
            Log::channel('install')->info('initializeDatabase - 新規管理者作成開始');
            $memberId = DB::connection('mysql')->table('members')->insertGetId([
                'account_name' => $data['admin_account_name'],
                'display_name' => $data['admin_display_name'] ?? null,
                'email' => $data['admin_email'],
                'email_verified_at' => now(),
                'locale' => $installLocale,
                'role' => 10,
                'password' => Hash::make($adminPassword),
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            Log::channel('install')->info('initializeDatabase - 新規管理者作成完了: ID='.$memberId.'（言語設定: '.$installLocale.'）');
        }

        $memberCount = DB::connection('mysql')->table('members')->count();
        Log::channel('install')->info('initializeDatabase - membersテーブル総レコード数: '.$memberCount);
        Log::channel('install')->info('initializeDatabase - 完了');
    }

    /**
     * バンドルテーマのセキュリティ監査を実行
     *
     * インストール時に登録されたテーマの権限宣言・署名・CSP準拠状況をスキャンし、
     * 監査結果をDBに保存する。失敗してもインストールは続行する。
     *
     * @see \App\Http\Controllers\Admin\Settings\AdminThemesSettingsController::runThemeAudit()
     */
    private function auditBundledThemes(): void
    {
        Log::channel('install')->info('バンドルテーマのセキュリティ監査開始');

        $themes = DB::table('themes')->select('slug')->get();

        foreach ($themes as $theme) {
            try {
                $slug = $theme->slug;
                Log::channel('install')->info("テーマ監査開始: {$slug}");

                // 権限宣言の整合性チェック（theme.json vs コード実態）
                Artisan::call('dls:theme:audit', [
                    'theme' => $slug,
                    '--json' => true,
                ]);

                $output = trim(Artisan::output());
                $result = json_decode($output, true);

                if (json_last_error() !== JSON_ERROR_NONE || ! is_array($result)) {
                    Log::channel('install')->warning("テーマ監査のJSON解析に失敗: {$slug}");

                    continue;
                }

                // evidenceを制限（DBサイズ削減）
                $mismatches = $result['mismatches'] ?? [];
                foreach ($mismatches as &$mismatch) {
                    if (isset($mismatch['evidence']) && is_array($mismatch['evidence'])) {
                        $mismatch['evidence'] = array_slice($mismatch['evidence'], 0, 3);
                    }
                }
                unset($mismatch);

                // 署名情報を取得
                $permissionService = app(ThemePermissionService::class);
                $summary = $permissionService->getSummary($slug);
                $signature = $summary['signature'] ?? [];

                // CSP準拠状況をコードスキャンで検証
                $cspScanner = app(CspComplianceScanner::class);
                $cspCompatibility = $cspScanner->scanTheme($slug);

                $auditData = [
                    'has_mismatches' => ! empty($mismatches),
                    'mismatches' => $mismatches,
                    'matches_count' => count($result['matches'] ?? []),
                    'total_checked' => $result['total_checked'] ?? 0,
                    'risk_level' => $result['risk_level'] ?? null,
                    'risk_reasons' => $result['risk_reasons'] ?? [],
                    'signature_status' => $signature['status'] ?? 'unsigned',
                    'signature_signer' => $signature['signer'] ?? null,
                    'csp_status' => $cspCompatibility['status'] ?? 'not_checked',
                    'csp_requires_inline_js' => $cspCompatibility['requires_inline_js'] ?? false,
                    'csp_requires_inline_css' => $cspCompatibility['requires_inline_css'] ?? false,
                    'csp_violations' => $cspCompatibility['violations'] ?? [],
                    'csp_summary' => $cspCompatibility['summary'] ?? [],
                ];

                ThemeAudit::saveAuditResult($slug, $auditData);

                Log::channel('install')->info("テーマ監査完了: {$slug}", [
                    'risk_level' => $auditData['risk_level'],
                    'csp_status' => $auditData['csp_status'],
                    'has_mismatches' => $auditData['has_mismatches'],
                ]);
            } catch (\Exception $e) {
                Log::channel('install')->warning("テーマ監査に失敗しましたが、インストールは続行します: {$theme->slug}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::channel('install')->info('バンドルテーマのセキュリティ監査完了');
    }
}
