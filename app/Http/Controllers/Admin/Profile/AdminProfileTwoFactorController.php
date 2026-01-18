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
use App\Http\Requests\Admin\Profile\ProfileUpdateRequest;
use App\Enums\AuthenticationMode;
use App\Enums\TwoFaMethod;
use App\Models\MemberSetting;
use App\Services\MailServerValidatorService;
use App\Services\TwoFa\TwoFaPasskeyService;
use App\Services\TwoFa\TwoFaRecoveryCodeService;
use Illuminate\Support\Facades\Auth;

class AdminProfileTwoFactorController extends AdminLoggedInController
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
        
        return view('admin.profile.two-factor', $this->viewParams);
    }

    /**
     * Update two-factor authentication settings.
     */
    public function update(ProfileUpdateRequest $request)
    {
        $member = Auth::guard('member')->user();
        $validated = $request->validated();
        
        // 二段階認証モードの変更を検出するため、保存前の値を取得（整数値として）
        $twoFaOldMode = is_int($member->two_fa_mode) ? $member->two_fa_mode : $member->two_fa_mode->value;
        
        // two_fa_mode は全体設定が UseProfileSetting のときだけ上書き
        $twoFaForceMode = (int) MemberSetting::getValue('two_fa_mode', AuthenticationMode::UseProfileSetting->value);
        if ($twoFaForceMode === AuthenticationMode::UseProfileSetting->value && array_key_exists('two_fa_mode', $validated)) {
            $member->two_fa_mode = (int) $validated['two_fa_mode'];
        }
        
        // two_fa_passkey_enabled は全体設定の two_fa_passkey_mode が UseProfileSetting のときだけ上書き
        $twoFaPasskeyMode = (int) MemberSetting::getValue('two_fa_passkey_mode', '2');
        if ($twoFaPasskeyMode === \App\Enums\PasskeyMode::UseProfileSetting->value && array_key_exists('two_fa_passkey_enabled', $validated)) {
            $member->two_fa_passkey_enabled = (bool) $validated['two_fa_passkey_enabled'];
        }
        
        // two_fa_default_method の処理
        if (array_key_exists('two_fa_default_method', $validated)) {
            $member->two_fa_default_method = (int) $validated['two_fa_default_method'];
        }
        
        $member->save();
        
        // 二段階認証が有効化された場合、回復コードを自動生成
        $shouldGenerateRecoveryCodes = false;
        
        if ($twoFaForceMode === AuthenticationMode::UseProfileSetting->value && array_key_exists('two_fa_mode', $validated)) {
            $twoFaNewMode = (int) $validated['two_fa_mode'];
            
            // 無効→有効に変更された場合
            if ($twoFaOldMode === AuthenticationMode::Disabled->value && 
                $twoFaNewMode === AuthenticationMode::Always->value) {
                
                $twoFaRecoveryCodeService = new TwoFaRecoveryCodeService();
                
                // 回復コードが存在しない場合は生成
                if (!$twoFaRecoveryCodeService->hasRecoveryCodes($member)) {
                    try {
                        $recoveryCodeService = app(\App\Services\TwoFa\TwoFaRecoveryCodeService::class);
                        $codes = $recoveryCodeService->generate($member);
                        $shouldGenerateRecoveryCodes = true;
                    } catch (\Exception $e) {
                        \Log::error('[Profile] Failed to auto-generate recovery codes', [
                            'member_id' => $member->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
        }
        
        $redirect = redirect()->route('admin.profile.two-factor')->with('success', __('admin/profile.updated'));
        
        // 回復コードが生成された場合はセッションに保存
        if ($shouldGenerateRecoveryCodes && isset($codes)) {
            $redirect->with('auto_generated_recovery_codes', $codes);
        }
        
        return $redirect;
    }

    /**
     * Load two-factor authentication settings.
     */
    private function loadTwoFactorSettings($member)
    {
        // 二段階認証設定の追加
        $twoFaForceMode = (int) MemberSetting::getValue(
            'two_fa_mode',
            AuthenticationMode::UseProfileSetting->value
        );
        $twoFaMode = $member->two_fa_mode;
        
        // グローバル設定で有効な二段階認証方法を取得
        $twoFaPasskeyMode = (int) MemberSetting::getValue('two_fa_passkey_mode', '2');
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
        
        $twoFaDefaultMethod = (int) MemberSetting::getValue('two_fa_default_method', TwoFaMethod::EMAIL->value);
        
        $this->viewParams['twoFaForceMode'] = $twoFaForceMode;
        $this->viewParams['twoFaMode'] = $twoFaMode;
        $this->viewParams['twoFaEnabledMethods'] = $twoFaEnabledMethods;
        $this->viewParams['twoFaPasskeyMode'] = $twoFaPasskeyMode;
        $this->viewParams['twoFaPasskeyEnabled'] = $twoFaPasskeyEnabled;
        $this->viewParams['twoFaDefaultMethod'] = $twoFaDefaultMethod;
        
        // Passkeyデバイス一覧を取得
        $twoFaPasskeyService = new TwoFaPasskeyService();
        $this->viewParams['twoFaPasskeyDevices'] = $twoFaPasskeyService->getDevices($member);
        
        // 回復コード情報を取得
        $twoFaRecoveryCodeService = new TwoFaRecoveryCodeService();
        $this->viewParams['twoFaRecoveryCodesCount'] = $twoFaRecoveryCodeService->getRemainingCount($member);
        $this->viewParams['twoFaHasRecoveryCodes'] = $twoFaRecoveryCodeService->hasRecoveryCodes($member);
    }
}
