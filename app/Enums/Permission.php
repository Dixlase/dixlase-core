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

declare(strict_types=1);

namespace App\Enums;

/**
 * Permission Enum
 *
 * Defines all available permissions in the system.
 * Each permission has a minimum required role level.
 *
 * Format: RESOURCE_ACTION
 */
enum Permission: string
{
    // =========================================================================
    // Dashboard
    // =========================================================================
    case DASHBOARD_VIEW = 'dashboard.view';

    // =========================================================================
    // Members Management
    // =========================================================================
    case MEMBERS_VIEW = 'members.view';
    case MEMBERS_CREATE = 'members.create';
    case MEMBERS_UPDATE = 'members.update';
    case MEMBERS_DELETE = 'members.delete';
    case MEMBERS_MANAGE_ROLES = 'members.manage_roles';

    // =========================================================================
    // Settings
    // =========================================================================
    case SETTINGS_VIEW = 'settings.view';
    case SETTINGS_BASE = 'settings.base';
    case SETTINGS_SECURITY = 'settings.security';
    case SETTINGS_MEMBERS = 'settings.members';
    case SETTINGS_SYSTEM = 'settings.system';
    case SETTINGS_API = 'settings.api';

    // =========================================================================
    // Plugins
    // =========================================================================
    case PLUGINS_VIEW = 'plugins.view';
    case PLUGINS_INSTALL = 'plugins.install';
    case PLUGINS_UNINSTALL = 'plugins.uninstall';
    case PLUGINS_ENABLE = 'plugins.enable';
    case PLUGINS_DISABLE = 'plugins.disable';
    case PLUGINS_SETTINGS = 'plugins.settings';

    // =========================================================================
    // Themes
    // =========================================================================
    case THEMES_VIEW = 'themes.view';
    case THEMES_INSTALL = 'themes.install';
    case THEMES_UNINSTALL = 'themes.uninstall';
    case THEMES_ENABLE = 'themes.enable';
    case THEMES_DISABLE = 'themes.disable';
    case THEMES_SETTINGS = 'themes.settings';

    // =========================================================================
    // Media
    // =========================================================================
    case MEDIA_VIEW = 'media.view';
    case MEDIA_UPLOAD = 'media.upload';
    case MEDIA_DELETE = 'media.delete';

    // =========================================================================
    // Audit Logs
    // =========================================================================
    case AUDIT_LOGS_VIEW = 'audit_logs.view';
    case AUDIT_LOGS_EXPORT = 'audit_logs.export';

    // =========================================================================
    // System
    // =========================================================================
    case SYSTEM_LOGS_VIEW = 'system.logs_view';
    case SYSTEM_LOGS_DELETE = 'system.logs_delete';
    case SYSTEM_CACHE_CLEAR = 'system.cache_clear';
    case SYSTEM_MAINTENANCE = 'system.maintenance';
    case SYSTEM_BACKUP = 'system.backup';
    case SYSTEM_RESTORE = 'system.restore';

    // =========================================================================
    // API
    // =========================================================================
    case API_KEYS_VIEW = 'api.keys_view';
    case API_KEYS_CREATE = 'api.keys_create';
    case API_KEYS_DELETE = 'api.keys_delete';

    // =========================================================================
    // Webhooks
    // =========================================================================
    case WEBHOOKS_VIEW = 'webhooks.view';
    case WEBHOOKS_CREATE = 'webhooks.create';
    case WEBHOOKS_UPDATE = 'webhooks.update';
    case WEBHOOKS_DELETE = 'webhooks.delete';

