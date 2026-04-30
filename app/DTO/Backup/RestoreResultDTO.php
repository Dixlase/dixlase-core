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
 * 復元結果DTO
 *
 * 復元処理の結果を保持する不変データオブジェクトです。
 */
final readonly class RestoreResultDTO
{
    /**
     * @param  string[]  $targets  実際に復元された対象
     * @param  array<string,mixed>  $metadata  追加メタデータ
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
     * 成功結果を生成
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
     * 失敗結果を生成
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
