<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
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
 * @api プラグイン/テーマから使用可能な安定APIです
 *
 * 二要素認証方式定義
 */
enum TwoFaMethod: int
{
    case EMAIL = 0;
    case PASSKEY = 1;

    public function label(): string
    {
        return match ($this) {
            self::EMAIL => __('two_fa.method.email'),
            self::PASSKEY => __('two_fa.method.passkey'),
        };
    }

    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }

    public static function translationOptions(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->translationKey();
        }

        return $options;
    }

    public function translationKey(): string
    {
        return match ($this) {
            self::EMAIL => 'two_fa.method.email',
            self::PASSKEY => 'two_fa.method.passkey',
        };
    }

    /**
     * セキュリティレベルを取得（5段階評価）
     */
    public function securityLevel(): int
    {
        return match ($this) {
            self::PASSKEY => 5,  // 最も安全
            self::EMAIL => 3,    // 中程度のセキュリティ
        };
    }

    /**
     * セキュリティレベルのラベル
     */
    public function securityLevelLabel(): string
    {
        return match ($this) {
            self::PASSKEY => __('two_fa.security.level.very_high'),
            self::EMAIL => __('two_fa.security.level.medium'),
        };
    }

    /**
     * セキュリティの説明
     */
    public function securityDescription(): string
    {
        return match ($this) {
            self::PASSKEY => __('two_fa.security.description.passkey'),
            self::EMAIL => __('two_fa.security.description.email'),
        };
    }

    /**
     * 推奨される認証方法かどうか
     */
    public function isRecommended(): bool
    {
        return match ($this) {
            self::PASSKEY => true,
            self::EMAIL => false,
        };
    }

    public static function forGlobalSettings(): array
    {
        return self::cases();
    }
}
