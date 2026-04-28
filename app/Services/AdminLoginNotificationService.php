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

namespace App\Services;

use App\Models\SecuritySetting;
use App\Notifications\AdminLoginNotification;
use App\Traits\LoginNotificationTrait;

/**
 * @internal コア専用。プラグイン/テーマから参照しないこと
 *
 * 管理画面ログイン通知サービス
 *
 * LoginNotificationTraitを使用してメンバーのログイン通知を処理
 */
class AdminLoginNotificationService
{
    use LoginNotificationTrait;

    /**
     * グローバル設定のキー名を取得
     */
    protected function getGlobalSettingKey(): string
    {
        return 'login_notification_mode';
    }

    /**
     * 設定値を取得する関数を取得（セキュリティ設定から）
     */
    protected function getSettingGetter(): callable
    {
        return fn () => SecuritySetting::getValue($this->getGlobalSettingKey(), '0');
    }

    /**
     * 通知クラス名を取得
     */
    protected function getNotificationClass(): string
    {
        return AdminLoginNotification::class;
    }

    /**
     * ログコンテキスト名を取得
     */
    protected function getLogContext(): string
    {
        return 'Admin login notification';
    }
}
