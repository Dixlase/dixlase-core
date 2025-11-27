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
use Illuminate\Support\Facades\Log;
use App\Contracts\Repositories\BaseSettingRepositoryInterface;


class AdminBaseSettingsController extends AdminLoggedInController
{
    use MailTestTrait;

    /**
     * 基本設定リポジトリ
     */
    protected BaseSettingRepositoryInterface $baseSettingRepository;

    /**
     * コンストラクタ
     */
    public function __construct(BaseSettingRepositoryInterface $baseSettingRepository)
    {
        parent::__construct();
        $this->baseSettingRepository = $baseSettingRepository;
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
            // サイト名
            'app_name' => ConfigHelper::getAppName(),

            // サイトの説明
            'site_description' => $this->baseSettingRepository->get('site_description', ''),
            'site_keywords' => $this->baseSettingRepository->get('site_keywords', ''),
            
            // OGP設定
            'default_ogp_image_id' => $this->baseSettingRepository->get('default_ogp_image_id'),
            'twitter_card_type' => $this->baseSettingRepository->get('twitter_card_type', 'summary_large_image'),

            // 基本設定では個人設定を無視してシステム設定を取得
            'locale' => $this->getSystemLocale(),
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
            'system_admin_email' => ConfigHelper::getNotificationEmail(),
            
            // Database-only settings (no .env equivalent)
            'admin_url' => $this->baseSettingRepository->get('admin_url', config('admin.admin_url')),
            'force_ssl' => (bool) $this->baseSettingRepository->get('force_ssl', false),
            
            // 多言語設定
            'multilingual_enabled' => (bool) $this->baseSettingRepository->get('multilingual_enabled', false),
            'enabled_locales' => $this->getEnabledLocales(),

        ];

        // メールテスト状態を取得（DB優先、セッションは一時的な状態のみ）
        $sessionTestResults = session('mail_test_results', []);
        
        $mailConnectionTested = (bool) ($sessionTestResults['mail_connection_tested'] ?? $this->baseSettingRepository->get('mail_connection_tested', false));
        $mailSendTested = (bool) ($sessionTestResults['mail_send_tested'] ?? $this->baseSettingRepository->get('mail_send_tested', false));
        $mailReceiveTested = (bool) ($sessionTestResults['mail_receive_tested'] ?? $this->baseSettingRepository->get('mail_receive_tested', false));
        
        $mailConnectionTestDate = $sessionTestResults['mail_connection_test_date'] ?? $this->baseSettingRepository->get('mail_connection_test_date', '');
        $mailSendTestDate = $sessionTestResults['mail_send_test_date'] ?? $this->baseSettingRepository->get('mail_send_test_date', '');
        $mailReceiveTestDate = $sessionTestResults['mail_receive_test_date'] ?? $this->baseSettingRepository->get('mail_receive_test_date', '');

        $timezones = TimezoneHelper::getTimezonesWithUtcOffset();
        
        // OGP画像のメディア情報を取得
        $defaultOgpImage = null;
        if ($settings['default_ogp_image_id']) {
            $defaultOgpImage = \App\Models\Media::find($settings['default_ogp_image_id']);
        }

        // デバッグ情報: フォーム表示時の言語設定
        if (config('app.debug')) {
            Log::info('基本設定表示デバッグ: フォーム表示時の言語設定', [
                'settings_locale' => $settings['locale'],
                'confighelper_result' => ConfigHelper::getAppLocale(),
                'old_locale' => old('locale'),
                'old_locale_exists' => session()->hasOldInput('locale'),
                'final_value_for_form' => old('locale', $settings['locale']),
                'session_has_errors' => session()->has('errors'),
                'session_old_input_keys' => array_keys(session()->getOldInput()),
            ]);
        }

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
        // 多言語設定用：利用可能な全言語（キーと名前）
        $this->viewParams['availableLocales'] = config('admin.locale.available', []);
        $this->viewParams['mailers'] = __('mail.mailers');
        $this->viewParams['encryptions'] = __('mail.encryptions');
        $this->viewParams['defaultOgpImage'] = $defaultOgpImage;

