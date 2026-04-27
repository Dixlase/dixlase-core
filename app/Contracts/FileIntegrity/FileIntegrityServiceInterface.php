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
 *       Dixlase Plugin and Theme Exception (see LICENSE
 *       for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE.commercial, or contact office@exc-d.com).
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

namespace App\Contracts\FileIntegrity;

use App\DTO\FileIntegrity\BaselineDTO;
use App\DTO\FileIntegrity\ScanResultDTO;
use App\DTO\FileIntegrity\ScanTargetDTO;

/**
 * ファイル整合性チェックサービスの契約
 *
 * コアおよびプラグインのファイル改ざん検知機能を提供します。
 */
interface FileIntegrityServiceInterface
{
    /**
     * ベースラインを生成
     *
     * @param  ScanTargetDTO  $target  スキャン対象
     */
    public function generateBaseline(ScanTargetDTO $target): BaselineDTO;

    /**
     * ベースラインを保存
     *
     * @param  BaselineDTO  $baseline  ベースライン
     * @param  string  $filename  ファイル名
     */
    public function saveBaseline(BaselineDTO $baseline, string $filename = 'core_hashes.json'): bool;

    /**
     * ベースラインを読み込み
     *
     * @param  string  $filename  ファイル名
     */
    public function loadBaseline(string $filename = 'core_hashes.json'): ?BaselineDTO;

    /**
     * ファイル整合性スキャンを実行
     *
     * @param  ScanTargetDTO  $target  スキャン対象
     * @param  string  $trigger  トリガー（manual, schedule, install, update）
     * @param  string  $initiatedByType  実行者タイプ（system, user）
     * @param  int|null  $initiatedById  実行者ID
     */
    public function scan(
        ScanTargetDTO $target,
        string $trigger = 'manual',
        string $initiatedByType = 'system',
        ?int $initiatedById = null
    ): ScanResultDTO;

    /**
     * ベースラインが存在するか
     *
     * @param  string  $filename  ファイル名
     */
    public function hasBaseline(string $filename = 'core_hashes.json'): bool;

    /**
     * ベースラインを再生成
     *
     * @param  ScanTargetDTO  $target  スキャン対象
     * @param  string  $trigger  トリガー
     * @param  string  $initiatedByType  実行者タイプ
     * @param  int|null  $initiatedById  実行者ID
     */
    public function regenerateBaseline(
        ScanTargetDTO $target,
        string $trigger = 'manual',
        string $initiatedByType = 'user',
        ?int $initiatedById = null
    ): bool;
}
