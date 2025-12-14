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
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Enums\TwoFactorMethod;
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
        // デバッグ: セッションの状態を確認
        Log::info('[Dashboard Index] Session check', [
            'has_auto_generated_recovery_codes' => session()->has('auto_generated_recovery_codes'),
            'auto_generated_recovery_codes' => session('auto_generated_recovery_codes'),
            'all_session_keys' => array_keys(session()->all()),
        ]);
        
        /*
        Log::info('管理者情報:', [
            'ID' => $this->member->id,
            '名前' => $this->member->name,
            'メール' => $this->member->email,
            '役割' => $this->member->role,
            '外観設定' => $this->member->appearance,
            'ステータス' => $this->member->status,
        ]);
        */

        // 回復コード情報を取得
        $user = Auth::guard('member')->user();
        $recoveryCodeService = app(\App\Services\RecoveryCodeService::class);
        $this->viewParams['recoveryCodesCount'] = $recoveryCodeService->getRemainingCount($user);
        $this->viewParams['hasRecoveryCodes'] = $recoveryCodeService->hasRecoveryCodes($user);
        $this->viewParams['canRegenerateRecoveryCodes'] = $recoveryCodeService->canRegenerate($user);
        $this->viewParams['nextRegenerateTime'] = $recoveryCodeService->getNextRegenerateTime($user);

        // 認証方法の変更を検知
        $shouldShowMethodChangeModal = false;
        $usedMethod = null;
        $currentMethod = null;
        
        if ($user->last_2fa_method !== null && $user->two_factor_method !== null) {
            // 最後に使用した認証方法と現在のデフォルト認証方法が異なる場合
            if ($user->last_2fa_method !== $user->two_factor_method) {
                $shouldShowMethodChangeModal = true;
                $usedMethod = TwoFactorMethod::from($user->last_2fa_method);
                $currentMethod = TwoFactorMethod::from($user->two_factor_method);
            }
        }
        
        $this->viewParams['shouldShowMethodChangeModal'] = $shouldShowMethodChangeModal;
        $this->viewParams['usedMethod'] = $usedMethod;
        $this->viewParams['currentMethod'] = $currentMethod;

        return view('admin::dashboard', $this->viewParams);
    }

    /**
     * 今回使用した認証方法をデフォルトに設定
     */
    public function switchToUsedMethod(Request $request)
    {
        $user = Auth::guard('member')->user();
        
        if ($user->last_2fa_method !== null) {
            // last_2fa_methodをtwo_factor_methodに設定
            $user->two_factor_method = $user->last_2fa_method;
            $user->save();
            
            return response()->json([
                'success' => true,
                'message' => __('admin/dashboard.method_switched_success')
            ]);
        }
        
        return response()->json([
            'success' => false,
            'message' => __('admin/dashboard.method_switch_failed')
        ], 400);
    }

    /**
     * 認証方法変更モーダルを閉じる（last_2fa_methodをtwo_factor_methodに同期）
     */
    public function dismissMethodChangeModal(Request $request)
    {
        $user = Auth::guard('member')->user();
        
        // last_2fa_methodをtwo_factor_methodに同期して、次回モーダルを表示しない
        $user->last_2fa_method = $user->two_factor_method;
        $user->save();
        
        return response()->json([
            'success' => true
        ]);
    }
}
