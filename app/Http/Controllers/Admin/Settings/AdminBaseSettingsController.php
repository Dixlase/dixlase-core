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

use App\Helpers\AdminHelper;
use App\Helpers\EnvHelper;
use App\Helpers\TimezoneHelper;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Http\Requests\Admin\Settings\AdminBaseSettingsRequest;
use App\Http\Requests\Admin\Settings\MailTestRequest;
use App\Models\BaseSetting;
use App\Traits\MailTestTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Facades\BaseSettings;
use DateTime;
use DateTimeZone;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;


class AdminBaseSettingsController extends AdminLoggedInController
{
    use MailTestTrait;

    //初期設定を行う
    public function __construct()
    {
        // 親クラスのコンストラクタを呼び出す
        parent::__construct();
    }

    /**
     * 設定の表示
     */

    public function index(Request $request)
    {
        // メール受信テスト状態更新のリクエストを処理
        if ($request->isMethod('post') && $request->input('action') === 'update_receive_test_status') {
            $mailTestResults = session('mail_test_results', []);
            $mailTestResults['mail_receive_tested'] = 1;
            $mailTestResults['mail_receive_test_date'] = now()->format('Y-m-d H:i:s');
            session(['mail_test_results' => $mailTestResults]);
            
            return response()->json(['success' => true]);
        }
        
        // 基本設定ページを開くたびにメールテストセッションをクリア
        // （保存されていないテスト結果を削除）
        session()->forget('mail_test_results');
        
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
            
            // データベースから読み取る設定
            'maintenance_message' => BaseSetting::getValue('maintenance_message', '現在メンテナンス中です。しばらくお待ちください。'),
            'notification_enabled' => (bool) BaseSetting::getValue('notification_enabled', false),
            'notification_email' => BaseSetting::getValue('notification_email', ''),
            'admin_url' => BaseSetting::getValue('admin_url', config('admin.admin_url')),
            'force_ssl' => (bool) BaseSetting::getValue('force_ssl', false),
        ];

        // メールテスト状態を取得（DB優先、セッションは一時的な状態のみ）
        $sessionTestResults = session('mail_test_results', []);
        
        $mailConnectionTested = (bool) ($sessionTestResults['mail_connection_tested'] ?? BaseSetting::getValue('mail_connection_tested', false));
        $mailSendTested = (bool) ($sessionTestResults['mail_send_tested'] ?? BaseSetting::getValue('mail_send_tested', false));
        $mailReceiveTested = (bool) ($sessionTestResults['mail_receive_tested'] ?? BaseSetting::getValue('mail_receive_tested', false));
        
        $mailConnectionTestDate = $sessionTestResults['mail_connection_test_date'] ?? BaseSetting::getValue('mail_connection_test_date', '');
        $mailSendTestDate = $sessionTestResults['mail_send_test_date'] ?? BaseSetting::getValue('mail_send_test_date', '');
        $mailReceiveTestDate = $sessionTestResults['mail_receive_test_date'] ?? BaseSetting::getValue('mail_receive_test_date', '');

        $timezones = TimezoneHelper::getTimezonesWithUtcOffset();

        $this->viewParams['settings'] = $settings;
        $this->viewParams['timezones'] = $timezones;
        $this->viewParams['mailConnectionTested'] = $mailConnectionTested;
        $this->viewParams['mailSendTested'] = $mailSendTested;
        $this->viewParams['mailReceiveTested'] = $mailReceiveTested;
        $this->viewParams['mailConnectionTestDate'] = $mailConnectionTestDate;
        $this->viewParams['mailSendTestDate'] = $mailSendTestDate;
        $this->viewParams['mailReceiveTestDate'] = $mailReceiveTestDate;
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


