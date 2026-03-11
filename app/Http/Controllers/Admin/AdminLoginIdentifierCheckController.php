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

use App\Models\Member;
use App\Traits\LoginIdentifierCheckTrait;

/**
 * ログイン識別子確認コントローラー
 * 
 * メールアドレスまたはアカウント名の存在確認を行う
 * セキュリティ対策：レート制限、タイミング攻撃対策、監査ログ記録
 */
class AdminLoginIdentifierCheckController extends AdminController
{
    use LoginIdentifierCheckTrait;

    /**
     * CAPTCHAアクション名を取得
     */
    protected function getCaptchaAction(): string
    {
        return 'admin_login';
    }

    /**
     * 設定モデルクラス名を取得
     */
    protected function getSettingModelClass(): string
    {
        return \App\Models\SecuritySetting::class;
    }

    /**
     * ユーザーモデルクラス名を取得
     */
    protected function getUserModelClass(): string
    {
        return Member::class;
    }

    /**
     * コンテキストを取得
     */
    protected function getContext(): string
    {
        return 'admin';
    }
}
