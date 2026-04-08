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

namespace App\Events;

/**
 * Dixlase Core Events
 *
 * This class defines all core event names that plugins can listen to.
 * These events provide extension points for backup, deploy, translation,
 * and other core functionalities.
 *
 * Usage:
 * ```php
 * // Listen to an event
 * Event::listen(DixlaseEvents::BACKUP_STARTED, function ($backup) {
 *     // Handle backup started
 * });
 *
 * // Or in EventServiceProvider
 * protected $listen = [
 *     DixlaseEvents::BACKUP_COMPLETED => [
 *         SendBackupNotification::class,
 *     ],
 * ];
 * ```
 *
 * @see docs/events-api-spec.md
 */
final class DixlaseEvents
{
    // =========================================================================
    // Backup Events
    // =========================================================================

    /**
     * Fired when a backup process starts
     * Payload: ['type' => string, 'options' => array]
     */
    public const BACKUP_STARTED = 'dixlase.backup.started';

    /**
     * Fired when a backup process completes successfully
     * Payload: ['type' => string, 'path' => string, 'size' => int, 'duration' => float]
     */
    public const BACKUP_COMPLETED = 'dixlase.backup.completed';

    /**
     * Fired when a backup process fails
     * Payload: ['type' => string, 'error' => string, 'exception' => Throwable|null]
     */
    public const BACKUP_FAILED = 'dixlase.backup.failed';

    /**
     * Fired before backup cleanup (old backups deletion)
     * Payload: ['files' => array, 'retention_days' => int]
     */
    public const BACKUP_CLEANUP_STARTED = 'dixlase.backup.cleanup.started';

    /**
     * Fired after backup cleanup completes
     * Payload: ['deleted_count' => int, 'freed_bytes' => int]
     */
    public const BACKUP_CLEANUP_COMPLETED = 'dixlase.backup.cleanup.completed';

    /**
     * Fired when a backup restore starts
     * Payload: ['path' => string, 'type' => string]
     */
    public const BACKUP_RESTORE_STARTED = 'dixlase.backup.restore.started';

    /**
     * Fired when a backup restore completes
     * Payload: ['path' => string, 'type' => string, 'duration' => float]
     */
    public const BACKUP_RESTORE_COMPLETED = 'dixlase.backup.restore.completed';

    /**
     * Fired when a backup restore fails
     * Payload: ['path' => string, 'error' => string]
     */
    public const BACKUP_RESTORE_FAILED = 'dixlase.backup.restore.failed';

    // =========================================================================
    // Backup Encryption Events
    // =========================================================================

    /**
     * Fired when backup file encryption starts
     * Payload: ['path' => string, 'algorithm' => string]
     */
    public const BACKUP_ENCRYPTING = 'dixlase.backup.encrypting';

    /**
     * Fired when backup file encryption completes
     * Payload: ['path' => string, 'algorithm' => string, 'original_size' => int, 'encrypted_size' => int]
     */
    public const BACKUP_ENCRYPTED = 'dixlase.backup.encrypted';

    /**
     * Fired when backup file encryption fails
     * Payload: ['path' => string, 'error' => string]
     */
    public const BACKUP_ENCRYPTION_FAILED = 'dixlase.backup.encryption.failed';

    // =========================================================================
    // Backup Verification Events
    // =========================================================================

    /**
     * Fired when backup file verification starts
     * Payload: ['path' => string]
     */
    public const BACKUP_VERIFYING = 'dixlase.backup.verifying';

    /**
     * Fired when backup file verification completes
     * Payload: ['path' => string, 'hash' => string]
     */
    public const BACKUP_VERIFIED = 'dixlase.backup.verified';

    /**
     * Fired when backup file verification fails
     * Payload: ['path' => string, 'reason' => string]
     */
    public const BACKUP_VERIFICATION_FAILED = 'dixlase.backup.verification.failed';

    // =========================================================================
    // Deploy Events
    // =========================================================================

    /**
     * Fired before deployment starts
     * Payload: ['environment' => string, 'targets' => array, 'options' => array]
     */
    public const DEPLOY_BEFORE = 'dixlase.deploy.before';

    /**
     * Fired after deployment completes successfully
     * Payload: ['environment' => string, 'targets' => array, 'duration' => float]
     */
    public const DEPLOY_AFTER = 'dixlase.deploy.after';

