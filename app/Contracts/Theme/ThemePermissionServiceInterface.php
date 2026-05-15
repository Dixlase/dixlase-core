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
 *       (see LICENSE.commercial, or contact info@dixlase.org).
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

namespace App\Contracts\Theme;

/**
 * Theme permission management service interface
 *
 * Reads the permissions section from theme.json and
 * provides theme permission checks, summary retrieval, violation logging, etc.
 */
interface ThemePermissionServiceInterface
{
    /**
     * Check theme permission
     *
     * @param  string  $themeSlug  Theme slug (e.g., dixlase-one-page)
     * @param  string  $permission  Permission key (e.g., assets.custom_js, database.own_tables)
     */
    public function check(string $themeSlug, string $permission): bool;

    /**
     * Check if theme has a specific permission (alias)
     */
    public function has(string $themeSlug, string $permission): bool;

    /**
     * Get all theme permissions
     */
    public function getPermissions(string $themeSlug): ?array;

    /**
     * Get theme permission summary (for admin panel display)
     */
    public function getSummary(string $themeSlug): array;

    /**
     * Get theme signature information
     */
    public function getSignatureInfo(string $themeSlug): array;

    /**
     * Calculate unified risk level from declared permissions and mismatch information
     *
     * @param  array  $declaredPermissions  Permissions from theme.json
     * @param  array  $mismatches  List of permission mismatches
     * @return array{level: string, reasons: array, score: int}
     */
    public function calculateUnifiedRiskLevel(array $declaredPermissions, array $mismatches = []): array;

    /**
     * Calculate risk level and reason
     *
     * @deprecated Use calculateUnifiedRiskLevel() instead.
     *
     * @return array{level: string, reasons: array, score: int}
     */
    public function calculateRiskLevelWithReasons(array $permissions): array;

    /**
     * Clear cache
     *
     * @param  string|null  $themeSlug  If clearing only a specific theme
     */
    public function clearCache(?string $themeSlug = null): void;

    /**
     * Log permission violation
     *
     * @param  string  $action  Action that was attempted
     */
    public function logViolation(string $themeSlug, string $permission, string $action = ''): void;
}
