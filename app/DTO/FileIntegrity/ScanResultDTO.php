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
 * スキャン結果DTO
 *
 * ファイル整合性スキャンの結果を保持する不変データオブジェクトです。
 */
final readonly class ScanResultDTO implements JsonSerializable
{
    public const STATUS_OK = 'ok';

    public const STATUS_WARNING = 'warning';

    public const STATUS_CRITICAL = 'critical';

    /**
     * @param  string  $id  スキャンID（UUID）
     * @param  string  $scope  スコープ
     * @param  string|null  $identifier  プラグイン/テーマのスラッグ
     * @param  string  $status  ステータス（ok, warning, critical）
     * @param  string  $trigger  トリガー
     * @param  string  $initiatedByType  実行者タイプ
     * @param  int|null  $initiatedById  実行者ID
     * @param  string  $hashAlgo  ハッシュアルゴリズム
     * @param  string|null  $baselineVersion  ベースラインバージョン
     * @param  int  $totalFilesScanned  スキャンしたファイル数
     * @param  array<FileChangeDTO>  $changedFiles  変更されたファイル
     * @param  array<FileChangeDTO>  $addedFiles  追加されたファイル
     * @param  array<FileChangeDTO>  $removedFiles  削除されたファイル
     * @param  array<FileChangeDTO>  $suspiciousFiles  疑わしいファイル
     * @param  string  $startedAt  開始日時
     * @param  string  $finishedAt  終了日時
     * @param  int  $durationMs  実行時間（ミリ秒）
     * @param  string  $summary  サマリー
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
     * 問題があるか
     */
    public function hasIssues(): bool
    {
        return $this->status !== self::STATUS_OK;
    }

    /**
     * 重大な問題があるか
     */
    public function isCritical(): bool
    {
        return $this->status === self::STATUS_CRITICAL;
    }

    /**
     * 警告があるか
     */
    public function isWarning(): bool
    {
        return $this->status === self::STATUS_WARNING;
    }

    /**
     * 正常か
     */
    public function isOk(): bool
    {
        return $this->status === self::STATUS_OK;
    }

    /**
     * 変更されたファイル数を取得
     */
    public function getChangedCount(): int
    {
        return count($this->changedFiles);
    }

    /**
     * 追加されたファイル数を取得
     */
    public function getAddedCount(): int
    {
        return count($this->addedFiles);
    }

    /**
     * 削除されたファイル数を取得
     */
    public function getRemovedCount(): int
    {
        return count($this->removedFiles);
    }

    /**
     * 疑わしいファイル数を取得
     */
    public function getSuspiciousCount(): int
    {
        return count($this->suspiciousFiles);
    }

    /**
     * 全ての変更ファイルを取得
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
     * JSON形式にシリアライズ
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
     * 配列形式に変換
     *
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return $this->jsonSerialize();
    }
}
