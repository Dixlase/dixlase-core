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

use App\Models\Member;
use App\Traits\PasskeyLoginTrait;
use App\Services\TwoFa\TwoFaPasskeyService;
use App\Helpers\TwoFaHelper;
use Illuminate\Http\Request;

/**
 * パスキーログインコントローラー
 * 
 * WebAuthnを使用したパスキー認証によるログイン処理
 */
class AdminPasskeyLoginController extends AdminController
{
    use PasskeyLoginTrait {
        getChallenge as traitGetChallenge;
    }

    public function __construct(TwoFaPasskeyService $passkeyService)
    {
        parent::__construct();
        $this->passkeyService = $passkeyService;
    }

    /**
     * パスキー認証のチャレンジを取得（オーバーライド）
     * 
     * メールサーバー設定のチェックを追加
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getChallenge(Request $request)
    {
        // メールサーバー設定チェック
        $twoFaHelper = app(TwoFaHelper::class);
        if (!$twoFaHelper->isMailConfigured()) {
            return response()->json([
                'success' => false,
                'error' => __($this->getTranslationPrefix() . '.two_fa_disabled'),
            ], 422);
        }

        // トレイトのメソッドを呼び出し
        return $this->traitGetChallenge($request);
    }

    /**
     * ユーザーモデルクラス名を取得
     */
    protected function getUserModelClass(): string
    {
        return Member::class;
    }

    /**
     * 設定モデルクラス名を取得
     */
    protected function getSettingModelClass(): string
    {
        return \App\Models\SecuritySetting::class;
    }

    /**
     * 認証ガード名を取得
     */
    protected function getGuardName(): string
    {
        return 'member';
    }

    /**
     * ダッシュボードのルート名を取得
     */
    protected function getDashboardRoute(): string
    {
        return 'admin.dashboard';
    }

    /**
     * セッションキーのプレフィックスを取得
     */
    protected function getSessionPrefix(): string
    {
        return 'login';
    }

    /**
     * ログイン通知サービスクラス名を取得
     */
    protected function getLoginNotificationServiceClass(): string
    {
        return \App\Services\AdminLoginNotificationService::class;
    }

    /**
     * 翻訳プレフィックスを取得
     */
    protected function getTranslationPrefix(): string
    {
        return 'auth';
    }
}
