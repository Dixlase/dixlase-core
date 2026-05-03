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

namespace App\DTO\Mail;

use JsonSerializable;

/**
 * メール添付ファイルDTO
 *
 * メールの添付ファイル情報を保持する不変データオブジェクトです。
 */
final readonly class MailAttachmentDTO implements JsonSerializable
{
    /**
     * @param  string  $path  ファイルパス
     * @param  string|null  $name  表示名（nullの場合はファイル名を使用）
     * @param  string|null  $mime  MIMEタイプ（nullの場合は自動検出）
     */
    public function __construct(
        public string $path,
        public ?string $name = null,
        public ?string $mime = null,
    ) {}

    /**
     * ファイルパスから生成
     *
     * @param  string  $path  ファイルパス
     * @param  string|null  $name  表示名
     */
    public static function fromPath(string $path, ?string $name = null): self
    {
        return new self(
            path: $path,
            name: $name ?? basename($path),
        );
    }

    /**
     * ストレージパスから生成
     *
     * @param  string  $storagePath  ストレージ相対パス
     * @param  string|null  $name  表示名
     */
    public static function fromStorage(string $storagePath, ?string $name = null): self
    {
        return new self(
            path: storage_path('app/'.$storagePath),
            name: $name ?? basename($storagePath),
        );
    }

    /**
     * 表示名を取得
     */
    public function getDisplayName(): string
    {
        return $this->name ?? basename($this->path);
    }

    /**
     * ファイルが存在するか
     */
    public function exists(): bool
    {
        return file_exists($this->path);
    }

    /**
     * ファイルサイズを取得
     */
    public function getSize(): ?int
    {
        if (! $this->exists()) {
            return null;
        }

        return filesize($this->path) ?: null;
    }

    /**
     * JSON形式にシリアライズ
     *
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'path' => $this->path,
            'name' => $this->getDisplayName(),
            'mime' => $this->mime,
            'size' => $this->getSize(),
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

    /**
     * 配列からDTOを生成
     *
     * @param  array<string,mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            path: $data['path'],
            name: $data['name'] ?? null,
            mime: $data['mime'] ?? null,
        );
    }
}
