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

namespace App\Http\Controllers\Admin\Profile;

use App\Enums\AuthenticationMode;
use App\Enums\TwoFaMethod;
use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Http\Requests\Admin\Profile\ProfileTwoFaUpdateRequest;
use App\Models\SecuritySetting;
use App\Services\MailServerValidatorService;
use App\Services\TwoFa\TwoFaPasskeyService;
use App\Services\TwoFa\TwoFaRecoveryCodeService;
use App\Services\TwoFa\TwoFaStatusService;
use Illuminate\Support\Facades\Auth;

class AdminProfileTwoFaController extends AdminLoggedInController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Show the two-factor authentication settings page.
     */
    public function index()
    {
        $member = Auth::guard('member')->user();

        // メールサーバー設定状態を渡す
        $this->viewParams['isMailServerTested'] = MailServerValidatorService::isMailServerTested();

        $this->loadTwoFactorSettings($member);

        // 2FA有効化可能かをチェック（プロフィール画面ではメールサーバーテスト済みかチェック）
        $this->viewParams['canEnableTwoFa'] = $this->viewParams['isMailServerTested'];
        $this->viewParams['twoFaEnableBlockReasons'] = $this->viewParams['canEnableTwoFa'] ? [] : ['no_mail_server'];

        return view('admin.profile.two-fa', $this->viewParams);
    }

    /**
     * Update two-factor authentication settings.
     */
    public function update(ProfileTwoFaUpdateRequest $request)
    {
        $member = Auth::guard('member')->user();
        $validated = $request->validated();

        // 二段階認証モードの変更を検出するため、保存前の値を取得（整数値として）
        $twoFaOldMode = is_int($member->two_fa_mode) ? $member->two_fa_mode : $member->two_fa_mode->value;
        $beforeMode = $member->two_fa_mode;

        // two_fa_mode は全体設定が UseProfileSetting のときだけ上書き
        $twoFaForceMode = (int) SecuritySetting::getValue('two_fa_mode', AuthenticationMode::UseProfileSetting->value);
        if ($twoFaForceMode === AuthenticationMode::UseProfileSetting->value && array_key_exists('two_fa_mode', $validated)) {
            $member->two_fa_mode = (int) $validated['two_fa_mode'];
        }

        $member->save();

        // 保存後の2FA状態を取得（保存後の値を使用）
        $member->refresh();

        // TwoFaStatusServiceを使用して判定
        $twoFaStatusService = new TwoFaStatusService();
        $twoFaRecoveryCodeService = new TwoFaRecoveryCodeService();
        $twoFaPasskeyService = new TwoFaPasskeyService();

        \App\Facades\Audit::log([
            'category' => 'account',
            'action' => 'profile.two_fa_changed',
            'actor' => auth()->user(),
            'target' => auth()->user(),
            'severity' => 'notice',
            'context' => [
                'before' => $beforeMode,
                'after' => auth()->user()->fresh()->two_fa_mode,
            ],
        ]);

        $redirect = redirect()->route('admin.profile.two-fa')->with('success', __('admin/profile/common.two_fa_updated'));

        // 回復コードが存在しない場合は自動生成
        if ($twoFaStatusService->shouldGenerateRecoveryCodes($member, $twoFaRecoveryCodeService)) {
            try {
                $codes = $twoFaRecoveryCodeService->generate($member);
                $redirect->with('auto_generated_recovery_codes', $codes);
            } catch (\Exception $e) {
                \Log::error('[Profile] Failed to auto-generate recovery codes', [
                    'member_id' => $member->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // パスキーが有効かつデバイス未登録の場合、促進モーダルを表示
        if ($twoFaStatusService->shouldPromptPasskeyRegistration($member, $twoFaPasskeyService)) {
            $redirect->with('prompt_passkey_registration', true);
        }

        return $redirect;
    }

    /**
     * Load two-factor authentication settings.
     */
    private function loadTwoFactorSettings($member)
    {
        // 二段階認証設定の追加
        $twoFaForceMode = (int) SecuritySetting::getValue(
            'two_fa_mode',
            AuthenticationMode::UseProfileSetting->value
        );
        $twoFaMode = $member->two_fa_mode;

        // グローバル設定で有効な二段階認証方法を取得
        // 0=無効, 1=有効（デフォルト: 有効）
        $twoFaPasskeyMode = (int) SecuritySetting::getValue('two_fa_passkey_mode', '1');
        $twoFaPasskeyEnabled = $twoFaPasskeyMode === 1;

        $this->viewParams['currentPasskeyEnabled'] = $twoFaPasskeyEnabled;

        // メール認証は常に有効、Passkeyは設定に応じて
        $twoFaEnabledMethods = [
            TwoFaMethod::EMAIL->value => TwoFaMethod::EMAIL->translationKey(),
        ];
        if ($twoFaPasskeyEnabled) {
            $twoFaEnabledMethods[TwoFaMethod::PASSKEY->value] = TwoFaMethod::PASSKEY->translationKey();
        }

        $this->viewParams['twoFaForceMode'] = $twoFaForceMode;
        $this->viewParams['twoFaMode'] = $twoFaMode;
        $this->viewParams['twoFaEnabledMethods'] = $twoFaEnabledMethods;
        $this->viewParams['twoFaPasskeyMode'] = $twoFaPasskeyMode;
        $this->viewParams['twoFaPasskeyEnabled'] = $twoFaPasskeyEnabled;
        $this->viewParams['twoFaPasskeyGloballyEnabled'] = in_array(TwoFaMethod::PASSKEY->value, array_keys($twoFaEnabledMethods));
        $this->viewParams['isTwoFaEditable'] = $twoFaForceMode === AuthenticationMode::UseProfileSetting->value;

        // Passkeyデバイス一覧を取得
        $twoFaPasskeyService = new TwoFaPasskeyService();
        $this->viewParams['twoFaPasskeyDevices'] = $twoFaPasskeyService->getDevices($member);

        // 回復コード情報を取得
        $twoFaRecoveryCodeService = new TwoFaRecoveryCodeService();
        $this->viewParams['twoFaRecoveryCodesCount'] = $twoFaRecoveryCodeService->getRemainingCount($member);
        $this->viewParams['twoFaHasRecoveryCodes'] = $twoFaRecoveryCodeService->hasRecoveryCodes($member);
    }
}
