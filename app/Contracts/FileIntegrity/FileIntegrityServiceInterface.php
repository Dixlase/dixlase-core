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
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
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
 * File integrity check service contract
 *
 * Provides file tampering detection for Core and plugins
 */
interface FileIntegrityServiceInterface
{
    /**
     * Generate baseline
     *
     * @param  ScanTargetDTO  $target  Scan target
     */
    public function generateBaseline(ScanTargetDTO $target): BaselineDTO;

    /**
     * Save baseline
     *
     * @param  BaselineDTO  $baseline  Baseline
     * @param  string  $filename  Filename
     */
    public function saveBaseline(BaselineDTO $baseline, string $filename = 'core_hashes.json'): bool;

    /**
     * Load baseline
     *
     * @param  string  $filename  Filename
     */
    public function loadBaseline(string $filename = 'core_hashes.json'): ?BaselineDTO;

    /**
     * Execute file integrity scan
     *
     * @param  ScanTargetDTO  $target  Scan target
     * @param  string  $trigger  Trigger (manual, schedule, install, update)
     * @param  string  $initiatedByType  Executor type (system, user)
     * @param  int|null  $initiatedById  Executor ID
     */
    public function scan(
        ScanTargetDTO $target,
        string $trigger = 'manual',
        string $initiatedByType = 'system',
        ?int $initiatedById = null
    ): ScanResultDTO;

    /**
     * Whether baseline exists
     *
     * @param  string  $filename  Filename
     */
    public function hasBaseline(string $filename = 'core_hashes.json'): bool;

    /**
     * Regenerate baseline
     *
     * @param  ScanTargetDTO  $target  Scan target
     * @param  string  $trigger  Trigger
     * @param  string  $initiatedByType  Executor type
     * @param  int|null  $initiatedById  Executor ID
     */
    public function regenerateBaseline(
        ScanTargetDTO $target,
        string $trigger = 'manual',
        string $initiatedByType = 'user',
        ?int $initiatedById = null
    ): bool;
}
