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

namespace App\Services;

use App\Models\SecuritySetting;
use App\Notifications\AdminLoginNotification;
use App\Traits\LoginNotificationTrait;

/**
 * @internal For Core use only. Do not reference from plugins/themes
 *
 * Admin panel login notification service
 *
 * Processes member login notifications using LoginNotificationTrait
 */
class AdminLoginNotificationService
{
    use LoginNotificationTrait;

    /**
     * Retrieve global settings key name
     */
    protected function getGlobalSettingKey(): string
    {
        return 'login_notification_mode';
    }

    /**
     * Retrieve function to retrieve settings value (from security settings)
     */
    protected function getSettingGetter(): callable
    {
        return fn () => SecuritySetting::getValue($this->getGlobalSettingKey(), '0');
    }

    /**
     * Retrieve notification class name
     */
    protected function getNotificationClass(): string
    {
        return AdminLoginNotification::class;
    }

    /**
     * Retrieve log context name
     */
    protected function getLogContext(): string
    {
        return 'Admin login notification';
    }
}
