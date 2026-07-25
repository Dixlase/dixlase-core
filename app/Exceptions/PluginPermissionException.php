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

namespace App\Exceptions;

use Exception;

/**
 * Plugin permission violation exception
 *
 * Thrown when a plugin attempts to use a permission it has not declared
 */
class PluginPermissionException extends Exception
{
    /**
     * Plugin slug
     */
    protected string $pluginSlug = '';

    /**
     * Violated permission
     */
    protected string $permission = '';

    /**
     * Constructor
     */
    public function __construct(
        string $message = '',
        string $pluginSlug = '',
        string $permission = '',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->pluginSlug = $pluginSlug;
        $this->permission = $permission;
    }

    /**
     * Get plugin slug
     */
    public function getPluginSlug(): string
    {
        return $this->pluginSlug;
    }

    /**
     * Get violated permission
     */
    public function getPermission(): string
    {
        return $this->permission;
    }
}
