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

namespace App\DTO\Backup;

/**
 * Backup Result DTO
 *
 * Immutable data object that holds the result of a backup operation
 */
final readonly class BackupResultDTO
{
    /**
     * @param  string[]  $targets  Targets included in the backup
     * @param  array<string,mixed>  $metadata  Additional metadata
     */
    public function __construct(
        public bool $success,
        public ?int $backupRecordId,
        public ?string $filePath,
        public ?int $fileSize,
        public ?float $duration,
        public array $targets = [],
        public ?string $error = null,
        public array $metadata = [],
    ) {}

    /**
     * Generate success result
     *
     * @param  string[]  $targets
     * @param  array<string,mixed>  $metadata
     */
    public static function success(
        int $backupRecordId,
        string $filePath,
        int $fileSize,
        float $duration,
        array $targets,
        array $metadata = [],
    ): self {
        return new self(
            success: true,
            backupRecordId: $backupRecordId,
            filePath: $filePath,
            fileSize: $fileSize,
            duration: $duration,
            targets: $targets,
            metadata: $metadata,
        );
    }

    /**
     * Generate failure result
     */
    public static function failure(string $error, ?int $backupRecordId = null): self
    {
        return new self(
            success: false,
            backupRecordId: $backupRecordId,
            filePath: null,
            fileSize: null,
            duration: null,
            error: $error,
        );
    }
}
