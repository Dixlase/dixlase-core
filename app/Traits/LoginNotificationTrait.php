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

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Common trait for login notifications
 *
 * Provides functionality commonly used in login notification processing for members and users.
 * Service classes using this trait must implement the following abstract methods.
 */
trait LoginNotificationTrait
{
    /**
     * Retrieve global settings key name (implement in child class)
     *
     * @return string Settings key name (e.g. 'login_notification_mode')
     */
    abstract protected function getGlobalSettingKey(): string;

    /**
     * Retrieve function to retrieve settings value (implement in child class)
     *
     * @return callable Settings retrieval function
     */
    abstract protected function getSettingGetter(): callable;

    /**
     * Retrieve notification class name (implement in child class)
     *
     * @return string Notification class name
     */
    abstract protected function getNotificationClass(): string;

    /**
     * Retrieve log context name (implement in child class)
     *
     * @return string Context name (e.g. 'Admin login notification', 'User login notification')
     */
    abstract protected function getLogContext(): string;

    /**
     * Process login notification
     *
     * @param  Model  $user  User model (Member or User)
     * @param  Request  $request  Request
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
