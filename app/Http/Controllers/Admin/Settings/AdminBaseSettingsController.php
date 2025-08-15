<?php

/**
 * This file is part of MySoftware.
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

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Models\BaseSetting;
use App\Http\Requests\Admin\Settings\AdminBaseSettingsRequest;
use App\Helpers\EnvHelper;
use App\Helpers\TimezoneHelper;
use App\Facades\BaseSettings;
use DateTime;
use DateTimeZone;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;

class AdminBaseSettingsController extends AdminLoggedInController
{

    //初期設定を行う
    public function __construct()
    {
        // 親クラスのコンストラクタを呼び出す
        parent::__construct();
    }

    /**
     * 設定の表示
     */

    public function index()
    {
        $settings = [
            // .env から読み取る設定
            'app_name' => env('APP_NAME', 'MySoftware'),
            'locale' => env('APP_LOCALE', 'ja'),
            'timezone' => env('APP_TIMEZONE', 'Asia/Tokyo'),

            'mail_mailer' => env('MAIL_MAILER', 'smtp'),
            'mail_host' => env('MAIL_HOST', 'smtp.example.com'),
            'mail_port' => env('MAIL_PORT', '587'),
            'mail_username' => env('MAIL_USERNAME', ''),
            'mail_password' => env('MAIL_PASSWORD', ''),
            'mail_encryption' => env('MAIL_ENCRYPTION', 'tls'),
            'mail_from_address' => env('MAIL_FROM_ADDRESS', 'no-reply@example.com'),

            // メンテナンスモードのON/OFFも.envから読み取り
            'maintenance_mode' => env('MAINTENANCE_MODE', 'false'),
            
            // データベースから読み取る設定（メンテナンスメッセージのみ）
            'maintenance_message' => BaseSetting::getValue('maintenance_message', '現在メンテナンス中です。しばらくお待ちください。'),
        ];

        $timezones = TimezoneHelper::getTimezonesWithUtcOffset();

        // メール接続テストの状態を取得
        $mailConnectionTested = (bool) BaseSetting::getValue('mail_connection_tested', false);
        $mailConnectionTestDate = BaseSetting::getValue('mail_connection_test_date', null);

        $this->viewParams['settings'] = $settings;
        $this->viewParams['timezones'] = $timezones;
        $this->viewParams['mailConnectionTested'] = $mailConnectionTested;
        $this->viewParams['mailConnectionTestDate'] = $mailConnectionTestDate;
        $this->viewParams['locales'] = collect(config('admin.locale.available', []))->mapWithKeys(function ($locale, $key) {
            return [$key => $locale['name']];
        })->toArray();
        $this->viewParams['mailers'] = __('mail.mailers');
        $this->viewParams['encryptions'] = __('mail.encryptions');

        return view(
            'admin::settings.base.index',
            $this->viewParams
        );
    }

    /**
     * 設定の更新
     */
    public function update(AdminBaseSettingsRequest $request)
    {


        // DBに保存するもの（メンテナンスメッセージのみ）
        $settings = $request->only([
            'maintenance_message',
        ]);

        // .envに保存するもの（メンテナンスモードのON/OFFも含む）
        $envData = $request->only([
            'app_name',
            'locale',
            'timezone',
            'mail_mailer',
            'mail_host',
            'mail_port',
            'mail_username',
            'mail_password',
            'mail_encryption',
            'mail_from_address',
            'maintenance_mode',
        ]);

        // 空文字列をnullに変換（mail_username, mail_password, mail_encryption のみ）
        // mail_host と mail_port は空文字列のままで保存
        $nullableMailFields = ['mail_username', 'mail_password', 'mail_encryption'];
        foreach ($nullableMailFields as $field) {
            if (isset($envData[$field]) && $envData[$field] === '') {
                $envData[$field] = null;
            }
        }

        // APP_FAKER_LOCALEとAPP_FALLBACK_LOCALEを選択された言語に基づいて自動設定
        if (isset($envData['locale'])) {
            $availableLocales = config('admin.locale.available', []);
            $envData['faker_locale'] = $availableLocales[$envData['locale']]['faker_locale'] ?? 'ja_JA';
            
            // フォールバック言語を選択された言語と同じに設定
            $envData['fallback_locale'] = $envData['locale'];
        }

        // メール設定が実際に変更された場合は接続テストステータスをリセット（.env更新前に確認）
        $mailKeys = ['mail_mailer', 'mail_host', 'mail_port', 'mail_username', 'mail_password', 'mail_encryption', 'mail_from_address'];
        $mailSettingsChanged = false;
        
        foreach ($mailKeys as $key) {
            if (array_key_exists($key, $envData)) {
                $currentValue = env(strtoupper($key));
                $newValue = $envData[$key];
                
                // null値を空文字列に正規化して比較
                $currentValue = $currentValue === null ? '' : (string)$currentValue;
                $newValue = $newValue === null ? '' : (string)$newValue;
                
                // 値が実際に変更された場合のみフラグを立てる
                if ($currentValue !== $newValue) {
                    $mailSettingsChanged = true;
                    break;
                }
            }
        }

        // DBに保存するもの（メンテナンスメッセージのみ）
        BaseSetting::setMany($settings);
        // .env に保存するもの（メンテナンスモードのON/OFFも含む）
        EnvHelper::update($envData);
        
        if ($mailSettingsChanged) {
            BaseSetting::setValue('mail_connection_tested', 0);
            BaseSetting::setValue('mail_connection_test_date', null);
        }

        return redirect()->route('admin.settings.base')->with('success', '設定が更新されました。');
    }

    /**
     * メール送信テスト
     */
    public function testMail(AdminBaseSettingsRequest $request)
    {
        try {
            // リクエストからメール設定を取得
            $mailSettings = $request->only([
                'mail_mailer',
                'mail_host',
                'mail_port',
                'mail_username',
                'mail_password',
                'mail_encryption',
                'mail_from_address',
            ]);

            // 一時的にメール設定を変更
            Config::set('mail.default', $mailSettings['mail_mailer']);
            Config::set('mail.mailers.smtp.host', $mailSettings['mail_host']);
            Config::set('mail.mailers.smtp.port', $mailSettings['mail_port']);
            Config::set('mail.mailers.smtp.username', $mailSettings['mail_username']);
            Config::set('mail.mailers.smtp.password', $mailSettings['mail_password']);
            Config::set('mail.mailers.smtp.encryption', $mailSettings['mail_encryption']);
            Config::set('mail.from.address', $mailSettings['mail_from_address']);
            Config::set('mail.from.name', env('APP_NAME', 'MySoftware'));

            // テストメールを送信
            $testEmail = $mailSettings['mail_from_address'];
            $appName = env('APP_NAME', 'MySoftware');
            
            // 多言語対応のメール内容を取得
            $subject = __('admin.settings.base.test_mail_subject');
            $body = __('admin.settings.base.test_mail_body', ['app_name' => $appName]);
            
            Mail::raw($body, function ($message) use ($testEmail, $appName, $subject) {
                $message->to($testEmail)
                        ->subject("[{$appName}] {$subject}");
            });

            return response()->json([
                'success' => true,
                'message' => __('admin.settings.base.test_mail_success')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('admin.settings.base.test_mail_failed', ['error' => $e->getMessage()])
            ], 400);
        }
    }

    /**
     * メールサーバー接続テスト
     */
    public function testConnection(AdminBaseSettingsRequest $request)
    {
        try {
            // リクエストからメール設定を取得
            $mailSettings = $request->only([
                'mail_mailer',
                'mail_host',
                'mail_port',
                'mail_username',
                'mail_password',
                'mail_encryption',
            ]);

            // SMTPの場合のみ接続テストを実行
            if ($mailSettings['mail_mailer'] === 'smtp') {
                $this->testSmtpConnection($mailSettings);
            } else {
                return response()->json([
                    'success' => true,
                    'message' => 'メーラー「' . $mailSettings['mail_mailer'] . '」は接続テストをサポートしていません。'
                ]);
            }

            // 接続テスト成功時にステータスを保存
            BaseSetting::setValue('mail_connection_tested', true);
            BaseSetting::setValue('mail_connection_test_date', now()->toDateTimeString());
            
            return response()->json([
                'success' => true,
                'message' => 'メールサーバーへの接続が正常に確認されました。'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'メールサーバーへの接続に失敗しました: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * SMTP接続テスト
     */
    private function testSmtpConnection(array $mailSettings)
    {
        $host = $mailSettings['mail_host'];
        $port = (int) $mailSettings['mail_port'];
        $username = $mailSettings['mail_username'];
        $password = $mailSettings['mail_password'];
        $encryption = $mailSettings['mail_encryption'];

        // ソケット接続でSMTPサーバーに接続テスト
        $context = stream_context_create();
        
        if ($encryption === 'ssl') {
            $host = 'ssl://' . $host;
        }

        $socket = @stream_socket_client(
            $host . ':' . $port,
            $errno,
            $errstr,
            10, // 10秒タイムアウト
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (!$socket) {
            throw new \Exception("接続エラー: {$errstr} (エラーコード: {$errno})");
        }

        // SMTPレスポンスを読み取り
        $response = fgets($socket);
        if (!$response || !str_starts_with($response, '220')) {
            fclose($socket);
            throw new \Exception('SMTPサーバーからの応答が不正です: ' . trim($response));
        }

        // STARTTLSが必要な場合
        if ($encryption === 'tls') {
            fwrite($socket, "EHLO localhost\r\n");
            $response = fgets($socket);
            
            fwrite($socket, "STARTTLS\r\n");
            $response = fgets($socket);
            
            if (!str_starts_with($response, '220')) {
                fclose($socket);
                throw new \Exception('STARTTLS の開始に失敗しました: ' . trim($response));
            }

            // TLS暗号化を有効にする
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                fclose($socket);
                throw new \Exception('TLS暗号化の有効化に失敗しました');
            }
        }

        // 認証テスト（ユーザー名とパスワードが設定されている場合）
        if (!empty($username) && !empty($password)) {
            fwrite($socket, "EHLO localhost\r\n");
            $response = fgets($socket);

            fwrite($socket, "AUTH LOGIN\r\n");
            $response = fgets($socket);
            
            if (!str_starts_with($response, '334')) {
                fclose($socket);
                throw new \Exception('AUTH LOGIN コマンドが失敗しました: ' . trim($response));
            }

            // ユーザー名を送信
            fwrite($socket, base64_encode($username) . "\r\n");
            $response = fgets($socket);
            
            if (!str_starts_with($response, '334')) {
                fclose($socket);
                throw new \Exception('ユーザー名認証が失敗しました: ' . trim($response));
            }

            // パスワードを送信
            fwrite($socket, base64_encode($password) . "\r\n");
            $response = fgets($socket);
            
            if (!str_starts_with($response, '235')) {
                fclose($socket);
                throw new \Exception('パスワード認証が失敗しました: ' . trim($response));
            }
        }

        // 接続を閉じる
        fwrite($socket, "QUIT\r\n");
        fclose($socket);
    }

    private function getTimezonesWithUtcOffset(): array
    {
        $timezones = [];
        $now = new DateTime('now');
        $translations = trans('timezones');

        foreach (DateTimeZone::listIdentifiers() as $timezone) {
            $tz = new DateTimeZone($timezone);
            $offset = $tz->getOffset($now);
            $sign = $offset < 0 ? '-' : '+';
            $hours = str_pad(abs($offset) / 3600, 2, '0', STR_PAD_LEFT);
            $minutes = str_pad(abs($offset) % 3600 / 60, 2, '0', STR_PAD_LEFT);
            $formattedOffset = "UTC{$sign}{$hours}:{$minutes}";
            $label = $translations[$timezone] ?? $timezone;
            $timezones[$timezone] = "（{$formattedOffset}）{$label}";
        }

        return $timezones;
    }
}