    /**
     * Fired when deployment fails
     * Payload: ['environment' => string, 'error' => string, 'exception' => Throwable|null]
     */
    public const DEPLOY_FAILED = 'dixlase.deploy.failed';

    /**
     * Fired before file sync during deployment
     * Payload: ['environment' => string, 'target' => string, 'files' => array]
     */
    public const DEPLOY_SYNC_BEFORE = 'dixlase.deploy.sync.before';

    /**
     * Fired after file sync during deployment
     * Payload: ['environment' => string, 'target' => string, 'synced_count' => int]
     */
    public const DEPLOY_SYNC_AFTER = 'dixlase.deploy.sync.after';

    /**
     * Fired before database sync during deployment
     * Payload: ['environment' => string, 'tables' => array]
     */
    public const DEPLOY_DATABASE_BEFORE = 'dixlase.deploy.database.before';

    /**
     * Fired after database sync during deployment
     * Payload: ['environment' => string, 'tables' => array, 'rows_affected' => int]
     */
    public const DEPLOY_DATABASE_AFTER = 'dixlase.deploy.database.after';

    // =========================================================================
    // Translation Events
    // =========================================================================

    /**
     * Fired when locale is changed
     * Payload: ['locale' => string, 'previous' => string]
     */
    public const LOCALE_CHANGED = 'dixlase.locale.changed';

    /**
     * Fired when a translatable model is retrieved
     * Payload: [Model $model]
     */
    public const TRANSLATION_MODEL_RETRIEVED = 'translation.model.retrieved';

    /**
     * Fired when a translatable model is being saved
     * Payload: [Model $model]
     */
    public const TRANSLATION_MODEL_SAVING = 'translation.model.saving';

    /**
     * Fired when a translatable model is saved
     * Payload: [Model $model]
     */
    public const TRANSLATION_MODEL_SAVED = 'translation.model.saved';

    /**
     * Fired when a translatable model is deleted
     * Payload: [Model $model]
     */
    public const TRANSLATION_MODEL_DELETED = 'translation.model.deleted';

    /**
     * Fired when a translation field is updated
     * Payload: [Model $model, string $field, mixed $value, string $locale]
     */
    public const TRANSLATION_FIELD_UPDATED = 'translation.field.updated';

    /**
     * Fired when a translation field is deleted
     * Payload: [Model $model, string $field, string|null $locale]
     */
    public const TRANSLATION_FIELD_DELETED = 'translation.field.deleted';

    // =========================================================================
    // Plugin Events
    // =========================================================================

    /**
     * Fired when a plugin is being installed
     * Payload: ['plugin' => string, 'version' => string]
     */
    public const PLUGIN_INSTALLING = 'dixlase.plugin.installing';

    /**
     * Fired when a plugin is installed
     * Payload: ['plugin' => string, 'version' => string]
     */
    public const PLUGIN_INSTALLED = 'dixlase.plugin.installed';

    /**
     * Fired when a plugin is being activated
     * Payload: ['plugin' => string]
     */
    public const PLUGIN_ACTIVATING = 'dixlase.plugin.activating';

    /**
     * Fired when a plugin is activated
     * Payload: ['plugin' => string]
     */
    public const PLUGIN_ACTIVATED = 'dixlase.plugin.activated';

    /**
     * Fired when a plugin is being deactivated
     * Payload: ['plugin' => string]
     */
    public const PLUGIN_DEACTIVATING = 'dixlase.plugin.deactivating';

    /**
     * Fired when a plugin is deactivated
     * Payload: ['plugin' => string]
     */
    public const PLUGIN_DEACTIVATED = 'dixlase.plugin.deactivated';

    /**
     * Fired when a plugin is being uninstalled
     * Payload: ['plugin' => string, 'delete_data' => bool]
     */
    public const PLUGIN_UNINSTALLING = 'dixlase.plugin.uninstalling';

    /**
     * Fired when a plugin is uninstalled
     * Payload: ['plugin' => string]
     */
    public const PLUGIN_UNINSTALLED = 'dixlase.plugin.uninstalled';

    /**
     * Fired when a plugin is being updated
     * Payload: ['plugin' => string, 'from_version' => string, 'to_version' => string]
     */
    public const PLUGIN_UPDATING = 'dixlase.plugin.updating';

