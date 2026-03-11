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

use App\Models\SecuritySetting;
use App\Services\TwoFa\TwoFaService;

class AdminTwoFaController extends AdminLoginController
{
    use \App\Traits\TwoFa\TwoFaAuthenticationTrait;

    /**
     * 設定モデルクラス名を取得
     */
    protected function getSettingModelClass(): string
    {
        return SecuritySetting::class;
    }

    /**
     * 二段階認証サービスのインスタンスを取得
     */
    protected function getTwoFaService()
    {
        return app(TwoFaService::class, [
            'settingModelClass' => SecuritySetting::class,
            'context' => 'admin'
        ]);
    }

    /**
     * CAPTCHAアクション名を取得（二段階認証用）
     */
    protected function getCaptchaAction(): string
    {
        return 'admin_two_fa';
    }
}
