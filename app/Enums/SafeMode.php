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
 *       (see LICENSE.commercial, or contact info@dixlase.org).
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

namespace App\Enums;

/**
 * @internal For Core use only. Do not reference from plugins/themes
 *
 * Safe mode level
 *
 * Defines safe mode levels for system recovery.
 * Supports three levels: CSP disabled, plugin disabled, theme disabled.
 */
enum SafeMode: string
{
    /** CSP header disabled */
    case Csp = 'csp';

    /** Plugin assets/routes disabled */
    case Plugins = 'plugins';

    /** Theme disabled (front-end only) */
    case Theme = 'theme';

    /**
     * Get session key
     */
    public function sessionKey(): string
    {
        return 'safe_mode_'.$this->value;
    }

    /**
     * Get Tailwind class for banner background color
     */
    public function bannerBgClass(): string
    {
        return match ($this) {
            self::Csp => 'bg-red-600 dark:bg-red-700',
            self::Plugins => 'bg-orange-600 dark:bg-orange-700',
            self::Theme => 'bg-red-600 dark:bg-red-700',
        };
    }

    /**
     * Get Tailwind class for banner button background color
     */
    public function bannerButtonClass(): string
    {
        return match ($this) {
            self::Csp => 'bg-red-800 dark:bg-red-900 hover:bg-red-900 dark:hover:bg-red-950',
            self::Plugins => 'bg-orange-800 dark:bg-orange-900 hover:bg-orange-900 dark:hover:bg-orange-950',
            self::Theme => 'bg-red-800 dark:bg-red-900 hover:bg-red-900 dark:hover:bg-red-950',
        };
    }

    /**
     * Get Tailwind class for banner link text color
     */
    public function bannerLinkTextClass(): string
    {
        return match ($this) {
            self::Csp => 'text-red-600 dark:text-red-700',
            self::Plugins => 'text-orange-600 dark:text-orange-700',
            self::Theme => 'text-red-600 dark:text-red-700',
        };
    }

    /**
     * Get Font Awesome icon class
     */
    public function iconClass(): string
    {
        return match ($this) {
            self::Csp => 'fas fa-shield-alt',
            self::Plugins => 'fas fa-puzzle-piece',
            self::Theme => 'fas fa-paint-brush',
        };
    }

    /**
     * Get related settings page route name
     */
    public function settingsRoute(): string
    {
        return match ($this) {
            self::Csp => 'admin.settings.security.csp',
            self::Plugins => 'admin.settings.plugins.index',
            self::Theme => 'admin.settings.themes.index',
        };
    }

    /**
     * Get translation key prefix
     */
    public function translationPrefix(): string
    {
        return 'admin/safe-mode.'.$this->value;
    }

    /**
     * Get Enum from URL parameter value (supports comma-separated)
     *
     * @return SafeMode[]
     */
    public static function fromUrlParam(string $param): array
    {
        $modes = [];

        // Backward compatibility for ?safe=1
        if ($param === '1') {
            return [self::Csp];
        }

        $parts = array_map('trim', explode(',', $param));

        foreach ($parts as $part) {
            $mode = self::tryFrom($part);
            if ($mode !== null) {
                $modes[] = $mode;
            }
        }

        return array_unique($modes, SORT_REGULAR);
    }
}
