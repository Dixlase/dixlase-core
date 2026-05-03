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

namespace App\Http\Controllers\Install;

use App\Http\Requests\Install\InstallMailRequest;
use App\Http\Requests\MailServerRequest;
use App\Traits\MailTestTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

/**
 * Install - Step 4: Mail server settings
 */
class InstallMailController extends BaseInstallController
{
    use MailTestTrait;

    /**
     * Display mail server settings screen
     */
    public function create()
    {
        $installData = session('install_data', []);
        $adminEmail = $installData['admin_email'] ?? '';

        // If mail_from_address is not set, set admin_email as default value
        if (empty($installData['mail_from_address']) && ! empty($adminEmail)) {
            $installData['mail_from_address'] = $adminEmail;
            session(['install_data' => $installData]);
        }

        // Get mail test results from session
        $testStatus = [
            'connection_tested' => (bool) ($installData['mail_connection_tested'] ?? false),
            'connection_test_date' => $installData['mail_connection_test_date'] ?? null,
            'send_tested' => (bool) ($installData['mail_send_tested'] ?? false),
            'send_test_date' => $installData['mail_send_test_date'] ?? null,
            'receive_tested' => (bool) ($installData['mail_receive_tested'] ?? false),
            'receive_test_date' => $installData['mail_receive_test_date'] ?? null,
        ];

        Log::channel('install')->info(__('http/controllers/install/install_mail_controller.session_data_mail_page_display'), [
            'install_data_keys' => array_keys($installData),
            'test_status' => $testStatus,
            'session_id' => session()->getId(),
        ]);

        return view('install.mail', array_merge(
            $this->getViewData(5),
            [
                'admin_email' => $adminEmail,
                'testStatus' => $testStatus,
                'mailers' => __('mail-server/config.mailers'),
                'encryptions' => __('mail-server/config.encryptions'),
            ]
        ));
    }

    /**
     * Save mail server settings
     */
    public function store(InstallMailRequest $request)
    {
        $validated = $request->validated();

        if ($request->filled('mail_password')) {
            $validated['mail_password'] = Crypt::encryptString($request->mail_password);
        }

        session(['install_data' => array_merge(session('install_data', []), $validated)]);

        return redirect()->route('install.confirm');
    }

    /**
     * Mail server connection test
     */
    public function testConnection(MailServerRequest $request)
    {
        $locale = $this->getCurrentLocale();
        app()->setLocale($locale);

        return $this->performConnectionTest($request, 'install');
    }

    /**
     * Mail sending test
     */
    public function testSend(MailServerRequest $request)
    {
        $locale = $this->getCurrentLocale();
        app()->setLocale($locale);

        return $this->performMailTest($request, 'install');
    }

    /**
     * Mail receipt confirmation
     */
    public function verify(Request $request, $token)
    {
        $locale = $this->getCurrentLocale();
        app()->setLocale($locale);

        return $this->performMailVerification($token, 'install');
    }

    /**
     * Reset mail test results
     */
    public function resetTests()
    {
        $installData = session('install_data', []);

        // Clear mail test related session data
        unset($installData['mail_connection_tested']);
        unset($installData['mail_connection_test_date']);
        unset($installData['mail_send_tested']);
        unset($installData['mail_send_test_date']);
        unset($installData['mail_receive_tested']);
        unset($installData['mail_receive_test_date']);

        // Force save session
        session(['install_data' => $installData]);
        session()->save();

        Log::channel('install')->info(__('http/controllers/install/install_mail_controller.mail_test_results_reset_completed'), [
            'reset_data' => array_keys($installData),
            'session_id' => session()->getId(),
        ]);

        return response()->json([
            'success' => true,
            'message' => __('http/controllers/install/install_mail_controller.reset_mail_test_results'),
            'debug' => [
                'session_keys' => array_keys($installData),
                'has_connection_tested' => isset($installData['mail_connection_tested']),
                'has_send_tested' => isset($installData['mail_send_tested']),
                'has_receive_tested' => isset($installData['mail_receive_tested']),
            ],
        ]);
    }
}