    /**
     * Fired when a plugin is updated
     * Payload: ['plugin' => string, 'from_version' => string, 'to_version' => string]
     */
    public const PLUGIN_UPDATED = 'dixlase.plugin.updated';

    // =========================================================================
    // Theme Events
    // =========================================================================

    /**
     * Fired when a theme is being activated
     * Payload: ['theme' => string, 'previous' => string|null]
     */
    public const THEME_ACTIVATING = 'dixlase.theme.activating';

    /**
     * Fired when a theme is activated
     * Payload: ['theme' => string, 'previous' => string|null]
     */
    public const THEME_ACTIVATED = 'dixlase.theme.activated';

    // =========================================================================
    // Cache Events
    // =========================================================================

    /**
     * Fired when cache is being cleared
     * Payload: ['type' => string] (all, config, route, view, etc.)
     */
    public const CACHE_CLEARING = 'dixlase.cache.clearing';

    /**
     * Fired when cache is cleared
     * Payload: ['type' => string]
     */
    public const CACHE_CLEARED = 'dixlase.cache.cleared';

    // =========================================================================
    // Maintenance Events
    // =========================================================================

    /**
     * Fired when maintenance mode is enabled
     * Payload: ['secret' => string|null, 'retry' => int|null]
     */
    public const MAINTENANCE_ENABLED = 'dixlase.maintenance.enabled';

    /**
     * Fired when maintenance mode is disabled
     * Payload: []
     */
    public const MAINTENANCE_DISABLED = 'dixlase.maintenance.disabled';

    // =========================================================================
    // Security Events
    // =========================================================================

    /**
     * Fired when file integrity scan starts
     * Payload: ['scope' => string]
     */
    public const INTEGRITY_SCAN_STARTED = 'dixlase.integrity.scan.started';

    /**
     * Fired when file integrity scan completes
     * Payload: ['scope' => string, 'status' => string, 'changes' => array]
     */
    public const INTEGRITY_SCAN_COMPLETED = 'dixlase.integrity.scan.completed';

    /**
     * Fired when suspicious activity is detected
     * Payload: ['type' => string, 'details' => array]
     */
    public const SECURITY_ALERT = 'dixlase.security.alert';

    /**
     * Fired when bot behavior is detected at login
     * Payload: SecurityAlertEvent
     */
    public const BOT_DETECTED = 'dixlase.security.bot.detected';

    /**
     * Fired when login anomaly is detected
     * Payload: SecurityAlertEvent
     */
    public const LOGIN_ANOMALY_DETECTED = 'dixlase.security.login.anomaly';

    // =========================================================================
    // Audit Events
    // =========================================================================

    /**
     * Fired when a new audit log record is created
     * Payload: AuditLogCreated event
     */
    public const AUDIT_LOG_CREATED = 'dixlase.audit.log.created';

    // =========================================================================
    // AI Events (reserved for future use)
    // =========================================================================

    /**
     * Fired when an AI plugin performs an audited operation
     * Payload: AuditLogCreated event
     */
    public const AI_OPERATION_LOGGED = 'dixlase.ai.operation.logged';

    // =========================================================================
    // Helper Methods
    // =========================================================================

    /**
     * Get all event names
     */
    public static function all(): array
    {
        $reflection = new \ReflectionClass(self::class);

        return array_values($reflection->getConstants());
    }

    /**
     * Get events by category
     *
     * @param  string  $category  (backup, deploy, translation, plugin, theme, cache, maintenance, security)
     */
    public static function byCategory(string $category): array
    {
        $prefix = match ($category) {
            'backup' => 'dixlase.backup.',
            'deploy' => 'dixlase.deploy.',
            'translation' => 'translation.',
            'locale' => 'dixlase.locale.',
            'plugin' => 'dixlase.plugin.',
            'theme' => 'dixlase.theme.',
            'cache' => 'dixlase.cache.',
            'maintenance' => 'dixlase.maintenance.',
            'security' => 'dixlase.security.',
            'integrity' => 'dixlase.integrity.',
            'audit' => 'dixlase.audit.',
            'ai' => 'dixlase.ai.',
            default => $category,
        };

        return array_filter(self::all(), fn ($event) => str_starts_with($event, $prefix));
    }
}
