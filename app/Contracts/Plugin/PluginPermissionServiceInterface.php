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

namespace App\Contracts\Plugin;

/**
 * Contract for plugin permission management service
 *
 * Reads the permissions section of plugin.json and
 * provides plugin permission checks, summary retrieval, violation recording, etc.
 */
interface PluginPermissionServiceInterface
{
    /**
     * Check plugin permission
     *
     * @param  string  $pluginSlug  Plugin slug (e.g., dixlase-inquiry)
     * @param  string  $permission  Permission key (e.g., mail.send, database.own_tables)
     */
    public function check(string $pluginSlug, string $permission): bool;

    /**
     * Check if plugin has a specific permission (alias)
     */
    public function has(string $pluginSlug, string $permission): bool;

    /**
     * Get all permissions for plugin
     */
    public function getPermissions(string $pluginSlug): ?array;

    /**
     * Get the _optional permission list for plugin
     *
     * @return array<string> List of optional permission keys
     */
    public function getOptionalPermissions(string $pluginSlug): array;

    /**
     * Get the _notes for plugin
     *
     * @return array{ja?: string, en?: string} Description of permission usage reason
     */
    public function getPermissionNotes(string $pluginSlug): array;

    /**
     * Determine if permission key is optional
     */
    public function isOptionalPermission(string $pluginSlug, string $permissionKey): bool;

    /**
     * Check if plugin can access a specific Core table
     *
     * @param  string  $table  Table name
     * @param  string  $access  Access type (read, write)
     */
    public function canAccessCoreTable(string $pluginSlug, string $table, string $access = 'read'): bool;

    /**
     * Check if plugin can access another plugin's content
     *
     * @param  string  $targetPlugin  Target plugin to access
     * @param  string  $access  Access type (read, write)
     */
    public function canAccessOtherPlugin(string $pluginSlug, string $targetPlugin, string $access = 'read'): bool;

    /**
     * Get plugin permission summary (for admin panel display)
     */
    public function getSummary(string $pluginSlug): array;

    /**
     * Get plugin signature information
     */
    public function getSignatureInfo(string $pluginSlug): array;

    /**
     * Calculate unified risk level from declared permissions and mismatch information
     *
     * @param  array  $declaredPermissions  permissions from plugin.json
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
     * @param  string|null  $pluginSlug  To clear only a specific plugin
     */
    public function clearCache(?string $pluginSlug = null): void;

    /**
     * Log permission violation
     *
     * @param  string  $action  Action that was attempted to execute
     */
    public function logViolation(string $pluginSlug, string $permission, string $action = ''): void;

    /**
     * Perform permission check and throw exception on violation
     *
     * @throws \App\Exceptions\PluginPermissionException
     */
    public function enforce(string $pluginSlug, string $permission, string $action = ''): void;
}
