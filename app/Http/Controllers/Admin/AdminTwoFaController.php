<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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
 *       (see LICENSE-COMMERCIAL, or contact info@dixlase.org).
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

use App\Models\SecuritySetting;
use App\Services\TwoFa\TwoFaService;

class AdminTwoFaController extends AdminLoginController
{
    use \App\Traits\TwoFa\TwoFaAuthenticationTrait;

    /**
     * Get settings model class name
     */
    protected function getSettingModelClass(): string
    {
        return SecuritySetting::class;
    }

    /**
     * Get two-factor authentication service instance
     */
    protected function getTwoFaService()
    {
        return app(TwoFaService::class, [
            'settingModelClass' => SecuritySetting::class,
            'context' => 'admin',
        ]);
    }

    /**
     * Get CAPTCHA action name (for two-factor authentication)
     */
    protected function getCaptchaAction(): string
    {
        return 'admin_two_fa';
    }
}