    /**
     * Get the minimum required role for this permission
     */
    public function minimumRole(): MemberRole
    {
        return match ($this) {
            // Super Admin only
            self::SETTINGS_SECURITY,
            self::SETTINGS_SYSTEM,
            self::SETTINGS_API,
            self::PLUGINS_INSTALL,
            self::PLUGINS_UNINSTALL,
            self::THEMES_INSTALL,
            self::THEMES_UNINSTALL,
            self::SYSTEM_LOGS_DELETE,
            self::SYSTEM_MAINTENANCE,
            self::SYSTEM_BACKUP,
            self::SYSTEM_RESTORE,
            self::API_KEYS_CREATE,
            self::API_KEYS_DELETE,
            self::MEMBERS_MANAGE_ROLES,
            self::MEMBERS_DELETE,
            self::AUDIT_LOGS_EXPORT, => MemberRole::SUPER_ADMIN,

            // Admin
            self::SETTINGS_BASE,
            self::SETTINGS_MEMBERS,
            self::PLUGINS_ENABLE,
            self::PLUGINS_DISABLE,
            self::PLUGINS_SETTINGS,
            self::THEMES_ENABLE,
            self::THEMES_DISABLE,
            self::THEMES_SETTINGS,
            self::MEMBERS_CREATE,
            self::MEMBERS_UPDATE,
            self::WEBHOOKS_CREATE,
            self::WEBHOOKS_UPDATE,
            self::WEBHOOKS_DELETE,
            self::API_KEYS_VIEW, => MemberRole::ADMIN,

            // Editor
            self::SETTINGS_VIEW,
            self::PLUGINS_VIEW,
            self::THEMES_VIEW,
            self::MEMBERS_VIEW,
            self::MEDIA_DELETE,
            self::WEBHOOKS_VIEW,
            self::AUDIT_LOGS_VIEW,
            self::SYSTEM_LOGS_VIEW,
            self::SYSTEM_CACHE_CLEAR, => MemberRole::EDITOR,

            // Contributor
            self::MEDIA_UPLOAD,
            self::MEDIA_VIEW, => MemberRole::CONTRIBUTOR,

            // Guest (everyone)
            self::DASHBOARD_VIEW, => MemberRole::GUEST,
        };
    }

    /**
     * Get the translation key for this permission
     */
    public function translationKey(): string
    {
        return 'permissions.'.$this->value;
    }

    /**
     * Get the translated label
     */
    public function label(): string
    {
        return __($this->translationKey());
    }

    /**
     * Get the resource name (first part of the permission)
     */
    public function resource(): string
    {
        return explode('.', $this->value)[0];
    }

    /**
     * Get the action name (second part of the permission)
     */
    public function action(): string
    {
        return explode('.', $this->value)[1] ?? '';
    }

    /**
     * Check if this is a dangerous permission (requires extra caution)
     */
    public function isDangerous(): bool
    {
        return in_array($this, [
            self::MEMBERS_DELETE,
            self::MEMBERS_MANAGE_ROLES,
            self::PLUGINS_INSTALL,
            self::PLUGINS_UNINSTALL,
            self::THEMES_INSTALL,
            self::THEMES_UNINSTALL,
            self::SYSTEM_BACKUP,
            self::SYSTEM_RESTORE,
            self::SYSTEM_MAINTENANCE,
            self::SYSTEM_LOGS_DELETE,
            self::API_KEYS_DELETE,
            self::WEBHOOKS_DELETE,
        ]);
    }

    /**
     * Get all permissions for a resource
     */
    public static function forResource(string $resource): array
    {
        return array_filter(
            self::cases(),
            fn (Permission $p) => $p->resource() === $resource
        );
    }

    /**
     * Get all permissions grouped by resource
     */
    public static function groupedByResource(): array
    {
        $grouped = [];
        foreach (self::cases() as $permission) {
            $resource = $permission->resource();
            if (! isset($grouped[$resource])) {
                $grouped[$resource] = [];
            }
            $grouped[$resource][] = $permission;
        }

        return $grouped;
    }

    /**
     * Get all permissions that a role has access to
     */
    public static function forRole(MemberRole $role): array
    {
        return array_filter(
            self::cases(),
            fn (Permission $p) => $role->value >= $p->minimumRole()->value
        );
    }

    /**
     * Get all dangerous permissions
     */
    public static function dangerous(): array
    {
        return array_filter(
            self::cases(),
            fn (Permission $p) => $p->isDangerous()
        );
    }
}