        return view(
            'admin::settings.base.index',
            $this->viewParams
        );
    }

    /**
     * システムの基本言語設定を取得（個人設定を無視）
     * 
     * @return string
     */
    private function getSystemLocale(): string
    {
        // 1. .env ファイルから直接取得
        $envLocale = env('APP_LOCALE');
        if ($envLocale !== null) {
            return $envLocale;
        }

        // 2. データベースから取得
        $dbLocale = $this->baseSettingRepository->get('locale');
        if ($dbLocale !== null) {
            return $dbLocale;
        }

        // 3. デフォルト値
        return 'en';
    }

    /**
     * 設定の更新
     */
    public function update(AdminBaseSettingsRequest $request)
    {


        // 全ての設定を取得
        $allSettings = $request->only([
            'app_name',
            'site_description',
            'site_keywords',
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
            'system_admin_email',
            'admin_url',
            'force_ssl',
            'default_ogp_image_id',
            'twitter_card_type',
            'multilingual_enabled',
            'enabled_locales',

        ]);
        
        // ラジオボタンとチェックボックスの値を正しく設定
        // maintenance_mode はラジオボタンなので値をそのまま使用
        $allSettings['maintenance_mode'] = (int) ($allSettings['maintenance_mode'] ?? 0);
        // force_ssl はチェックボックスなので has() で判定
        $allSettings['force_ssl'] = $request->has('force_ssl') ? 1 : 0;
        // multilingual_enabled はラジオボタンなので値をそのまま使用
        $allSettings['multilingual_enabled'] = (int) ($allSettings['multilingual_enabled'] ?? 0);
        // enabled_locales は配列なのでそのまま使用（バリデーション済み）
        $allSettings['enabled_locales'] = $allSettings['enabled_locales'] ?? ['en'];


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
            'site_description' => $allSettings['site_description'] ?? '',
            'site_keywords' => $allSettings['site_keywords'] ?? '',
            'locale' => $allSettings['locale'],
            'timezone' => $allSettings['timezone'],
            'mail_mailer' => $allSettings['mail_mailer'],
            'mail_host' => $allSettings['mail_host'],
            'mail_port' => (string) $allSettings['mail_port'],
            'mail_username' => $allSettings['mail_username'],
            'mail_password' => $allSettings['mail_password'],
            'mail_encryption' => $allSettings['mail_encryption'],
            'mail_from_address' => $allSettings['mail_from_address'],
            'maintenance_mode' => ($allSettings['maintenance_mode'] ?? false) ? '1' : '0',
            'maintenance_message' => $allSettings['maintenance_message'] ?? '',
            'system_admin_email' => $allSettings['system_admin_email'] ?? '',
            'admin_url' => $allSettings['admin_url'] ?? '',
            'force_ssl' => ($allSettings['force_ssl'] ?? false) ? '1' : '0',
            'default_ogp_image_id' => $allSettings['default_ogp_image_id'] ?? null,
            'twitter_card_type' => $allSettings['twitter_card_type'] ?? 'summary_large_image',
            'multilingual_enabled' => ($allSettings['multilingual_enabled'] ?? false) ? '1' : '0',
            'enabled_locales' => json_encode($allSettings['enabled_locales'] ?? ['en']),

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
        $this->baseSettingRepository->setMultiple($dbSettings);
        
        // .envファイルに設定を保存
        EnvHelper::update($envData);
        
        if ($mailSettingsChanged) {
            // メール設定変更時は全てのテストステータスをリセット
            $this->baseSettingRepository->set('mail_connection_tested', 0);
            $this->baseSettingRepository->set('mail_connection_test_date', null);
            $this->baseSettingRepository->set('mail_send_tested', 0);
            $this->baseSettingRepository->set('mail_send_test_date', null);
            $this->baseSettingRepository->set('mail_receive_tested', 0);
            $this->baseSettingRepository->set('mail_receive_test_date', null);
            $this->baseSettingRepository->set('mail_verification_token', null);
            
            // セッションのテスト結果もクリア
            session()->forget('mail_test_results');
        } else {
            // メール設定が変更されていない場合、セッションのテスト結果をDBに保存
            $sessionTestResults = session('mail_test_results', []);
            
            if (!empty($sessionTestResults)) {
                foreach ($sessionTestResults as $key => $value) {
                    $this->baseSettingRepository->set($key, $value);
                }
                
                // セッションからテスト結果をクリア
                session()->forget('mail_test_results');
            }
        }

        // 管理画面URLが変更された場合の特別な処理
        $currentAdminUrl = AdminHelper::getAdminUrl();
        
        // デバッグ情報（開発時のみ）
        if (config('app.debug')) {
            Log::info('基本設定保存: admin_url=' . ($allSettings['admin_url'] ?? 'NOT_SET'));
        }
        
        $newAdminUrl = $allSettings['admin_url'] ?? $currentAdminUrl;
        $forceSsl = (bool) ($allSettings['force_ssl'] ?? false);

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

        // セッションの古い入力値を完全にクリア（フォーム表示の正常化のため）
        session()->forget('_old_input');
        session()->forget('_flash');
        session()->reflash();
        
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
        $this->baseSettingRepository->set('mail_connection_tested', 0);
        $this->baseSettingRepository->set('mail_connection_test_date', null);
        $this->baseSettingRepository->set('mail_send_tested', 0);
        $this->baseSettingRepository->set('mail_send_test_date', null);
        $this->baseSettingRepository->set('mail_receive_tested', 0);
        $this->baseSettingRepository->set('mail_receive_test_date', null);
        $this->baseSettingRepository->set('mail_verification_token', null);
        
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
        $mailConnectionTested = (bool) ($sessionTestResults['mail_connection_tested'] ?? $this->baseSettingRepository->get('mail_connection_tested', false));
        $mailConnectionTestDate = $sessionTestResults['mail_connection_test_date'] ?? $this->baseSettingRepository->get('mail_connection_test_date', null);
        $mailSendTested = (bool) ($sessionTestResults['mail_send_tested'] ?? $this->baseSettingRepository->get('mail_send_tested', false));
        $mailSendTestDate = $sessionTestResults['mail_send_test_date'] ?? $this->baseSettingRepository->get('mail_send_test_date', null);
        $mailReceiveTested = (bool) ($sessionTestResults['mail_receive_tested'] ?? $this->baseSettingRepository->get('mail_receive_tested', false));
        $mailReceiveTestDate = $sessionTestResults['mail_receive_test_date'] ?? $this->baseSettingRepository->get('mail_receive_test_date', null);
        
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

    /**
     * 有効な言語一覧を取得
     * 
     * DBから取得した値が文字列（JSON）か配列かを判定して適切に処理
     * 
     * @return array
     */
    private function getEnabledLocales(): array
    {
        $value = $this->baseSettingRepository->get('enabled_locales', '["en"]');
        
        // 既に配列の場合はそのまま返す
        if (is_array($value)) {
            return $value ?: ['en'];
        }
        
        // 文字列の場合はJSONデコード
        $decoded = json_decode($value, true);
        
        return is_array($decoded) ? $decoded : ['en'];
    }
}
