<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
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

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * インストール - 完了画面
 */
class InstallCompleteController extends BaseInstallController
{
    /**
     * 完了画面を表示
     */
    public function show()
    {
        Log::channel('install')->info('=== InstallCompleteController::show() 開始 ===');

        // リダイレクトループ防止フラグをクリア
        session()->forget('_redirect_to_complete');

        // 現在の環境変数をログ出力
        $installed = env('INSTALLED');
        Log::channel('install')->info('INSTALLED環境変数の値: '.var_export($installed, true));

        // インストール状態の判定
        $isInstalled = ($installed === 'true' || $installed === true);

        if ($isInstalled) {
            Log::channel('install')->info('INSTALLED=true: フロントページにリダイレクト');

            return redirect('/')->with('message', 'インストールは既に完了しています。');
        }

        Log::channel('install')->info('インストール完了画面を表示（ボタン押下待ち）');

        // セッションデータから管理画面URLを先に取得
        $adminSlug = session('install_data.admin_url', 'admin');

        // force_ssl設定を確認
        $forceSsl = false;

        // まず.envのFORCE_SSLを確認
        $envForceSsl = env('FORCE_SSL');
        if ($envForceSsl === 'true' || $envForceSsl === true) {
            $forceSsl = true;
        } else {
            // base_settingsからも確認
            try {
                $forceSsl = DB::table('base_settings')
                    ->where('name', 'force_ssl')
                    ->value('value') === '1';
            } catch (\Exception $e) {
                Log::channel('install')->warning('force_ssl設定の取得に失敗: '.$e->getMessage());
            }
        }

        Log::channel('install')->info('force_ssl設定: '.($forceSsl ? 'true' : 'false'));

        // .envのAPP_URLを確実に取得する
        $envAppUrl = env('APP_URL');
        if (! $envAppUrl) {
            // フォールバック: リクエストから現在のURLを構築
            $scheme = ($forceSsl || request()->isSecure()) ? 'https' : 'http';
            $host = request()->getHost();
            $port = request()->getPort();

            if (($scheme === 'http' && $port != 80) || ($scheme === 'https' && $port != 443)) {
                $envAppUrl = $scheme.'://'.$host.':'.$port;
            } else {
                $envAppUrl = $scheme.'://'.$host;
            }
        } else {
            // APP_URLが存在する場合、force_sslが有効ならhttpsに変換
            if ($forceSsl) {
                $envAppUrl = preg_replace('/^http:/', 'https:', $envAppUrl);
            }
        }

        config()->set('app.url', $envAppUrl);

        // アプリケーションURLの取得
        $appUrl = rtrim(config('app.url'), '/');
        // 管理画面URLを取得
        $adminUrl = rtrim($appUrl.'/'.$adminSlug, '/');
        $adminLoginUrl = $adminUrl.'/login';

        // admin_modeをbase_settingsから取得
        $isSimpleMode = false;
        try {
            $adminMode = DB::table('base_settings')
                ->where('name', 'admin_mode')
                ->value('value');
            $isSimpleMode = ($adminMode === '0' || $adminMode === null);
        } catch (\Exception $e) {
            Log::channel('install')->warning('admin_mode設定の取得に失敗: '.$e->getMessage());
        }

        // セッションデータを削除
        session()->forget('install_data');

        Log::channel('install')->info('完了画面を表示: appUrl='.$appUrl.', adminLoginUrl='.$adminLoginUrl.', isSimpleMode='.($isSimpleMode ? 'true' : 'false'));
        Log::channel('install')->info('APP_URL取得結果: '.$envAppUrl);
        Log::channel('install')->info('リダイレクトフラグクリア完了');

        Log::channel('install')->info('=== InstallCompleteController::show() 終了 ===');

        // 完了画面を表示（INSTALLED=trueの設定はfinalizeメソッドで行う）
        return view('install.complete', compact('appUrl', 'adminUrl', 'adminLoginUrl', 'isSimpleMode'));
    }

    /**
     * インストール最終化（INSTALLED=trueを設定）
     */
    public function finalize(Request $request)
    {
        Log::channel('install')->info('=== InstallCompleteController::finalize() 開始 ===');

        // INSTALLED=trueを設定 & セッションドライバーをguard-aware-databaseに戻す
        Log::channel('install')->info('INSTALLED=trueを設定中...');
        $this->updateEnv([
            'INSTALLED' => 'true',
            'SESSION_DRIVER' => 'guard-aware-database',
        ]);

        // 環境変数を即座に反映（putenvで現在のプロセスに反映）
        putenv('INSTALLED=true');
        $_ENV['INSTALLED'] = 'true';
        $_SERVER['INSTALLED'] = 'true';

        // Artisanコマンドは実行しない（APP_KEY再生成とセッション破壊を防ぐため）
        Log::channel('install')->info('INSTALLED=true設定完了 & セッションドライバーをguard-aware-databaseに復元', [
            'env_INSTALLED' => env('INSTALLED'),
            'putenv_check' => getenv('INSTALLED'),
        ]);

        // リダイレクト先を取得
        $redirectTo = $request->input('redirect_to');

        Log::channel('install')->info('finalize: リダイレクト先', [
            'redirect_to' => $redirectTo,
            'request_all' => $request->all(),
            'has_session' => $request->hasSession(),
            'session_id' => $request->hasSession() ? $request->session()->getId() : 'no session',
        ]);

        if ($redirectTo) {
            // リダイレクト先が指定されている場合
            Log::channel('install')->info('finalize: リダイレクト実行', ['url' => $redirectTo]);

            return redirect($redirectTo)->with('message', 'インストールが完了しました。');
        } else {
            // AJAX呼び出しの場合はJSONレスポンス
            Log::channel('install')->info('finalize: JSONレスポンス返却');

            return response()->json(['success' => true, 'message' => 'インストールが完了しました。']);
        }

        Log::channel('install')->info('=== InstallCompleteController::finalize() 終了 ===');
    }

    /**
     * .envファイルを更新する
     */
    protected function updateEnv(array $values): void
    {
        $envPath = base_path('.env');

        // .envがない場合は.env.exampleからコピー
        if (! File::exists($envPath)) {
            File::copy(base_path('.env.example'), $envPath);
        }

        $env = File::get($envPath);

        foreach ($values as $key => $value) {
            // 値にスペース、特殊文字、または空の場合は引用符で囲む
            $formattedValue = $this->formatEnvValue($value);

            if (preg_match("/^{$key}=/m", $env)) {
                // 既存の値を更新
                $env = preg_replace(
                    "/^{$key}=.*/m",
                    "{$key}={$formattedValue}",
                    $env
                );
            } else {
                // .envに存在しない場合は末尾に追加
                $env .= "\n{$key}={$formattedValue}";
            }
        }

        File::put($envPath, $env);
    }

    /**
     * .env用に値をフォーマットする
     */
    protected function formatEnvValue($value): string
    {
        // nullの場合は空文字列
        if ($value === null) {
            return '';
        }

        // booleanの場合は文字列に変換
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        $value = (string) $value;

        // 空文字列、スペース、特殊文字を含む場合は引用符で囲む
        if ($value === '' ||
            preg_match('/[\s"\'#$]/', $value) ||
            str_contains($value, '=')) {
            // 既に引用符で囲まれている場合はそのまま
            if (preg_match('/^".*"$/', $value) || preg_match("/^'.*'$/", $value)) {
                return $value;
            }

            // ダブルクォートで囲む（内部のダブルクォートはエスケープ）
            return '"'.str_replace('"', '\\"', $value).'"';
        }

        return $value;
    }
}
