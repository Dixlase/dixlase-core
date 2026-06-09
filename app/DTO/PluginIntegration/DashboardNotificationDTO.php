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

namespace App\DTO\PluginIntegration;

/**
 * Dashboard Notification DTO
 *
 * Display warnings, recommendations, and informational notifications on the dashboard from plugins, remote servers, and system
 * Holds warning, recommendation, and information notifications
 */
final readonly class DashboardNotificationDTO
{
    /** @var string Notification from plugin */
    public const SOURCE_PLUGIN = 'plugin';

    /** @var string Notification from remote server */
    public const SOURCE_REMOTE = 'remote';

    /** @var string Notification from system internal */
    public const SOURCE_SYSTEM = 'system';

    /**
     * @param  string  $key  Notification unique key (e.g., 'inquiry_captcha_off')
     * @param  string  $level  Notification level ('warning', 'recommendation', 'info')
     * @param  string  $message  Notification message (translated string)
     * @param  string  $icon  Font Awesome icon class (e.g., 'fas fa-exclamation-triangle')
     * @param  string  $pluginName  Plugin display name (translated string)
     * @param  string|null  $url  Link to corresponding page (nullable)
     * @param  string|null  $actionLabel  Action link label (nullable)
     * @param  string  $source  Notification source ('plugin', 'remote', 'system')
     */
    public function __construct(
        public string $key,
        public string $level,
        public string $message,
        public string $icon,
        public string $pluginName,
        public ?string $url = null,
        public ?string $actionLabel = null,
        public string $source = self::SOURCE_PLUGIN,
    ) {}
}
