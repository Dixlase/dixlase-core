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

namespace App\Services\Backup;

use App\Contracts\Backup\RestoreServiceInterface;
use App\DTO\Backup\RestoreResultDTO;
use App\Models\BackupRecord;
use App\Models\RestoreRecord;

/**
 * コア復元サービス
 *
 * バックアップからの復元およびロールバックのデフォルト実装です。
 * 復元前に自動的にセーフティスナップショットを取得します。
 */
class CoreRestoreService implements RestoreServiceInterface
{
    public function restore(BackupRecord $backup, array $targets = [], array $options = []): RestoreResultDTO
    {
        // Phase C で実装予定
        return RestoreResultDTO::failure('CoreRestoreService::restore() is not yet implemented');
    }

    public function rollback(RestoreRecord $restore): RestoreResultDTO
    {
        // Phase C で実装予定
        return RestoreResultDTO::failure('CoreRestoreService::rollback() is not yet implemented');
    }
}
