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

namespace App\Http\Controllers\Admin\Profile;

use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Enums\AuthenticationMode;
use App\Enums\TwoFaMethod;
use App\Models\SecuritySetting;
use App\Services\MailServerValidatorService;
use App\Services\TwoFa\TwoFaPasskeyService;
use App\Services\TwoFa\TwoFaRecoveryCodeService;
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
            'admin/profile.passkey_register_options_error'
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
            'admin/profile.passkey_registered',
            'admin/profile.passkey_register_error'
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
            'admin/profile.passkey_deleted_all',
            'admin/profile.passkey_not_found',
            'admin/profile.passkey_deleted',
            'admin/profile.passkey_delete_error'
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
                    'message' => __('admin/profile.no_passkeys_to_delete')
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => __('admin/profile.all_passkeys_deleted', ['count' => $deletedCount])
            ]);
        } catch (\Exception $e) {
            \Log::error('[Passkey] 一括削除エラー', [
                'member_id' => $member->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => __('admin/profile.passkey_delete_all_error')
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
            'admin/profile.recovery_codes_generated',
            'admin/profile.recovery_codes_generation_error'
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
            'admin/profile.recovery_codes_regenerated',
            'admin/profile.recovery_codes_regenerate_too_soon',
            'admin/profile.recovery_codes_generation_error'
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
        // 二段階認証設定の追加
        $twoFaForceMode = (int) SecuritySetting::getValue(
            'two_fa_mode',
            AuthenticationMode::UseProfileSetting->value
        );
        $twoFaMode = $member->two_fa_mode;
        
        // グローバル設定で有効な二段階認証方法を取得
        $twoFaPasskeyMode = (int) SecuritySetting::getValue('two_fa_passkey_mode', '2');
        $twoFaPasskeyEnabled = $twoFaPasskeyMode > 0;
        
        // パスキー設定の計算
        $twoFaPasskeyEditable = \App\Enums\PasskeyMode::isProfileEditable($twoFaPasskeyMode);
        $twoFaPasskeyForcedValue = \App\Enums\PasskeyMode::getForcedProfileValue($twoFaPasskeyMode);
        $twoFaPasskeyCurrentEnabled = $twoFaPasskeyForcedValue ?? ($member->two_fa_passkey_enabled ?? true);
        
        $this->viewParams['isPasskeyEditable'] = $twoFaPasskeyEditable;
        $this->viewParams['forcedPasskeyValue'] = $twoFaPasskeyForcedValue;
        $this->viewParams['currentPasskeyEnabled'] = $twoFaPasskeyCurrentEnabled;
        
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
