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
 *       Dixlase Plugin and Theme Exception (see LICENSE
 *       for full exception terms); or
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

namespace App\Http\Controllers\Admin;

use App\Helpers\AdminModeHelper;
use App\Presenters\Admin\DashboardPresenter;
use App\Services\TwoFa\TwoFaPasskeyService;
use App\Services\TwoFa\TwoFaRecoveryCodeService;
use App\Services\TwoFa\TwoFaStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AdminDashboardController extends AdminLoggedInController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * ダッシュボード表示
     */
    public function index()
    {
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

        // はじめにカードのデータ準備
        if (! $user->getting_started_dismissed) {
            $visited = $user->getting_started_visited ?? [];
            $this->viewParams['gettingStarted'] = [
                'visited' => $visited,
                'allCompleted' => count(array_intersect(['two_fa', 'plugins', 'theme', 'front'], $visited)) >= 4,
            ];
        }

        // ダッシュボード表示データ
        $isAdvancedMode = AdminModeHelper::isAdvancedMode();
        $mailStatus = DashboardPresenter::mailServerStatus();
        $captchaStatus = DashboardPresenter::captchaStatus();
        $siteHealthItems = array_merge(
            DashboardPresenter::siteHealth($user),
            [array_merge(['key' => 'mail'], $mailStatus)],
            [array_merge(['key' => 'captcha'], $captchaStatus)],
        );
        $this->viewParams['siteHealth'] = DashboardPresenter::decorateSiteHealthItems($siteHealthItems, $isAdvancedMode);
        $this->viewParams['systemInfo'] = DashboardPresenter::systemInfo();
        $this->viewParams['pluginWidgets'] = DashboardPresenter::pluginWidgets();
        $this->viewParams['pluginNotifications'] = DashboardPresenter::pluginNotifications();
        $this->viewParams['extensionOverview'] = DashboardPresenter::extensionOverview();
        $this->viewParams['memberOverview'] = DashboardPresenter::memberOverview();
        $this->viewParams['recentActivity'] = DashboardPresenter::recentActivity();
        $this->viewParams['isAdvancedMode'] = $isAdvancedMode;

        return view('admin::dashboard', $this->viewParams);
    }

    /**
     * はじめにカードを非表示にする（Ajax）
     */
    public function dismissGettingStarted(): JsonResponse
    {
        $user = Auth::guard('member')->user();
        $user->getting_started_dismissed = true;
        $user->save();

        return response()->json(['success' => true]);
    }

    /**
     * はじめにカードのステップを訪問済みにする（Ajax）
     */
    public function visitGettingStartedStep(Request $request): JsonResponse
    {
        $step = $request->input('step');
        $validSteps = ['two_fa', 'plugins', 'theme', 'front'];

        if (! in_array($step, $validSteps)) {
            return response()->json(['success' => false], 422);
        }

        $user = Auth::guard('member')->user();
        $visited = $user->getting_started_visited ?? [];

        if (! in_array($step, $visited)) {
            $visited[] = $step;
            $user->getting_started_visited = $visited;

            // 全ステップ訪問済みなら自動dismiss
            if (count(array_intersect($validSteps, $visited)) >= count($validSteps)) {
                $user->getting_started_dismissed = true;
            }

            $user->save();
        }

        return response()->json([
            'success' => true,
            'visited' => $visited,
            'allCompleted' => $user->getting_started_dismissed,
        ]);
    }
}
