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

use App\Contracts\Backup\BackupServiceInterface;
use App\DTO\Backup\BackupResultDTO;
use App\Models\BackupRecord;

/**
 * コアバックアップサービス
 *
 * 手動バックアップのデフォルト実装です。
 * 対象: データベース、メディア、storage/app/private、custom/、（オプション）storage/logs
 *
 * スケジュール実行・暗号化・リモートストレージ等の高度な機能は
 * バックアッププラグインで上書きします。
 */
class CoreBackupService implements BackupServiceInterface
{
    /**
     * デフォルトでバックアップ対象に含まれない（オプション）対象
     */
    private const OPTIONAL_TARGETS = [
        BackupServiceInterface::TARGET_LOGS,
    ];

    public function backup(array $targets, array $options = []): BackupResultDTO
    {
        // Phase B で実装予定
        return BackupResultDTO::failure('CoreBackupService::backup() is not yet implemented');
    }

    public function getAvailableTargets(): array
    {
        return [
            BackupServiceInterface::TARGET_DATABASE,
            BackupServiceInterface::TARGET_MEDIA,
            BackupServiceInterface::TARGET_PRIVATE,
            BackupServiceInterface::TARGET_CUSTOM,
            BackupServiceInterface::TARGET_LOGS,
        ];
    }

    public function getDefaultTargets(): array
    {
        return array_values(array_diff(
            $this->getAvailableTargets(),
            self::OPTIONAL_TARGETS,
        ));
    }

    public function delete(BackupRecord $record): bool
    {
        // Phase B で実装予定
        return false;
    }
}
