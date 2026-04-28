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

namespace App\DTO\FileIntegrity;

use JsonSerializable;

/**
 * ファイル変更DTO
 *
 * ファイルの変更情報を保持する不変データオブジェクトです。
 */
final readonly class FileChangeDTO implements JsonSerializable
{
    public const TYPE_CHANGED = 'changed';

    public const TYPE_ADDED = 'added';

    public const TYPE_REMOVED = 'removed';

    public const TYPE_SUSPICIOUS = 'suspicious';

    /**
     * @param  string  $path  ファイルパス
     * @param  string  $type  変更タイプ（changed, added, removed, suspicious）
     * @param  string|null  $oldHash  変更前ハッシュ
     * @param  string|null  $newHash  変更後ハッシュ
     * @param  string|null  $reason  理由（suspiciousの場合）
     */
    public function __construct(
        public string $path,
        public string $type,
        public ?string $oldHash = null,
        public ?string $newHash = null,
        public ?string $reason = null,
    ) {}

    /**
     * 変更されたファイルを生成
     *
     * @param  string  $path  ファイルパス
     * @param  string  $oldHash  変更前ハッシュ
     * @param  string  $newHash  変更後ハッシュ
     */
    public static function changed(string $path, string $oldHash, string $newHash): self
    {
        return new self(
            path: $path,
            type: self::TYPE_CHANGED,
            oldHash: $oldHash,
            newHash: $newHash,
        );
    }

    /**
     * 追加されたファイルを生成
     *
     * @param  string  $path  ファイルパス
     * @param  string  $newHash  ハッシュ
     */
    public static function added(string $path, string $newHash): self
    {
        return new self(
            path: $path,
            type: self::TYPE_ADDED,
            newHash: $newHash,
        );
    }

    /**
     * 削除されたファイルを生成
     *
     * @param  string  $path  ファイルパス
     * @param  string  $oldHash  削除前ハッシュ
     */
    public static function removed(string $path, string $oldHash): self
    {
        return new self(
            path: $path,
            type: self::TYPE_REMOVED,
            oldHash: $oldHash,
        );
    }

    /**
     * 疑わしいファイルを生成
     *
     * @param  string  $path  ファイルパス
     * @param  string  $hash  ハッシュ
     * @param  string  $reason  理由
     */
    public static function suspicious(string $path, string $hash, string $reason): self
    {
        return new self(
            path: $path,
            type: self::TYPE_SUSPICIOUS,
            newHash: $hash,
            reason: $reason,
        );
    }

    /**
     * 変更タイプか
     */
    public function isChanged(): bool
    {
        return $this->type === self::TYPE_CHANGED;
    }

    /**
     * 追加タイプか
     */
    public function isAdded(): bool
    {
        return $this->type === self::TYPE_ADDED;
    }

    /**
     * 削除タイプか
     */
    public function isRemoved(): bool
    {
        return $this->type === self::TYPE_REMOVED;
    }

    /**
     * 疑わしいタイプか
     */
    public function isSuspicious(): bool
    {
        return $this->type === self::TYPE_SUSPICIOUS;
    }

    /**
     * JSON形式にシリアライズ
     *
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return array_filter([
            'path' => $this->path,
            'type' => $this->type,
            'old_hash' => $this->oldHash,
            'new_hash' => $this->newHash,
            'reason' => $this->reason,
        ], fn ($v) => $v !== null);
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

    /**
     * 配列からDTOを生成
     *
     * @param  array<string,mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            path: $data['path'],
            type: $data['type'],
            oldHash: $data['old_hash'] ?? null,
            newHash: $data['new_hash'] ?? null,
            reason: $data['reason'] ?? null,
        );
    }
}
