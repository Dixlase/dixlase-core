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

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Services\TwoFa\TwoFaRecoveryCodeService;
use App\Services\TwoFa\TwoFaPasskeyService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Enums\TwoFaMethod;
use App\Enums\AuthenticationMode;
use Illuminate\Http\Request;

class AdminDashboardController extends AdminLoggedInController
{
    //初期設定を行う
    public function __construct()
    {
        parent::__construct();
    }
    //
    public function index()
    {
        // 回復コード情報を取得
        $user = Auth::guard('member')->user();
        $twoFaRecoveryCodeService = new TwoFaRecoveryCodeService();
        $this->viewParams['twoFaRecoveryCodesCount'] = $twoFaRecoveryCodeService->getRemainingCount($user);
        $this->viewParams['twoFaHasRecoveryCodes'] = $twoFaRecoveryCodeService->hasRecoveryCodes($user);
        $this->viewParams['twoFaCanRegenerateRecoveryCodes'] = $twoFaRecoveryCodeService->canRegenerate($user);
        $this->viewParams['twoFaNextRegenerateTime'] = $twoFaRecoveryCodeService->getNextRegenerateTime($user);

        // 2FAが有効かつ回復コード未生成の場合、自動生成してモーダル表示
        // 全体設定とプロフィール設定の両方を考慮
        $twoFaForceMode = (int) SecuritySetting::getValue('two_fa_mode', AuthenticationMode::UseProfileSetting->value);
        $profileTwoFaMode = is_int($user->two_fa_mode) ? $user->two_fa_mode : $user->two_fa_mode->value;
        
        // 実際の二段階認証モードを判定
        if ($twoFaForceMode === AuthenticationMode::UseProfileSetting->value) {
            // プロフィール設定に従う場合はプロフィールの値を使用
            $actualTwoFaMode = $profileTwoFaMode;
        } else {
            // それ以外は全体設定を使用
            $actualTwoFaMode = $twoFaForceMode;
        }
        
        $isTwoFaEnabled = ($actualTwoFaMode === AuthenticationMode::Always->value || $actualTwoFaMode === AuthenticationMode::DifferentDevice->value);
        
        $shouldGenerateRecoveryCodes = false;
        $shouldPromptPasskey = false;
        
        if ($isTwoFaEnabled && !$twoFaRecoveryCodeService->hasRecoveryCodes($user)) {
            // 回復コードを自動生成
            try {
                $codes = $twoFaRecoveryCodeService->generate($user);
                $shouldGenerateRecoveryCodes = true;
                $this->viewParams['auto_generated_recovery_codes'] = $codes;
            } catch (\Exception $e) {
                Log::error('[Dashboard] Failed to auto-generate recovery codes', [
                    'member_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        // パスキーが有効かつデバイス未登録の場合、促進モーダルを表示
        if ($isTwoFaEnabled) {
            $passkeyEnabled = $user->two_fa_passkey_enabled ?? true;
            if ($passkeyEnabled) {
                $twoFaPasskeyService = new TwoFaPasskeyService();
                $passkeyDevices = $twoFaPasskeyService->getDevices($user);
                
                if ($passkeyDevices->isEmpty()) {
                    $shouldPromptPasskey = true;
                    $this->viewParams['prompt_passkey_registration'] = true;
                }
            }
        }

        return view('admin::dashboard', $this->viewParams);
    }
}
