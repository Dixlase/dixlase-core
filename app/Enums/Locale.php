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

enum Locale: string
{
    case JAPANESE = 'ja';
    case ENGLISH = 'en';

    /**
     * 表示用ラベルを取得
     *
     * @return string
     */
    public function label(): string
    {
        return match($this) {
            self::JAPANESE => '日本語',
            self::ENGLISH => 'English',
        };
    }

    /**
     * 全ての言語オプションを取得
     *
     * @return array
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(function ($case) {
            return [$case->value => $case->label()];
        })->toArray();
    }

    /**
     * 利用可能な言語コードの配列を取得
     *
     * @return array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * デフォルト言語を取得
     *
     * @return self
     */
    public static function default(): self
    {
        return self::JAPANESE;
    }

    /**
     * 設定ファイルから利用可能な言語を取得
     *
     * @return array
     */
    public static function availableOptions(): array
    {
        $available = config('admin.locale.available', []);
        $options = [];
        
        foreach (self::cases() as $case) {
            if (isset($available[$case->value])) {
                $options[$case->value] = $available[$case->value]['name'] ?? $case->label();
            }
        }
        
        return $options;
    }

    /**
     * 言語コードが有効かチェック
     *
     * @param string|null $locale
     * @return bool
     */
    public static function isValid(?string $locale): bool
    {
        if ($locale === null) {
            return true; // null は有効（システムデフォルト使用）
        }
        
        return in_array($locale, self::values());
    }
}
