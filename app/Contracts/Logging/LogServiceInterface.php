<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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

namespace App\Contracts\Logging;

use App\DTO\Logging\LogContextDTO;

/**
 * Log service contract
 *
 * Provides an interface for using unified logging functionality
 * from Core and plugins
 */
interface LogServiceInterface
{
    /**
     * Output info log
     *
     * @param  string  $message  Message
     * @param  LogContextDTO|array  $context  Context
     * @param  string|null  $channel  Channel name (default if null)
     */
    public function info(string $message, LogContextDTO|array $context = [], ?string $channel = null): void;

    /**
     * Output warning log
     *
     * @param  string  $message  Message
     * @param  LogContextDTO|array  $context  Context
     * @param  string|null  $channel  Channel name
     */
    public function warning(string $message, LogContextDTO|array $context = [], ?string $channel = null): void;

    /**
     * Output error log
     *
     * @param  string  $message  Message
     * @param  LogContextDTO|array  $context  Context
     * @param  string|null  $channel  Channel name
     */
    public function error(string $message, LogContextDTO|array $context = [], ?string $channel = null): void;

    /**
     * Output debug log
     *
     * @param  string  $message  Message
     * @param  LogContextDTO|array  $context  Context
     * @param  string|null  $channel  Channel name
     */
    public function debug(string $message, LogContextDTO|array $context = [], ?string $channel = null): void;

    /**
     * Output critical error log
     *
     * @param  string  $message  Message
     * @param  LogContextDTO|array  $context  Context
     * @param  string|null  $channel  Channel name
     */
    public function critical(string $message, LogContextDTO|array $context = [], ?string $channel = null): void;

    /**
     * Output operation log (admin panel operations, etc.)
     *
     * @param  string  $action  Operation details
     * @param  LogContextDTO|array  $context  Context
     */
    public function activity(string $action, LogContextDTO|array $context = []): void;

    /**
     * Output login log
     *
     * @param  string  $action  Login/Logout
     * @param  LogContextDTO|array  $context  Context
     */
    public function login(string $action, LogContextDTO|array $context = []): void;

    /**
     * Output front-end operation log
     *
     * @param  string  $action  Operation details
     * @param  LogContextDTO|array  $context  Context
     */
    public function frontActivity(string $action, LogContextDTO|array $context = []): void;

    /**
     * Output front-end error log
     *
     * @param  string  $error  Error details
     * @param  LogContextDTO|array  $context  Context
     */
    public function frontError(string $error, LogContextDTO|array $context = []): void;

    /**
     * Output log to custom channel
     *
     * @param  string  $channel  Channel name
     * @param  string  $level  Log level
     * @param  string  $message  Message
     * @param  LogContextDTO|array  $context  Context
     */
    public function log(string $channel, string $level, string $message, LogContextDTO|array $context = []): void;

    /**
     * Get list of available log channels
     *
     * @return array<string>
     */
    public function getAvailableChannels(): array;
}
