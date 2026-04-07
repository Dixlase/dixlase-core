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

namespace App\DTO\FileIntegrity;

use JsonSerializable;

/**
 * ベースラインDTO
 *
 * ファイル整合性チェックの基準となるハッシュ情報を保持する不変データオブジェクトです。
 */
final readonly class BaselineDTO implements JsonSerializable
{
    /**
     * @param  string  $generatedAt  生成日時（ISO8601）
     * @param  string  $appVersion  アプリケーションバージョン
     * @param  string  $hashAlgo  ハッシュアルゴリズム
     * @param  string  $scope  スコープ
     * @param  string|null  $identifier  プラグイン/テーマのスラッグ
     * @param  array<string>  $paths  スキャン対象パス
     * @param  array<string>  $ignorePatterns  除外パターン
     * @param  array<string,string>  $files  ファイルパス => ハッシュ値
     */
    public function __construct(
        public string $generatedAt,
        public string $appVersion,
        public string $hashAlgo,
        public string $scope,
        public ?string $identifier,
        public array $paths,
        public array $ignorePatterns,
        public array $files,
    ) {}

    /**
     * ファイル数を取得
     */
    public function getFileCount(): int
    {
        return count($this->files);
    }

    /**
     * 特定のファイルのハッシュを取得
     *
     * @param  string  $path  ファイルパス
     */
    public function getFileHash(string $path): ?string
    {
        return $this->files[$path] ?? null;
    }

    /**
     * ファイルが存在するか
     *
     * @param  string  $path  ファイルパス
     */
    public function hasFile(string $path): bool
    {
        return isset($this->files[$path]);
    }

    /**
     * JSON形式にシリアライズ
     *
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'meta' => [
                'generated_at' => $this->generatedAt,
                'app_version' => $this->appVersion,
                'hash_algo' => $this->hashAlgo,
                'scope' => $this->scope,
                'identifier' => $this->identifier,
                'paths' => $this->paths,
                'ignore_patterns' => $this->ignorePatterns,
            ],
            'files' => $this->files,
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
        $meta = $data['meta'] ?? [];

        return new self(
            generatedAt: $meta['generated_at'] ?? now()->toIso8601String(),
            appVersion: $meta['app_version'] ?? config('app.version', '1.0.0'),
            hashAlgo: $meta['hash_algo'] ?? 'sha256',
            scope: $meta['scope'] ?? ScanTargetDTO::SCOPE_CORE,
            identifier: $meta['identifier'] ?? null,
            paths: $meta['paths'] ?? [],
            ignorePatterns: $meta['ignore_patterns'] ?? [],
            files: $data['files'] ?? [],
        );
    }

    /**
     * メタ情報のみを取得
     *
     * @return array<string,mixed>
     */
    public function getMeta(): array
    {
        return [
            'generated_at' => $this->generatedAt,
            'app_version' => $this->appVersion,
            'hash_algo' => $this->hashAlgo,
            'scope' => $this->scope,
            'identifier' => $this->identifier,
            'paths' => $this->paths,
            'ignore_patterns' => $this->ignorePatterns,
            'files_count' => $this->getFileCount(),
        ];
    }
}
