<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
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
 * 操作リスクレベル
 *
 * β版での「強制再認証」機能の基盤として使用
 * 重大操作（Danger Zone）の判定に使用
 */
enum OperationRiskLevel: int
{
    case Low = 0;       // 閲覧・参照のみ
    case Medium = 1;    // 編集・更新
    case High = 2;      // 削除・重要設定変更
    case Critical = 3;  // システム設定・セキュリティ設定・APIキー操作

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
     * Check if this level requires step-up authentication (β版で実装予定)
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
