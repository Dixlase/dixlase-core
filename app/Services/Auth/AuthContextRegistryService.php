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

namespace App\Services\Auth;

/**
 * Authentication context registry
 *
 * Registry service that allows plugins to register their own authentication contexts
 * (routes, translation prefixes, etc.)
 */
class AuthContextRegistryService
{
    /**
     * Registered authentication contexts
     *
     * @var array<string, array>
     */
    protected static array $contexts = [];

    /**
     * Default administrator context
     */
    protected static array $defaultAdminContext = [
        'guard' => 'member',
        'routes' => [
            'login' => 'admin.login',
            'dashboard' => 'admin.dashboard',
            'two_fa.email' => 'admin.two-fa.email.show',
            'two_fa.recovery_code' => 'admin.two-fa.recovery-code.show',
        ],
        'translation_prefix' => 'admin/members',
        'entity_type' => 'member',
    ];

    /**
     * Register an authentication context
     *
     * @param  string  $context  Context name (e.g., 'user', 'admin')
     * @param  array  $config  Settings array
     */
    public static function register(string $context, array $config): void
    {
        static::$contexts[$context] = array_merge([
            'guard' => $context,
            'routes' => [],
            'translation_prefix' => null,
            'entity_type' => $context,
        ], $config);
    }

    /**
     * Get a registered context
     *
     * @param  string  $context  Context name
     */
    public static function get(string $context): ?array
    {
        // Administrator context uses default settings
        if ($context === 'admin') {
            return static::$defaultAdminContext;
        }

        return static::$contexts[$context] ?? null;
    }

    /**
     * Get route name
     *
     * @param  string  $context  Context name
     * @param  string  $routeKey  Route key (e.g., 'login', 'dashboard')
     */
    public static function getRoute(string $context, string $routeKey): ?string
    {
        $config = static::get($context);

        return $config['routes'][$routeKey] ?? null;
    }

    /**
     * Get translation prefix
     *
     * @param  string  $context  Context name
     */
    public static function getTranslationPrefix(string $context): ?string
    {
        $config = static::get($context);

        return $config['translation_prefix'] ?? null;
    }

    /**
     * Get entity type
     *
     * @param  string  $context  Context name
     */
    public static function getEntityType(string $context): ?string
    {
        $config = static::get($context);

        return $config['entity_type'] ?? null;
    }

    /**
     * Get guard name
     *
     * @param  string  $context  Context name
     */
    public static function getGuard(string $context): ?string
    {
        $config = static::get($context);

        return $config['guard'] ?? null;
    }

    /**
     * Get all registered contexts
     */
    public static function all(): array
    {
        return array_merge(
            ['admin' => static::$defaultAdminContext],
            static::$contexts
        );
    }

    /**
     * Check if a context is registered
     *
     * @param  string  $context  Context name
     */
    public static function has(string $context): bool
    {
        return $context === 'admin' || isset(static::$contexts[$context]);
    }

    /**
     * Clear all contexts (for testing)
     */
    public static function clear(): void
    {
        static::$contexts = [];
    }
}
