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

use App\DTO\Backup\RestoreResultDTO;
use App\Models\BackupRecord;
use App\Models\RestoreRecord;

/**
 * Restore service interface
 *
 * Provides restore and rollback from backups.
 * Automatically takes a safety snapshot (backup of current state) before restore,
 * enabling rollback on failure.
 */
interface RestoreServiceInterface
{
    /**
     * Execute restore from backup
     *
     * Automatically takes a safety snapshot before execution.
     *
     * @param  BackupRecord  $backup  Backup to restore from
     * @param  string[]  $targets  Targets to restore (if empty array, all targets included in backup)
     * @param  array<string,mixed>  $options  Additional options (e.g., ['skip_pre_restore_backup' => false])
     */
    public function restore(BackupRecord $backup, array $targets = [], array $options = []): RestoreResultDTO;

    /**
     * Rollback restore (restore from safety snapshot)
     *
     * Can only be executed on RestoreRecord where canRollback() is true.
     */
    public function rollback(RestoreRecord $restore): RestoreResultDTO;
}
