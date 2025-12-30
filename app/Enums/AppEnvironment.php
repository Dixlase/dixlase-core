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

enum AppEnvironment: string
{
    case Local = 'local';
    case Staging = 'staging';
    case Production = 'production';

    /**
     * 翻訳キーを取得
     */
    public function translationKey(): string
    {
        return match ($this) {
            self::Local => 'admin/settings/security/environment.env_options.local',
            self::Staging => 'admin/settings/security/environment.env_options.staging',
            self::Production => 'admin/settings/security/environment.env_options.production',
        };
    }

    /**
     * 説明の翻訳キーを取得
     */
    public function descriptionKey(): string
    {
        return match ($this) {
            self::Local => 'admin/settings/security/environment.env_descriptions.local',
            self::Staging => 'admin/settings/security/environment.env_descriptions.staging',
            self::Production => 'admin/settings/security/environment.env_descriptions.production',
        };
    }

    /**
     * アイコンクラスを取得
     */
    public function iconClass(): string
    {
        return match ($this) {
            self::Local => 'fas fa-laptop-code',
            self::Staging => 'fas fa-flask',
            self::Production => 'fas fa-server',
        };
    }

    /**
     * 色名を取得（radio-card-group用）
     */
    public function colorName(): string
    {
        return match ($this) {
            self::Local => 'yellow',
            self::Staging => 'blue',
            self::Production => 'red',
        };
    }

    /**
     * 本番環境かどうか
     */
    public function isProduction(): bool
    {
        return $this === self::Production;
    }

    /**
     * 開発環境かどうか
     */
    public function isDevelopment(): bool
    {
        return $this === self::Local;
    }

    /**
     * radio-card-groupコンポーネント用のオプション配列を取得
     */
    public static function getRadioCardOptions(): array
    {
        $options = [];
        foreach (self::cases() as $env) {
            $options[] = [
                'value' => $env->value,
                'label' => $env->translationKey(),
                'description' => $env->descriptionKey(),
                'icon' => $env->iconClass(),
                'color' => $env->colorName(),
            ];
        }
        return $options;
    }

    /**
     * すべての環境を取得
     */
    public static function all(): array
    {
        return self::cases();
    }

    /**
     * デフォルト値を取得
     */
    public static function default(): self
    {
        return self::Production;
    }

    /**
     * 文字列から環境を取得
     */
    public static function fromString(?string $value): ?self
    {
        if ($value === null) {
            return null;
        }

        return self::tryFrom($value);
    }
}
