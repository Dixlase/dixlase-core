<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
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

enum AdminMode: int
{
    case Simple = 0;
    case Advanced = 1;

    /**
     * 翻訳キーを取得
     */
    public function translationKey(): string
    {
        return match ($this) {
            self::Simple => 'install/mode.simple_mode',
            self::Advanced => 'install/mode.advanced_mode',
        };
    }

    /**
     * 説明の翻訳キーを取得
     */
    public function descriptionKey(): string
    {
        return match ($this) {
            self::Simple => 'install/mode.simple_mode_description',
            self::Advanced => 'install/mode.advanced_mode_description',
        };
    }

    /**
     * アイコンクラスを取得
     */
    public function iconClass(): string
    {
        return match ($this) {
            self::Simple => 'fas fa-magic',
            self::Advanced => 'fas fa-cogs',
        };
    }

    /**
     * かんたんモードかどうか
     */
    public function isSimple(): bool
    {
        return $this === self::Simple;
    }

    /**
     * 詳細モードかどうか
     */
    public function isAdvanced(): bool
    {
        return $this === self::Advanced;
    }

    /**
     * デフォルト値を取得
     */
    public static function default(): self
    {
        return self::Simple;
    }

    /**
     * 整数値からモードを取得
     */
    public static function fromInt(?int $value): self
    {
        if ($value === null) {
            return self::default();
        }

        return self::tryFrom($value) ?? self::default();
    }
}
