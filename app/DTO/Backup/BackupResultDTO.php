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
 * バックアップ結果DTO
 *
 * バックアップ処理の結果を保持する不変データオブジェクトです。
 */
final readonly class BackupResultDTO
{
    /**
     * @param  string[]  $targets  バックアップに含まれる対象
     * @param  array<string,mixed>  $metadata  追加メタデータ
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
     * 成功結果を生成
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
     * 失敗結果を生成
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
