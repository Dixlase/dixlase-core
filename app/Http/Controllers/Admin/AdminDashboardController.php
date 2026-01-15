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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Enums\TwoFaMethod;
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

// ログレベル別テスト
Log::debug('DEBUGレベル - 通知されないはず', ['level' => 'debug']);
Log::info('INFOレベル - 通知されないはず', ['level' => 'info']);
Log::notice('NOTICEレベル - 通知されないはず', ['level' => 'notice']);
Log::warning('WARNINGレベル - 通知されないはず（デフォルト設定）', ['level' => 'warning']);
Log::error('ERRORレベル - 通知される', ['level' => 'error']);
Log::critical('CRITICALレベル - 通知される', ['level' => 'critical']);
Log::alert('ALERTレベル - 通知される', ['level' => 'alert']);
Log::emergency('EMERGENCYレベル - 通知される', ['level' => 'emergency']);


        // 回復コード情報を取得
        $user = Auth::guard('member')->user();
        $twoFaRecoveryCodeService = new TwoFaRecoveryCodeService();
        $this->viewParams['twoFaRecoveryCodesCount'] = $twoFaRecoveryCodeService->getRemainingCount($user);
        $this->viewParams['twoFaHasRecoveryCodes'] = $twoFaRecoveryCodeService->hasRecoveryCodes($user);
        $this->viewParams['twoFaCanRegenerateRecoveryCodes'] = $twoFaRecoveryCodeService->canRegenerate($user);
        $this->viewParams['twoFaNextRegenerateTime'] = $twoFaRecoveryCodeService->getNextRegenerateTime($user);

        return view('admin::dashboard', $this->viewParams);
    }
}
