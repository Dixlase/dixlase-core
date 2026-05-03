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
 * @internal Core use only. Do not reference from plugins/themes
 *
 * Application environment definition
 */
enum AppEnvironment: string
{
    case Local = 'local';
    case Staging = 'staging';
    case Production = 'production';

    /**
     * Get translation key
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
     * Get description translation key
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
     * Get icon class
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
     * Get color name (for radio-card-group)
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
     * Whether it is production environment
     */
    public function isProduction(): bool
    {
        return $this === self::Production;
    }

    /**
     * Whether it is development environment
     */
    public function isDevelopment(): bool
    {
        return $this === self::Local;
    }

    /**
     * Get options array for radio-card-group component
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
     * Get all environments
     */
    public static function all(): array
    {
        return self::cases();
    }

    /**
     * Get default value
     */
    public static function default(): self
    {
        return self::Production;
    }

    /**
     * Get environment from string
     */
    public static function fromString(?string $value): ?self
    {
        if ($value === null) {
            return null;
        }

        return self::tryFrom($value);
    }
}
