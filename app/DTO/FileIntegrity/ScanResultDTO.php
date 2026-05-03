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

namespace App\DTO\FileIntegrity;

use JsonSerializable;

/**
 * Scan result DTO
 *
 * Immutable data object that holds the results of a file integrity scan
 */
final readonly class ScanResultDTO implements JsonSerializable
{
    public const STATUS_OK = 'ok';

    public const STATUS_WARNING = 'warning';

    public const STATUS_CRITICAL = 'critical';

    /**
     * @param  string  $id  Scan ID (UUID)
     * @param  string  $scope  Scope
     * @param  string|null  $identifier  Plugin/theme slug
     * @param  string  $status  Status (ok, warning, critical)
     * @param  string  $trigger  Trigger
     * @param  string  $initiatedByType  Executor type
     * @param  int|null  $initiatedById  Executor ID
     * @param  string  $hashAlgo  Hash algorithm
     * @param  string|null  $baselineVersion  Baseline version
     * @param  int  $totalFilesScanned  Number of scanned files
     * @param  array<FileChangeDTO>  $changedFiles  Modified files
     * @param  array<FileChangeDTO>  $addedFiles  Added files
     * @param  array<FileChangeDTO>  $removedFiles  Deleted files
     * @param  array<FileChangeDTO>  $suspiciousFiles  Suspicious files
     * @param  string  $startedAt  Start datetime
     * @param  string  $finishedAt  End datetime
     * @param  int  $durationMs  Execution time (milliseconds)
     * @param  string  $summary  Summary
     */
    public function __construct(
        public string $id,
        public string $scope,
        public ?string $identifier,
        public string $status,
        public string $trigger,
        public string $initiatedByType,
        public ?int $initiatedById,
        public string $hashAlgo,
        public ?string $baselineVersion,
        public int $totalFilesScanned,
        public array $changedFiles,
        public array $addedFiles,
        public array $removedFiles,
        public array $suspiciousFiles,
        public string $startedAt,
        public string $finishedAt,
        public int $durationMs,
        public string $summary,
    ) {}

    /**
     * Whether there are any issues
     */
    public function hasIssues(): bool
    {
        return $this->status !== self::STATUS_OK;
    }

    /**
     * Whether there are any critical issues
     */
    public function isCritical(): bool
    {
        return $this->status === self::STATUS_CRITICAL;
    }

    /**
     * Whether there are any warnings
     */
    public function isWarning(): bool
    {
        return $this->status === self::STATUS_WARNING;
    }

    /**
     * Whether it is normal
     */
    public function isOk(): bool
    {
        return $this->status === self::STATUS_OK;
    }

    /**
     * Get the number of modified files
     */
    public function getChangedCount(): int
    {
        return count($this->changedFiles);
    }

    /**
     * Get the number of added files
     */
    public function getAddedCount(): int
    {
        return count($this->addedFiles);
    }

    /**
     * Get the number of deleted files
     */
    public function getRemovedCount(): int
    {
        return count($this->removedFiles);
    }

    /**
     * Get the number of suspicious files
     */
    public function getSuspiciousCount(): int
    {
        return count($this->suspiciousFiles);
    }

    /**
     * Get all modified files
     *
     * @return array<FileChangeDTO>
     */
    public function getAllChanges(): array
    {
        return array_merge(
            $this->changedFiles,
            $this->addedFiles,
            $this->removedFiles,
            $this->suspiciousFiles
        );
    }

    /**
     * Serialize to JSON format
     *
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'scope' => $this->scope,
            'identifier' => $this->identifier,
            'status' => $this->status,
            'trigger' => $this->trigger,
            'initiated_by_type' => $this->initiatedByType,
            'initiated_by_id' => $this->initiatedById,
            'hash_algo' => $this->hashAlgo,
            'baseline_version' => $this->baselineVersion,
            'total_files_scanned' => $this->totalFilesScanned,
            'changed_files_count' => $this->getChangedCount(),
            'added_files_count' => $this->getAddedCount(),
            'removed_files_count' => $this->getRemovedCount(),
            'suspicious_files_count' => $this->getSuspiciousCount(),
            'changed_files' => array_map(fn ($f) => $f->toArray(), $this->changedFiles),
            'added_files' => array_map(fn ($f) => $f->toArray(), $this->addedFiles),
            'removed_files' => array_map(fn ($f) => $f->toArray(), $this->removedFiles),
            'suspicious_files' => array_map(fn ($f) => $f->toArray(), $this->suspiciousFiles),
            'started_at' => $this->startedAt,
            'finished_at' => $this->finishedAt,
            'duration_ms' => $this->durationMs,
            'summary' => $this->summary,
        ];
    }

    /**
     * Convert to array format
     *
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return $this->jsonSerialize();
    }
}
