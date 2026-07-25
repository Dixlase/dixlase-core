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
 * @internal Core only. Do not reference from plugins/themes
 *
 * Action on security detection (general purpose)
 *
 * Defines actions to take when permission mismatch, policy violation, etc. are detected
 */
enum SecurityAction: int
{
    /**
     * Warning only
     * - Log to record
     * - Notify administrator (depending on settings)
     * - Operation is allowed
     */
    case Warn = 0;

    /**
     * Block
     * - Log to record
     * - Notify administrator (depending on settings)
     * - Deny operation
     */
    case Block = 1;

    /**
     * Get translation key base
     */
    public function translationKeyBase(): string
    {
        return 'admin.settings.security.action_'.$this->toString();
    }

    /**
     * Get label
     */
    public function label(): string
    {
        return __($this->translationKeyBase());
    }

    /**
     * Get description
     */
    public function description(): string
    {
        return __($this->translationKeyBase().'_desc');
    }

    /**
     * Get string representation
     */
    public function toString(): string
    {
        return match ($this) {
            self::Warn => 'warn',
            self::Block => 'block',
        };
    }

    /**
     * Get Enum from string
     */
    public static function fromString(string $value): ?self
    {
        return match ($value) {
            'warn' => self::Warn,
            'block' => self::Block,
            default => null,
        };
    }

    /**
     * Get CSS class (for color coding)
     */
    public function cssClass(): string
    {
        return match ($this) {
            self::Warn => 'text-yellow-600 dark:text-yellow-400',
            self::Block => 'text-red-600 dark:text-red-400',
        };
    }

    /**
     * Get color name (for radio-card-group)
     */
    public function colorName(): string
    {
        return match ($this) {
            self::Warn => 'yellow',
            self::Block => 'red',
        };
    }

    /**
     * Get icon class
     */
    public function iconClass(): string
    {
        return match ($this) {
            self::Warn => 'fas fa-exclamation-triangle',
            self::Block => 'fas fa-ban',
        };
    }

    /**
     * Get default value
     */
    public static function default(): self
    {
        return self::Warn;
    }

    /**
     * Get all actions
     */
    public static function all(): array
    {
        return self::cases();
    }

    /**
     * Get all string values
     */
    public static function getAllStrings(): array
    {
        return array_map(fn ($case) => $case->toString(), self::cases());
    }

    /**
     * Get string for validation rule
     */
    public static function validationRule(): string
    {
        return 'in:'.implode(',', self::getAllStrings());
    }
}
