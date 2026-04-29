<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
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

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * ログイン通知の共通トレイト
 *
 * メンバーとユーザーのログイン通知処理で共通して使用される機能を提供します。
 * このトレイトを使用するサービスクラスは、以下の抽象メソッドを実装する必要があります。
 */
trait LoginNotificationTrait
{
    /**
     * グローバル設定のキー名を取得（継承先で実装）
     *
     * @return string 設定キー名（例: 'login_notification_mode'）
     */
    abstract protected function getGlobalSettingKey(): string;

    /**
     * 設定値を取得する関数を取得（継承先で実装）
     *
     * @return callable 設定取得関数
     */
    abstract protected function getSettingGetter(): callable;

    /**
     * 通知クラス名を取得（継承先で実装）
     *
     * @return string 通知クラス名
     */
    abstract protected function getNotificationClass(): string;

    /**
     * ログコンテキスト名を取得（継承先で実装）
     *
     * @return string コンテキスト名（例: 'Admin login notification', 'User login notification'）
     */
    abstract protected function getLogContext(): string;

    /**
     * ログイン通知を処理
     *
     * @param  Model  $user  ユーザーモデル（Member または User）
     * @param  Request  $request  リクエスト
     */
    public function handle(Model $user, Request $request): void
    {
        $loginNotificationService = app(\App\Services\LoginNotificationService::class);

        $loginNotificationService->handle(
            $user,
            $request,
            $this->getSettingGetter(),
            $this->getNotificationClass(),
            $this->getLogContext()
        );
    }
}
