<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE.commercial, or contact office@exc-d.com).
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the AGPL terms below.
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

namespace App\Http\Controllers\Admin\Settings\Base;

use App\Contracts\Repositories\SiteSettingRepositoryInterface;
use App\Helpers\AdminModeHelper;
use App\Helpers\ConfigHelper;
use App\Helpers\EnvHelper;
use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Http\Requests\Admin\Settings\Base\AdminBaseMailUpdateRequest;
use App\Http\Requests\MailServerRequest;
use App\Traits\MailTestTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminBaseMailController extends AdminLoggedInController
{
    use MailTestTrait;

    protected const SETTING_KEYS = ['mail_mailer', 'mail_host', 'mail_port', 'mail_username', 'mail_password', 'mail_encryption', 'mail_from_address', 'system_admin_email'];

    protected const SENSITIVE_KEYS = ['mail_password'];

    protected SiteSettingRepositoryInterface $baseSettingRepository;

    public function __construct(SiteSettingRepositoryInterface $baseSettingRepository)
    {
        parent::__construct();
        $this->baseSettingRepository = $baseSettingRepository;
    }

    /**
     * Mail settings page
     */
    public function index(Request $request)
    {
        // Process request to update mail reception test status
        if ($request->isMethod('post') && $request->input('action') === 'update_receive_test_status') {
            $mailTestResults = session('mail_test_results', []);
            $mailTestResults['mail_receive_tested'] = 1;
            $mailTestResults['mail_receive_test_date'] = now()->format('Y-m-d H:i:s');
            session(['mail_test_results' => $mailTestResults]);

            return response()->json(['success' => true]);
        }

        // Clear mail test session every time the mail settings page is opened
        session()->forget('mail_test_results');

        $settings = [
            'mail_mailer' => ConfigHelper::getMailMailer(),
            'mail_host' => ConfigHelper::getMailHost(),
            'mail_port' => ConfigHelper::getMailPort(),
            'mail_username' => ConfigHelper::getMailUsername(),
            'mail_password' => ConfigHelper::getMailPassword(),
            'mail_encryption' => ConfigHelper::getMailEncryption(),
            'mail_from_address' => ConfigHelper::getMailFromAddress(),
            'system_admin_email' => ConfigHelper::getNotificationEmail(),
        ];

        // Get mail test status
        $sessionTestResults = session('mail_test_results', []);

        $mailConnectionTested = (bool) ($sessionTestResults['mail_connection_tested'] ?? $this->baseSettingRepository->get('mail_connection_tested', false));
        $mailSendTested = (bool) ($sessionTestResults['mail_send_tested'] ?? $this->baseSettingRepository->get('mail_send_tested', false));
        $mailReceiveTested = (bool) ($sessionTestResults['mail_receive_tested'] ?? $this->baseSettingRepository->get('mail_receive_tested', false));

        $mailConnectionTestDate = $sessionTestResults['mail_connection_test_date'] ?? $this->baseSettingRepository->get('mail_connection_test_date', '');
        $mailSendTestDate = $sessionTestResults['mail_send_test_date'] ?? $this->baseSettingRepository->get('mail_send_test_date', '');
        $mailReceiveTestDate = $sessionTestResults['mail_receive_test_date'] ?? $this->baseSettingRepository->get('mail_receive_test_date', '');

        $this->viewParams['settings'] = $settings;
        $this->viewParams['mailConnectionTested'] = $mailConnectionTested;
        $this->viewParams['mailSendTested'] = $mailSendTested;
        $this->viewParams['mailReceiveTested'] = $mailReceiveTested;
        $this->viewParams['mailConnectionTestDate'] = $mailConnectionTestDate;
        $this->viewParams['mailSendTestDate'] = $mailSendTestDate;
        $this->viewParams['mailReceiveTestDate'] = $mailReceiveTestDate;
        $this->viewParams['mailers'] = __('mail-server/config.mailers');
        $this->viewParams['encryptions'] = __('mail-server/config.encryptions');
        $this->viewParams['mailSettingsConfig'] = [
            'routes' => [
                'checkTestSession' => route('admin.settings.base.mail.check-test-session'),
                'clearTestSession' => route('admin.settings.base.mail.clear-test-session'),
            ],
            'translations' => [
                'mailReceiveTestCompleted' => __('admin/settings/base/mail.mail_receive_test_completed'),
                'mailTestIncomplete' => __('admin/settings/base/mail.mail_test_incomplete'),
            ],
        ];
        $this->viewParams['modeData'] = AdminModeHelper::getViewModeData('settings.base.mail');

        return view('admin.settings.base.mail', $this->viewParams);
    }

    /**
     * Update mail settings
     */
    public function update(AdminBaseMailUpdateRequest $request)
    {
        $actor = new \App\Actors\MemberActor(\App\Helpers\AdminHelper::getMember());

        \App\Actions\Settings\UpdateSettingsAction::make(
            repository: $this->baseSettingRepository,
            settingsPage: 'base.mail',
            settingKeys: static::SETTING_KEYS,
            writeCallback: function ($repo, $data) {
                // Data for .env
                $envData = [
                    'mail_mailer' => $data['mail_mailer'],
                    'mail_host' => $data['mail_host'] ?? '',
                    'mail_port' => $data['mail_port'] ?? '',
                    'mail_username' => $data['mail_username'] ?? null,
                    'mail_password' => $data['mail_password'] ?? null,
                    'mail_encryption' => $data['mail_encryption'] ?? null,
                    'mail_from_address' => $data['mail_from_address'] ?? '',
                ];

                // Convert empty strings to null
                foreach (['mail_username', 'mail_password', 'mail_encryption'] as $field) {
                    if (isset($envData[$field]) && $envData[$field] === '') {
                        $envData[$field] = null;
                    }
                }

                // Check if mail settings have changed
                $mailSettingsChanged = false;
                foreach (array_keys($envData) as $key) {
                    $currentValue = env(strtoupper($key));
                    $currentValue = $currentValue === null ? '' : (string) $currentValue;
                    $newValue = $envData[$key] === null ? '' : (string) $envData[$key];
                    if ($currentValue !== $newValue) {
                        $mailSettingsChanged = true;
                        break;
                    }
                }

                // Save to DB + .env
                $repo->setMultiple([
                    'mail_mailer' => $data['mail_mailer'],
                    'mail_host' => $data['mail_host'] ?? '',
                    'mail_port' => (string) ($data['mail_port'] ?? ''),
                    'mail_username' => $data['mail_username'] ?? '',
                    'mail_password' => $data['mail_password'] ?? '',
                    'mail_encryption' => $data['mail_encryption'] ?? '',
                    'mail_from_address' => $data['mail_from_address'] ?? '',
                    'system_admin_email' => $data['system_admin_email'] ?? '',
                ]);
                EnvHelper::update($envData);

                if ($mailSettingsChanged) {
                    $repo->set('mail_connection_tested', 0);
                    $repo->set('mail_connection_test_date', null);
                    $repo->set('mail_send_tested', 0);
                    $repo->set('mail_send_test_date', null);
                    $repo->set('mail_receive_tested', 0);
                    $repo->set('mail_receive_test_date', null);
                    $repo->set('mail_verification_token', null);
                    session()->forget('mail_test_results');
                } else {
                    $sessionTestResults = session('mail_test_results', []);
                    if (! empty($sessionTestResults)) {
                        foreach ($sessionTestResults as $key => $value) {
                            $repo->set($key, $value);
                        }
                        session()->forget('mail_test_results');
                    }
                }
            },
            sensitiveKeys: static::SENSITIVE_KEYS,
        )->execute($actor, $request->validated());

        return redirect()->route('admin.settings.base.mail')
            ->with('success', __('admin/settings/base/mail.settings_updated'));
    }

    /**
     * Clear mail test session
     */
    public function clearTestSession()
    {
        session()->forget('mail_test_results');

        $this->baseSettingRepository->set('mail_connection_tested', 0);
        $this->baseSettingRepository->set('mail_connection_test_date', null);
        $this->baseSettingRepository->set('mail_send_tested', 0);
        $this->baseSettingRepository->set('mail_send_test_date', null);
        $this->baseSettingRepository->set('mail_receive_tested', 0);
        $this->baseSettingRepository->set('mail_receive_test_date', null);
        $this->baseSettingRepository->set('mail_verification_token', null);

        Log::info('Mail test session and DB both cleared');

        return response()->json([
            'success' => true,
            'message' => __('admin/settings/base/mail.test_session_cleared'),
        ]);
    }

    /**
     * Check mail test session status
     */
    public function checkTestSession()
    {
        $sessionTestResults = session('mail_test_results', []);

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
                'all_tests_complete' => $mailConnectionTested && $mailSendTested && $mailReceiveTested,
            ],
        ]);
    }

    /**
     * Mail server connection test
     */
    public function testConnection(MailServerRequest $request)
    {
        return $this->performConnectionTest($request, 'admin');
    }

    /**
     * Mail sending test
     */
    public function testMail(MailServerRequest $request)
    {
        return $this->performMailTest($request, 'admin');
    }

    /**
     * Mail reception confirmation (when authentication link is accessed)
     */
    public function verifyMail($token)
    {
        return $this->performMailVerification($token, 'admin');
    }

    /**
     * Mail authentication success page
     */
    public function mailVerificationSuccess()
    {
        return view('components.mail-server.verification-success', [
            'isInstall' => false,
        ]);
    }
}
