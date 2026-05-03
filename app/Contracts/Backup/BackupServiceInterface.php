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
