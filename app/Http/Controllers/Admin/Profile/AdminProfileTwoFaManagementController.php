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
use App\Models\SecuritySetting;
use App\Services\MailServerValidatorService;
use App\Services\TwoFa\TwoFaPasskeyService;
use App\Services\TwoFa\TwoFaRecoveryCodeService;
use App\Services\TwoFa\TwoFaStatusService;
use App\Traits\ManagesTwoFaTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminProfileTwoFaManagementController extends AdminLoggedInController
{
    use ManagesTwoFaTrait;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Show the two-factor authentication management page.
     */
    public function index()
    {
        $member = Auth::guard('member')->user();

        // メールサーバー設定状態を渡す
        $this->viewParams['isMailServerTested'] = MailServerValidatorService::isMailServerTested();

        $this->loadTwoFactorSettings($member);

        return view('admin.profile.two-fa-management', $this->viewParams);
    }

    /**
     * Passkey登録用のWebAuthnチャレンジを生成
     */
    public function passkeyRegisterOptions(Request $request)
    {
        $member = Auth::guard('member')->user();

        return $this->generatePasskeyRegistrationOptions(
            $member,
            'admin/profile/common.passkey_register_options_error'
        );
    }

    /**
     * Passkeyを登録
     */
    public function passkeyRegister(Request $request)
    {
        $member = Auth::guard('member')->user();

        return $this->registerPasskeyForModel(
            $request,
            $member,
            'admin/profile/common.passkey_registered',
            'admin/profile/common.passkey_register_error'
        );
    }

    /**
     * Passkeyを削除
     */
    public function revokePasskey(Request $request, string $credentialId)
    {
        $member = Auth::guard('member')->user();

        return $this->revokePasskeyForModel(
            $request,
            $member,
            $credentialId,
            'admin/profile/common.passkey_deleted_all',
            'admin/profile/common.passkey_not_found',
            'admin/profile/common.passkey_deleted',
            'admin/profile/common.passkey_delete_error'
        );
    }

    /**
     * すべてのPasskeyを削除
     */
    public function revokeAllPasskeys(Request $request)
    {
        $member = Auth::guard('member')->user();
        $twoFaPasskeyService = new TwoFaPasskeyService();

        try {
            // すべてのPasskeyを取得して削除
            $credentials = $twoFaPasskeyService->getCredentials($member);
            $deletedCount = 0;

            foreach ($credentials as $credential) {
                if ($twoFaPasskeyService->revokeCredential($member, $credential->id)) {
                    $deletedCount++;
                }
            }

            if ($deletedCount === 0) {
                return response()->json([
                    'success' => false,
                    'message' => __('admin/profile/common.no_passkeys_to_delete'),
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => __('admin/profile/common.all_passkeys_deleted', ['count' => $deletedCount]),
            ]);
        } catch (\Exception $e) {
            \Log::error('[Passkey] 一括削除エラー', [
                'member_id' => $member->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('admin/profile/common.passkey_delete_all_error'),
            ], 500);
        }
    }

    /**
     * 回復コードを生成
     */
    public function generateRecoveryCodes(Request $request)
    {
        $member = Auth::guard('member')->user();
        $recoveryCodeService = app(\App\Services\TwoFa\TwoFaRecoveryCodeService::class);

        // 既に回復コードが存在する場合は再生成として扱う
        if ($recoveryCodeService->hasRecoveryCodes($member)) {
            return $this->regenerateRecoveryCodes($request);
        }

        return $this->generateRecoveryCodesForModel(
            $member,
            'admin/profile/common.recovery_codes_generated',
            'admin/profile/common.recovery_codes_generation_error'
        );
    }

    /**
     * 回復コードを再生成
     */
    public function regenerateRecoveryCodes(Request $request)
    {
        $member = Auth::guard('member')->user();

        return $this->regenerateRecoveryCodesForModel(
            $member,
            'admin/profile/common.recovery_codes_regenerated',
            'admin/profile/common.recovery_codes_regenerate_too_soon',
            'admin/profile/common.recovery_codes_generation_error'
        );
    }

    /**
     * 回復コードセッションをクリア
     */
    public function clearRecoveryCodesSession(Request $request)
    {
        return $this->clearRecoveryCodesSessionData();
    }

    /**
     * Load two-factor authentication settings.
     */
    private function loadTwoFactorSettings($member)
    {
        // TwoFaStatusServiceを使用して設定を取得
        $twoFaStatusService = new TwoFaStatusService();
        $twoFaForceMode = $twoFaStatusService->getGlobalTwoFaMode();
        $twoFaMode = $member->two_fa_mode;

        // グローバル設定で有効な二段階認証方法を取得
        $twoFaPasskeyMode = $twoFaStatusService->getGlobalPasskeyMode();
        $twoFaPasskeyEnabled = $twoFaPasskeyMode === 1;

        $this->viewParams['currentPasskeyEnabled'] = $twoFaPasskeyEnabled;

        // メール認証は常に有効、Passkeyは設定に応じて
        $twoFaEnabledMethods = [
            TwoFaMethod::EMAIL->value => TwoFaMethod::EMAIL->translationKey(),
        ];
        if ($twoFaPasskeyEnabled) {
            $twoFaEnabledMethods[TwoFaMethod::PASSKEY->value] = TwoFaMethod::PASSKEY->translationKey();
        }

        // 実際の二段階認証の有効/無効状態を判定
        // 全体設定で無効、または全体設定がプロフィールに従う場合はプロフィール設定を確認
        $actualTwoFaMode = $twoFaForceMode === AuthenticationMode::UseProfileSetting->value
            ? (is_int($twoFaMode) ? $twoFaMode : $twoFaMode->value)
            : $twoFaForceMode;
        $isTwoFaActuallyDisabled = $actualTwoFaMode === AuthenticationMode::Disabled->value;

        $this->viewParams['twoFaForceMode'] = $twoFaForceMode;
        $this->viewParams['twoFaMode'] = $twoFaMode;
        $this->viewParams['isTwoFaActuallyDisabled'] = $isTwoFaActuallyDisabled;
        $this->viewParams['twoFaEnabledMethods'] = $twoFaEnabledMethods;
        $this->viewParams['twoFaPasskeyMode'] = $twoFaPasskeyMode;
        $this->viewParams['twoFaPasskeyEnabled'] = $twoFaPasskeyEnabled;

        // Passkeyデバイス一覧を取得
        $twoFaPasskeyService = new TwoFaPasskeyService();
        $this->viewParams['twoFaPasskeyDevices'] = $twoFaPasskeyService->getDevices($member);

        // Passkeyデバイス最大登録数を取得
        $twoFaPasskeyMaxDevices = (int) SecuritySetting::getValue('two_fa_passkey_max_devices', '5');
        $currentDeviceCount = count($this->viewParams['twoFaPasskeyDevices']);
        $canRegisterMoreDevices = $currentDeviceCount < $twoFaPasskeyMaxDevices;

        $this->viewParams['twoFaPasskeyMaxDevices'] = $twoFaPasskeyMaxDevices;
        $this->viewParams['twoFaPasskeyCurrentCount'] = $currentDeviceCount;
        $this->viewParams['canRegisterMorePasskeys'] = $canRegisterMoreDevices;

        // 回復コード情報を取得
        $twoFaRecoveryCodeService = new TwoFaRecoveryCodeService();
        $this->viewParams['twoFaRecoveryCodesCount'] = $twoFaRecoveryCodeService->getRemainingCount($member);
        $this->viewParams['twoFaHasRecoveryCodes'] = $twoFaRecoveryCodeService->hasRecoveryCodes($member);
    }
}
