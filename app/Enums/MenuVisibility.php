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

namespace App\Enums;

/**
 * @internal Core only. Do not reference from plugins/themes
 *
 * Menu visibility settings definition
 */
enum MenuVisibility: int
{
    /**
     * Display all and enable
     */
    case Full = 0;

    /**
     * Display only some features, auto-configure hidden parts
     */
    case Partial = 1;

    /**
     * Hide entire menu, auto-configure or disable
     */
    case Hidden = 2;

    /**
     * Display but "status display only (read-only)"
     */
    case ReadOnly = 3;

    /**
     * Display but "navigation only (settings on separate page or guide to mode switch)"
     */
    case GuideOnly = 4;

    /**
     * Get translation key
     */
    public function translationKey(): string
    {
        return match ($this) {
            self::Full => 'admin/settings/base/mode.visibility.full',
            self::Partial => 'admin/settings/base/mode.visibility.partial',
            self::Hidden => 'admin/settings/base/mode.visibility.hidden',
            self::ReadOnly => 'admin/settings/base/mode.visibility.read_only',
            self::GuideOnly => 'admin/settings/base/mode.visibility.guide_only',
        };
    }

    /**
     * Get description translation key
     */
    public function descriptionKey(): string
    {
        return match ($this) {
            self::Full => 'admin/settings/base/mode.visibility.full_description',
            self::Partial => 'admin/settings/base/mode.visibility.partial_description',
            self::Hidden => 'admin/settings/base/mode.visibility.hidden_description',
            self::ReadOnly => 'admin/settings/base/mode.visibility.read_only_description',
            self::GuideOnly => 'admin/settings/base/mode.visibility.guide_only_description',
        };
    }

    /**
     * Get icon class
     */
    public function iconClass(): string
    {
        return match ($this) {
            self::Full => 'fas fa-eye',
            self::Partial => 'fas fa-eye-low-vision',
            self::Hidden => 'fas fa-eye-slash',
            self::ReadOnly => 'fas fa-lock',
            self::GuideOnly => 'fas fa-directions',
        };
    }

    /**
     * Get badge color
     */
    public function badgeColor(): string
    {
        return match ($this) {
            self::Full => 'blue',
            self::Partial => 'yellow',
            self::Hidden => 'gray',
            self::ReadOnly => 'green',
            self::GuideOnly => 'purple',
        };
    }

    /**
     * Get MenuVisibility from integer value
     */
    public static function fromInt(?int $value): self
    {
        if ($value === null) {
            return self::Full;
        }

        return self::tryFrom($value) ?? self::Full;
    }
}
