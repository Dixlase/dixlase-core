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
 *       (see LICENSE.commercial, or contact office@exc-d.com).
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
 * Gender Enum
 *
 * 性別を表す列挙型。データベースには数値として保存される。
 * 翻訳キーは common.gender_* を使用。
 */
enum Gender: int
{
    case MALE = 1;
    case FEMALE = 2;
    case NON_BINARY = 3;
    case OTHER = 4;
    case PREFER_NOT_TO_SAY = 9;

    /**
     * 翻訳キーを取得
     */
    public function label(): string
    {
        return match ($this) {
            self::MALE => __('common.gender_male'),
            self::FEMALE => __('common.gender_female'),
            self::NON_BINARY => __('common.gender_non_binary'),
            self::OTHER => __('common.gender_other'),
            self::PREFER_NOT_TO_SAY => __('common.prefer_not_to_say'),
        };
    }

    /**
     * 翻訳キーの文字列を取得（__()なし）
     */
    public function translationKey(): string
    {
        return match ($this) {
            self::MALE => 'common.gender_male',
            self::FEMALE => 'common.gender_female',
            self::NON_BINARY => 'common.gender_non_binary',
            self::OTHER => 'common.gender_other',
            self::PREFER_NOT_TO_SAY => 'common.prefer_not_to_say',
        };
    }

    /**
     * 値から名前を取得（デバッグ用）
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * 全ての選択肢を配列で取得（セレクトボックス用）
     *
     * @return array<int, string>
     */
    public static function options(): array
    {
        return [
            self::MALE->value => self::MALE->label(),
            self::FEMALE->value => self::FEMALE->label(),
            self::NON_BINARY->value => self::NON_BINARY->label(),
            self::OTHER->value => self::OTHER->label(),
            self::PREFER_NOT_TO_SAY->value => self::PREFER_NOT_TO_SAY->label(),
        ];
    }

    /**
     * 基本的な選択肢のみ取得（男性・女性のみ）
     *
     * @return array<int, string>
     */
    public static function basicOptions(): array
    {
        return [
            self::MALE->value => self::MALE->label(),
            self::FEMALE->value => self::FEMALE->label(),
        ];
    }

    /**
     * 拡張選択肢を取得（男性・女性・その他・回答しない）
     *
     * @return array<int, string>
     */
    public static function extendedOptions(): array
    {
        return [
            self::MALE->value => self::MALE->label(),
            self::FEMALE->value => self::FEMALE->label(),
            self::OTHER->value => self::OTHER->label(),
            self::PREFER_NOT_TO_SAY->value => self::PREFER_NOT_TO_SAY->label(),
        ];
    }

    /**
     * 値から対応するEnumケースを取得（nullセーフ）
     */
    public static function fromValue(?int $value): ?self
    {
        if ($value === null) {
            return null;
        }

        return self::tryFrom($value);
    }

    /**
     * 文字列名からEnumケースを取得
     */
    public static function fromName(string $name): ?self
    {
        return match (strtoupper($name)) {
            'MALE' => self::MALE,
            'FEMALE' => self::FEMALE,
            'NON_BINARY', 'NONBINARY' => self::NON_BINARY,
            'OTHER' => self::OTHER,
            'PREFER_NOT_TO_SAY', 'PREFERNOTTOSAY' => self::PREFER_NOT_TO_SAY,
            default => null,
        };
    }

    /**
     * 全てのケースを配列で取得
     *
     * @return array<self>
     */
    public static function all(): array
    {
        return self::cases();
    }

    /**
     * 値の配列を取得
     *
     * @return array<int>
     */
    public static function values(): array
    {
        return array_map(fn ($case) => $case->value, self::cases());
    }
}
