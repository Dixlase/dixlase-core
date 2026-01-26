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

use App\Http\Requests\Install\InstallMailRequest;
use App\Http\Requests\MailServerRequest;
use App\Traits\MailTestTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

/**
 * インストール - ステップ4: メールサーバー設定
 */
class InstallMailController extends BaseInstallController
{
    use MailTestTrait;

    /**
     * メールサーバー設定画面を表示
     */
    public function create()
    {
        $installData = session('install_data', []);
        $adminEmail = $installData['admin_email'] ?? '';

        // mail_from_addressが未設定の場合、admin_emailをデフォルト値として設定
        if (empty($installData['mail_from_address']) && !empty($adminEmail)) {
            $installData['mail_from_address'] = $adminEmail;
            session(['install_data' => $installData]);
        }

        // メールテスト結果をセッションから取得
        $testStatus = [
            'connection_tested' => (bool) ($installData['mail_connection_tested'] ?? false),
            'connection_test_date' => $installData['mail_connection_test_date'] ?? null,
            'send_tested' => (bool) ($installData['mail_send_tested'] ?? false),
            'send_test_date' => $installData['mail_send_test_date'] ?? null,
            'receive_tested' => (bool) ($installData['mail_receive_tested'] ?? false),
            'receive_test_date' => $installData['mail_receive_test_date'] ?? null,
        ];

        Log::channel('install')->info('メールページ表示時のセッションデータ', [
            'install_data_keys' => array_keys($installData),
            'test_status' => $testStatus,
            'session_id' => session()->getId()
        ]);

        return view('install.mail', array_merge(
            $this->getViewData(4),
            [
                'admin_email' => $adminEmail,
                'testStatus' => $testStatus,
                'mailers' => __('mail.mailers'),
                'encryptions' => __('mail.encryptions')
            ]
        ));
    }

    /**
     * メールサーバー設定を保存
     */
    public function store(InstallMailRequest $request)
    {
        $validated = $request->validated();

        if ($request->filled('mail_password')) {
            $validated['mail_password'] = Crypt::encryptString($request->mail_password);
        }

        session(['install_data' => array_merge(session('install_data', []), $validated)]);

        return redirect()->route('install.security');
    }

    /**
     * メールサーバー接続テスト
     */
    public function testConnection(MailServerRequest $request)
    {
        $locale = $this->getCurrentLocale();
        app()->setLocale($locale);
        return $this->performConnectionTest($request, 'install');
    }

    /**
     * メール送信テスト
     */
    public function testSend(MailServerRequest $request)
    {
        $locale = $this->getCurrentLocale();
        app()->setLocale($locale);
        return $this->performMailTest($request, 'install');
    }

    /**
     * メール受信確認
     */
    public function verify(Request $request, $token)
    {
        $locale = $this->getCurrentLocale();
        app()->setLocale($locale);
        return $this->performMailVerification($token, 'install');
    }

    /**
     * メールテスト結果をリセット
     */
    public function resetTests()
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
        
        Log::channel('install')->info('メールテスト結果リセット完了', [
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
}
