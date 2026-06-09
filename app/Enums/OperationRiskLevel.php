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

namespace App\Enums;

/**
 * Operation risk level
 *
 * Used as the foundation for the "forced re-authentication" feature in beta
 * Used to determine critical operations (Danger Zone)
 */
enum OperationRiskLevel: int
{
    case Low = 0;       // View/reference only
    case Medium = 1;    // Edit/update
    case High = 2;      // Delete/critical settings changes
    case Critical = 3;  // System settings/security settings/API key operations

    /**
     * Get the string representation
     */
    public function toString(): string
    {
        return match ($this) {
            self::Low => 'low',
            self::Medium => 'medium',
            self::High => 'high',
            self::Critical => 'critical',
        };
    }

    /**
     * Get translation key for this risk level
     */
    public function translationKey(): string
    {
        return 'common.operation_risk_level.'.$this->toString();
    }

    /**
     * Get label (translated)
     */
    public function label(): string
    {
        return __($this->translationKey());
    }

    /**
     * Get description translation key
     */
    public function descriptionKey(): string
    {
        return 'common.operation_risk_level.'.$this->toString().'_description';
    }

    /**
     * Get description (translated)
     */
    public function description(): string
    {
        return __($this->descriptionKey());
    }

    /**
     * Check if this level requires step-up authentication (planned for beta implementation)
     */
    public function requiresStepUpAuth(): bool
    {
        return $this->value >= self::High->value;
    }

    /**
     * Check if this is a dangerous operation
     */
    public function isDangerous(): bool
    {
        return $this->value >= self::High->value;
    }

    /**
     * Check if this is a critical operation
     */
    public function isCritical(): bool
    {
        return $this === self::Critical;
    }

    /**
     * Get CSS color class for UI display
     */
    public function colorClass(): string
    {
        return match ($this) {
            self::Low => 'text-gray-600 bg-gray-50',
            self::Medium => 'text-blue-600 bg-blue-50',
            self::High => 'text-orange-600 bg-orange-50',
            self::Critical => 'text-red-600 bg-red-50',
        };
    }

    /**
     * Get badge color class for UI display
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Low => 'bg-gray-100 text-gray-800',
            self::Medium => 'bg-blue-100 text-blue-800',
            self::High => 'bg-orange-100 text-orange-800',
            self::Critical => 'bg-red-100 text-red-800',
        };
    }

    /**
     * Get icon name (FontAwesome)
     */
    public function icon(): string
    {
        return match ($this) {
            self::Low => 'fa-eye',
            self::Medium => 'fa-edit',
            self::High => 'fa-exclamation-triangle',
            self::Critical => 'fa-shield-alt',
        };
    }

    /**
     * Create from string
     */
    public static function fromString(string $level): ?self
    {
        return match (strtolower($level)) {
            'low' => self::Low,
            'medium' => self::Medium,
            'high' => self::High,
            'critical' => self::Critical,
            default => null,
        };
    }

    /**
     * Get all levels
     */
    public static function all(): array
    {
        return self::cases();
    }

    /**
     * Get dangerous levels (High and Critical)
     */
    public static function dangerousLevels(): array
    {
        return [self::High, self::Critical];
    }

    /**
     * Get levels that require step-up authentication
     */
    public static function stepUpAuthLevels(): array
    {
        return [self::High, self::Critical];
    }
}