        // DBに保存するもの
        $settings = $request->only([
            'maintenance_message',
            'notification_enabled',
            'notification_email',
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

        // DBに保存するもの（メンテナンスメッセージ、admin_url、force_sslなど）
        $settings = $request->only([
            'maintenance_message',
            'notification_enabled',
            'notification_email',
            'admin_url',
            'force_ssl',
        ]);
        
        // チェックボックスの場合、未チェック時は値が送信されないため、デフォルト値を設定
        $settings['force_ssl'] = $settings['force_ssl'] ?? 0;
        
        BaseSetting::setMany($settings);
        // .env に保存するもの（メンテナンスモードのON/OFFも含む）
        EnvHelper::update($envData);
        
        if ($mailSettingsChanged) {
            // メール設定変更時は全てのテストステータスをリセット
            BaseSetting::setValue('mail_connection_tested', 0);
            BaseSetting::setValue('mail_connection_test_date', null);
            BaseSetting::setValue('mail_send_tested', 0);
            BaseSetting::setValue('mail_send_test_date', null);
            BaseSetting::setValue('mail_receive_tested', 0);
            BaseSetting::setValue('mail_receive_test_date', null);
            BaseSetting::setValue('mail_verification_token', null);
            
            // セッションのテスト結果もクリア
            session()->forget('mail_test_results');
        } else {
            // メール設定が変更されていない場合、セッションのテスト結果をDBに保存
            $sessionTestResults = session('mail_test_results', []);
            
            if (!empty($sessionTestResults)) {
                foreach ($sessionTestResults as $key => $value) {
                    BaseSetting::setValue($key, $value);
                }
                
                // セッションからテスト結果をクリア
                session()->forget('mail_test_results');
            }
        }

        // 管理画面URLが変更された場合の特別な処理
        $currentAdminUrl = AdminHelper::getAdminUrl();
        $newAdminUrl = $settings['admin_url'];
        $forceSsl = (bool) $settings['force_ssl'];

        if ($newAdminUrl !== $currentAdminUrl) {
            // ユーザーをログアウト
            Auth::guard('admin')->logout();
            Session::flush();

            $newAdminLoginUrl = url($newAdminUrl . '/login');

            // SSL強制の場合、HTTPSにリダイレクト
            if ($forceSsl) {
                $newAdminLoginUrl = str_replace('http://', 'https://', $newAdminLoginUrl);
            }

            // 新しいURLのログイン画面にリダイレクト
            return redirect($newAdminLoginUrl)
                ->with('success', __('admin.settings.base.controller_messages.admin_url_changed'));
        }

        // 通常のリダイレクト
        $baseUrl = url($newAdminUrl . '/settings/base');

        // SSL強制の場合、HTTPSに変換
        if ($forceSsl) {
            $baseUrl = str_replace('http://', 'https://', $baseUrl);
        }

        return redirect($baseUrl)->with('success', __('admin.settings.base.controller_messages.settings_updated'));
    }

    /**
     * メールテストセッションをクリア
     */
    public function clearTestSession()
    {
        session()->forget('mail_test_results');
        
        Log::info('メールテストセッションクリア完了', [
            'session_before' => session('mail_test_results'),
            'session_after' => session('mail_test_results')
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'テストセッションがクリアされました。'
        ]);
    }

    /**
     * メールテストセッション状態をチェック
     */
    public function checkTestSession()
    {
        $sessionTestResults = session('mail_test_results', []);
        
        // セッションとDBの状態を統合
        $mailConnectionTested = (bool) ($sessionTestResults['mail_connection_tested'] ?? BaseSetting::getValue('mail_connection_tested', false));
        $mailConnectionTestDate = $sessionTestResults['mail_connection_test_date'] ?? BaseSetting::getValue('mail_connection_test_date', null);
        $mailSendTested = (bool) ($sessionTestResults['mail_send_tested'] ?? BaseSetting::getValue('mail_send_tested', false));
        $mailSendTestDate = $sessionTestResults['mail_send_test_date'] ?? BaseSetting::getValue('mail_send_test_date', null);
        $mailReceiveTested = (bool) ($sessionTestResults['mail_receive_tested'] ?? BaseSetting::getValue('mail_receive_tested', false));
        $mailReceiveTestDate = $sessionTestResults['mail_receive_test_date'] ?? BaseSetting::getValue('mail_receive_test_date', null);
        
        return response()->json([
            'success' => true,
            'data' => [
                'connection_tested' => $mailConnectionTested,
                'connection_test_date' => $mailConnectionTestDate,
                'send_tested' => $mailSendTested,
                'send_test_date' => $mailSendTestDate,
                'receive_tested' => $mailReceiveTested,
                'receive_test_date' => $mailReceiveTestDate,
                'all_tests_complete' => $mailConnectionTested && $mailSendTested && $mailReceiveTested
            ]
        ]);
    }


    /**
     * メールサーバー接続テスト
     */
    public function testConnection(MailTestRequest $request)
    {
        // MailTestTraitの統合メソッドを使用（メソッド名の競合を避けるため別名で呼び出し）
        return $this->performConnectionTest($request, 'admin');
    }


    /**
     * メール送信テスト
     */
    public function testMail(MailTestRequest $request)
    {
        return $this->performMailTest($request, 'admin');
    }


    /**
     * メール受信確認（認証リンクアクセス時）
     */
    public function verifyMail($token)
    {
        return $this->performMailVerification($token, 'admin');
    }

    /**
     * メール認証成功ページ（ウィンドウを閉じるメッセージを表示）
     */
    public function mailVerificationSuccess()
    {
        return view('components.mail-verification-success', [
            'isInstall' => false
        ]);
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
