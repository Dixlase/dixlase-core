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
use App\Services\MailServerValidatorService;
use App\Services\TwoFa\TwoFaPasskeyService;
use App\Services\TwoFa\TwoFaRecoveryCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminProfileController extends AdminLoggedInController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * モデルのルートパラメータ名を取得
     */
    protected function getModelRouteParameterName(): string
    {
        return 'member';
    }

    /**
     * Show the profile overview page.
     */
    public function index()
    {
        $member = Auth::guard('member')->user();
        
        // メールサーバー設定状態を渡す
        $this->viewParams['isMailServerTested'] = MailServerValidatorService::isMailServerTested();
        
        // 二段階認証設定の追加
        $twoFaMode = $member->two_fa_mode;
        $this->viewParams['twoFaMode'] = $twoFaMode;
        
        // Passkeyデバイス一覧を取得
        $twoFaPasskeyService = new TwoFaPasskeyService();
        $this->viewParams['twoFaPasskeyDevices'] = $twoFaPasskeyService->getDevices($member);
        
        // 回復コード情報を取得
        $twoFaRecoveryCodeService = new TwoFaRecoveryCodeService();
        $this->viewParams['twoFaRecoveryCodesCount'] = $twoFaRecoveryCodeService->getRemainingCount($member);
        
        return view('admin.profile.index', $this->viewParams);
    }

    /**
     * メール認証処理（セキュリティ強化版：ログイン後に認証）
     */
    public function verifyEmail(Request $request, $id, $hash)
    {
        $emailVerificationHelper = app(\App\Helpers\EmailVerificationHelper::class);
        
        // IDからメンバーを取得
        $member = \App\Models\Member::findOrFail($id);

        // ハッシュの検証
        if (!$emailVerificationHelper->verifyHash($member, $hash)) {
            return redirect()->route('admin.login')
                ->with('error', __('admin/profile.email_verification_invalid'));
        }

        // 認証が必要かチェック
        $verificationStatus = $emailVerificationHelper->needsVerification($member);
        if (!$verificationStatus['needs_verification']) {
            return redirect()->route('admin.login')
                ->with('info', __('admin/profile.email_already_verified'));
        }

        // ログイン状態をチェック
        $currentUser = \Auth::guard('member')->user();
        
        // ログイン済みで、認証対象のメンバーと一致する場合は即座に処理
        if ($currentUser && $currentUser->id === $member->id) {
            $result = $emailVerificationHelper->processVerificationImmediately($member, 'admin');
            return redirect($result['redirect'])->with(
                $result['success'] ? 'success' : 'error',
                $result['message']
            );
        }

        // 未ログインまたは別のユーザーでログイン中の場合
        // 認証トークン情報をセッションに保存
        $emailVerificationHelper->storeVerificationInSession($member, $hash);

        // コンテキストに応じたメッセージを選択
        $messageKey = $emailVerificationHelper->getLoginRequiredMessageKey(
            $verificationStatus['is_email_change']
        );

        // ログイン画面にリダイレクト
        return redirect()->route('admin.login')->with('info', __($messageKey));
    }

}
