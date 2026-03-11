<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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

use App\Presenters\Admin\DashboardPresenter;
use App\Services\TwoFa\TwoFaPasskeyService;
use App\Services\TwoFa\TwoFaRecoveryCodeService;
use App\Services\TwoFa\TwoFaStatusService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AdminDashboardController extends AdminLoggedInController
{
    // 初期設定を行う
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

        // TwoFaStatusServiceを使用して判定
        $twoFaStatusService = new TwoFaStatusService();

        // 2FAが有効かつ回復コード未生成の場合、自動生成してモーダル表示
        if ($twoFaStatusService->shouldGenerateRecoveryCodes($user, $twoFaRecoveryCodeService)) {
            try {
                $codes = $twoFaRecoveryCodeService->generate($user);
                $this->viewParams['auto_generated_recovery_codes'] = $codes;
            } catch (\Exception $e) {
                Log::error('[Dashboard] Failed to auto-generate recovery codes', [
                    'member_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // パスキーが有効かつデバイス未登録の場合、促進モーダルを表示
        $twoFaPasskeyService = new TwoFaPasskeyService();
        if ($twoFaStatusService->shouldPromptPasskeyRegistration($user, $twoFaPasskeyService)) {
            $this->viewParams['prompt_passkey_registration'] = true;
        }

        // ダッシュボード表示データ
        $this->viewParams['securityOverview'] = DashboardPresenter::securityOverview($user);
        $this->viewParams['mailStatus'] = DashboardPresenter::mailServerStatus();
        $this->viewParams['captchaStatus'] = DashboardPresenter::captchaStatus();
        $this->viewParams['systemInfo'] = DashboardPresenter::systemInfo();
        $this->viewParams['pluginWidgets'] = DashboardPresenter::pluginWidgets();
        $this->viewParams['pluginNotifications'] = DashboardPresenter::pluginNotifications();
        $this->viewParams['extensionOverview'] = DashboardPresenter::extensionOverview();
        $this->viewParams['memberOverview'] = DashboardPresenter::memberOverview();
        $this->viewParams['recentActivity'] = DashboardPresenter::recentActivity();

        return view('admin::dashboard', $this->viewParams);
    }
}
