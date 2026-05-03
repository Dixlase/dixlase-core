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
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
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

use App\Enums\LoginIdentifierMode;
use App\Helpers\TwoFaHelper;
use App\Models\Member;
use App\Services\TwoFa\TwoFaPasskeyService;
use App\Traits\PasskeyLoginTrait;
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
     * @return \Illuminate\Http\JsonResponse
     */
    public function getChallenge(Request $request)
    {
        // メールサーバー設定チェック
        $twoFaHelper = app(TwoFaHelper::class);
        if (! $twoFaHelper->isMailConfigured()) {
            return response()->json([
                'success' => false,
                'error' => __($this->getTranslationPrefix().'.two_fa_disabled'),
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

    /**
     * ログイン識別子モードを取得
     */
    protected function getLoginIdentifierMode(): LoginIdentifierMode
    {
        $value = (int) \App\Models\SecuritySetting::getValue('login_identifier_mode', LoginIdentifierMode::EmailOrAccountName->value);

        return LoginIdentifierMode::tryFrom($value) ?? LoginIdentifierMode::EmailOrAccountName;
    }

    /**
     * メールアドレスでのログインをサポートするかどうか
     */
    protected function supportsEmailLogin(): bool
    {
        return $this->getLoginIdentifierMode()->supportsEmail();
    }

    /**
     * アカウント名でのログインをサポートするかどうか
     */
    protected function supportsAccountNameLogin(): bool
    {
        return $this->getLoginIdentifierMode()->supportsAccountName();
    }
}
