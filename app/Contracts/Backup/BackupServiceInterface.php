<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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

namespace App\Contracts\Backup;

use App\DTO\Backup\BackupResultDTO;
use App\Models\BackupRecord;

/**
 * Backup service interface
 *
 * Provides backup creation, deletion, and target enumeration
 * Default implementation (CoreBackupService) supports manual backups only
 * Advanced features such as scheduled execution, encryption, remote storage, etc.
 * are overridden by backup plugins
 */
interface BackupServiceInterface
{
    /**
     * Backup target: entire database
     */
    public const TARGET_DATABASE = 'database';

    /**
     * Backup target: media (uploaded files)
     */
    public const TARGET_MEDIA = 'media';

    /**
     * Backup target: storage/app/private (file-stored content such as pages)
     */
    public const TARGET_PRIVATE = 'private';

    /**
     * Backup target: custom/ (site-specific customizations)
     */
    public const TARGET_CUSTOM = 'custom';

    /**
     * Backup target: storage/logs (optional, default OFF)
     */
    public const TARGET_LOGS = 'logs';

    /**
     * Backup target: core source tree (app/, bootstrap/, config/,
     * database/{migrations,seeders}/, lang/, public/, resources/,
     * routes/ + the SOURCE_FILES whitelist in CoreSourceSnapshot).
     * Same path set CoreUpdater snapshots before applying a core
     * upgrade — sized to roll a core upgrade back from a stored
     * backup if the snapshot itself was discarded.
     */
    public const TARGET_CORE_SOURCE = 'core_source';

    /**
     * Backup target: every installed plugin's source tree (the whole
     * plugins/ directory). Used by the "take a backup first" path on
     * the admin updates page so a botched plugin update can be
     * restored from this backup even after the rollback snapshot has
     * been discarded.
     */
    public const TARGET_PLUGINS_ALL = 'plugins_all';

    /**
     * Backup target: every installed theme's source tree (the whole
     * themes/ directory). Mirror of TARGET_PLUGINS_ALL for the theme
     * update path.
     */
    public const TARGET_THEMES_ALL = 'themes_all';

    /**
     * Execute backup
     *
     * @param  string[]  $targets  Backup targets (array of TARGET_* constants)
     * @param  array<string,mixed>  $options  Additional options (e.g., ['retention_days' => 30])
     */
    public function backup(array $targets, array $options = []): BackupResultDTO;

    /**
     * Get list of available backup targets
     *
     * @return string[] Array of TARGET_* constants
     */
    public function getAvailableTargets(): array;

    /**
     * Get default backup targets (excluding optional items)
     *
     * @return string[] Array of TARGET_* constants
     */
    public function getDefaultTargets(): array;

    /**
     * Delete backup (file + BackupRecord status update)
     */
    public function delete(BackupRecord $record): bool;
}
