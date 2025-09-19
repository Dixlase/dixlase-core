<?php

/**
 * This file is part of Dixlase.
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
use App\Helpers\ConfigHelper;
use App\Helpers\EnvHelper;
use App\Helpers\TimezoneHelper;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Http\Requests\Admin\Settings\AdminBaseSettingsRequest;
use App\Http\Requests\MailServerRequest;
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
            // App settings - config(.env) -> database -> default
            'app_name' => ConfigHelper::getAppName(),
            'locale' => ConfigHelper::getAppLocale(),
            'timezone' => ConfigHelper::getAppTimezone(),

            // Mail settings - config(.env) -> database -> default
            'mail_mailer' => ConfigHelper::getMailMailer(),
            'mail_host' => ConfigHelper::getMailHost(),
            'mail_port' => ConfigHelper::getMailPort(),
            'mail_username' => ConfigHelper::getMailUsername(),
            'mail_password' => ConfigHelper::getMailPassword(),
            'mail_encryption' => ConfigHelper::getMailEncryption(),
            'mail_from_address' => ConfigHelper::getMailFromAddress(),


            // Other settings - config(.env) -> database -> default
            'maintenance_mode' => ConfigHelper::getMaintenanceMode(),
            'maintenance_message' => ConfigHelper::getMaintenanceMessage(),
            'notification_enabled' => ConfigHelper::getNotificationEnabled(),
            'system_admin_email' => ConfigHelper::getNotificationEmail(),
            
            // Database-only settings (no .env equivalent)
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


        // 全ての設定を取得
        $allSettings = $request->only([
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
            'maintenance_message',
            'notification_enabled',
            'notification_email',
        ]);

        // .envに保存するもの
        $envData = [
            'app_name' => $allSettings['app_name'],
            'locale' => $allSettings['locale'],
            'timezone' => $allSettings['timezone'],
            'mail_mailer' => $allSettings['mail_mailer'],
            'mail_host' => $allSettings['mail_host'],
            'mail_port' => $allSettings['mail_port'],
            'mail_username' => $allSettings['mail_username'],
            'mail_password' => $allSettings['mail_password'],
            'mail_encryption' => $allSettings['mail_encryption'],
            'mail_from_address' => $allSettings['mail_from_address'],
            'maintenance_mode' => $allSettings['maintenance_mode'] ? 'true' : 'false',
        ];

        // DBにも保存（フォールバック用）
        $dbSettings = [
            'app_name' => $allSettings['app_name'],
            'locale' => $allSettings['locale'],
            'timezone' => $allSettings['timezone'],
            'mail_mailer' => $allSettings['mail_mailer'],
            'mail_host' => $allSettings['mail_host'],
            'mail_port' => (string) $allSettings['mail_port'],
            'mail_username' => $allSettings['mail_username'],
            'mail_password' => $allSettings['mail_password'],
            'mail_encryption' => $allSettings['mail_encryption'],
            'mail_from_address' => $allSettings['mail_from_address'],
            'maintenance_mode' => $allSettings['maintenance_mode'] ? '1' : '0',
            'maintenance_message' => $allSettings['maintenance_message'],
            'notification_enabled' => $allSettings['notification_enabled'] ? '1' : '0',
            'system_admin_email' => $allSettings['system_admin_email'],
        ];

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

        // DBに全ての設定を保存（フォールバック用）
        BaseSetting::setMany($dbSettings);
        
        // .envファイルに設定を保存
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
        // セッションのテスト結果をクリア
        session()->forget('mail_test_results');
        
        // データベースのテスト結果も即座にリセット
        BaseSetting::setValue('mail_connection_tested', 0);
        BaseSetting::setValue('mail_connection_test_date', null);
        BaseSetting::setValue('mail_send_tested', 0);
        BaseSetting::setValue('mail_send_test_date', null);
        BaseSetting::setValue('mail_receive_tested', 0);
        BaseSetting::setValue('mail_receive_test_date', null);
        BaseSetting::setValue('mail_verification_token', null);
        
        Log::info('メールテストセッション・DB両方クリア完了', [
            'session_cleared' => true,
            'db_reset' => true
        ]);
        
        return response()->json([
            'success' => true,
            'message' => __('admin.settings.base.controller_messages.test_session_cleared')
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
    public function testConnection(MailServerRequest $request)
    {
        // MailTestTraitの統合メソッドを使用（メソッド名の競合を避けるため別名で呼び出し）
        return $this->performConnectionTest($request, 'admin');
    }


    /**
     * メール送信テスト
     */
    public function testMail(MailServerRequest $request)
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
