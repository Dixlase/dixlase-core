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
 * Restore Result DTO
 *
 * Immutable data object that holds the result of a restore operation
 */
final readonly class RestoreResultDTO
{
    /**
     * @param  string[]  $targets  Actually restored target
     * @param  array<string,mixed>  $metadata  Additional metadata
     */
    public function __construct(
        public bool $success,
        public ?int $restoreRecordId,
        public ?int $preRestoreBackupRecordId,
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
        int $restoreRecordId,
        ?int $preRestoreBackupRecordId,
        float $duration,
        array $targets,
        array $metadata = [],
    ): self {
        return new self(
            success: true,
            restoreRecordId: $restoreRecordId,
            preRestoreBackupRecordId: $preRestoreBackupRecordId,
            duration: $duration,
            targets: $targets,
            metadata: $metadata,
        );
    }

    /**
     * Generate failure result
     */
    public static function failure(string $error, ?int $restoreRecordId = null): self
    {
        return new self(
            success: false,
            restoreRecordId: $restoreRecordId,
            preRestoreBackupRecordId: null,
            duration: null,
            error: $error,
        );
    }
}
