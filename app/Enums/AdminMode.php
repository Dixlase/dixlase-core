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

namespace App\Enums;

/**
 * @internal For Core use only. Do not reference from plugins/themes
 *
 * Admin panel mode definition
 */
enum AdminMode: int
{
    case Simple = 0;
    case Advanced = 1;

    /**
     * Get translation key
     */
    public function translationKey(): string
    {
        return match ($this) {
            self::Simple => 'install/mode.simple_mode',
            self::Advanced => 'install/mode.advanced_mode',
        };
    }

    /**
     * Get description translation key
     */
    public function descriptionKey(): string
    {
        return match ($this) {
            self::Simple => 'install/mode.simple_mode_description',
            self::Advanced => 'install/mode.advanced_mode_description',
        };
    }

    /**
     * Get icon class
     */
    public function iconClass(): string
    {
        return match ($this) {
            self::Simple => 'fas fa-magic',
            self::Advanced => 'fas fa-cogs',
        };
    }

    /**
     * Whether it is simple mode
     */
    public function isSimple(): bool
    {
        return $this === self::Simple;
    }

    /**
     * Whether it is advanced mode
     */
    public function isAdvanced(): bool
    {
        return $this === self::Advanced;
    }

    /**
     * Get default value
     */
    public static function default(): self
    {
        return self::Simple;
    }

    /**
     * Get mode from integer value
     */
    public static function fromInt(?int $value): self
    {
        if ($value === null) {
            return self::default();
        }

        return self::tryFrom($value) ?? self::default();
    }
}
