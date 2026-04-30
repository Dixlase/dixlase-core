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
 * バックアップサービスインターフェース
 *
 * バックアップの作成・削除・対象列挙を提供します。
 * デフォルト実装（CoreBackupService）は手動バックアップのみをサポートします。
 * スケジュール実行・暗号化・リモートストレージ等の高度な機能は
 * バックアッププラグインで上書きします。
 */
interface BackupServiceInterface
{
    /**
     * バックアップ対象: データベース全体
     */
    public const TARGET_DATABASE = 'database';

    /**
     * バックアップ対象: メディア（アップロードファイル）
     */
    public const TARGET_MEDIA = 'media';

    /**
     * バックアップ対象: storage/app/private（ページ等のファイル保存コンテンツ）
     */
    public const TARGET_PRIVATE = 'private';

    /**
     * バックアップ対象: custom/（サイト固有カスタマイズ）
     */
    public const TARGET_CUSTOM = 'custom';

    /**
     * バックアップ対象: storage/logs（オプション、デフォルト OFF）
     */
    public const TARGET_LOGS = 'logs';

    /**
     * バックアップを実行
     *
     * @param  string[]  $targets  バックアップ対象（TARGET_* 定数の配列）
     * @param  array<string,mixed>  $options  追加オプション（例: ['retention_days' => 30]）
     */
    public function backup(array $targets, array $options = []): BackupResultDTO;

    /**
     * 利用可能なバックアップ対象の一覧を取得
     *
     * @return string[] TARGET_* 定数の配列
     */
    public function getAvailableTargets(): array;

    /**
     * デフォルトのバックアップ対象を取得（オプション項目を除く）
     *
     * @return string[] TARGET_* 定数の配列
     */
    public function getDefaultTargets(): array;

    /**
     * バックアップを削除（ファイル + BackupRecord のステータス更新）
     */
    public function delete(BackupRecord $record): bool;
}
