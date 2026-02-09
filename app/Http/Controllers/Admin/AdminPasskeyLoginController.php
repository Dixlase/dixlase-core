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

/**
 * パスキーログインコントローラー
 * 
 * WebAuthnを使用したパスキー認証によるログイン処理
 */
class AdminPasskeyLoginController extends AdminController
{
    use PasskeyLoginTrait;

    public function __construct(TwoFaPasskeyService $passkeyService)
    {
        parent::__construct();
        $this->passkeyService = $passkeyService;
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
