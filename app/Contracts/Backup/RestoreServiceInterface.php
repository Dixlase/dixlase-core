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
 * 復元サービスインターフェース
 *
 * バックアップからの復元およびロールバックを提供します。
 * 復元前に自動的にセーフティスナップショット（現在状態のバックアップ）を取得し、
 * 失敗時のロールバックを可能にします。
 */
interface RestoreServiceInterface
{
    /**
     * バックアップから復元を実行
     *
     * 実行前に自動的にセーフティスナップショットを取得します。
     *
     * @param  BackupRecord  $backup  復元元のバックアップ
     * @param  string[]  $targets  復元する対象（空配列の場合はバックアップに含まれる全対象）
     * @param  array<string,mixed>  $options  追加オプション（例: ['skip_pre_restore_backup' => false]）
     */
    public function restore(BackupRecord $backup, array $targets = [], array $options = []): RestoreResultDTO;

    /**
     * 復元のロールバック（セーフティスナップショットからの復元）
     *
     * canRollback() が true の RestoreRecord にのみ実行可能です。
     */
    public function rollback(RestoreRecord $restore): RestoreResultDTO;
}
