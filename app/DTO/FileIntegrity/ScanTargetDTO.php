<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
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
 * @api プラグイン/テーマから使用可能な安定APIです
 *
 * スキャン対象DTO
 *
 * ファイル整合性スキャンの対象を定義する不変データオブジェクトです。
 */
final readonly class ScanTargetDTO implements JsonSerializable
{
    public const SCOPE_CORE = 'core';

    public const SCOPE_PLUGIN = 'plugin';

    public const SCOPE_THEME = 'theme';

    public const SCOPE_ALL = 'all';

    /**
     * @param  string  $scope  スコープ（core, plugin, theme, all）
     * @param  string|null  $identifier  プラグイン/テーマのスラッグ（scope=plugin/themeの場合）
     * @param  array<string>  $paths  スキャン対象パス
     * @param  array<string>  $ignorePatterns  除外パターン
     * @param  string  $hashAlgo  ハッシュアルゴリズム
     */
    public function __construct(
        public string $scope = self::SCOPE_CORE,
        public ?string $identifier = null,
        public array $paths = [],
        public array $ignorePatterns = [],
        public string $hashAlgo = 'sha256',
    ) {}

    /**
     * コアスキャン用のターゲットを生成
     */
    public static function core(): self
    {
        return new self(
            scope: self::SCOPE_CORE,
            paths: [
                'app',
                'bootstrap',
                'config',
                'routes',
                'public/index.php',
                'artisan',
                'composer.json',
                'composer.lock',
            ],
            ignorePatterns: [
                'app/Custom',
                'storage',
                'vendor',
                'node_modules',
                'bootstrap/cache',
                '.git',
                '.env',
                '.env.*',
            ],
        );
    }

    /**
     * プラグインスキャン用のターゲットを生成
     *
     * @param  string  $pluginSlug  プラグインスラッグ
     */
    public static function plugin(string $pluginSlug): self
    {
        return new self(
            scope: self::SCOPE_PLUGIN,
            identifier: $pluginSlug,
            paths: [
                "plugins/{$pluginSlug}",
            ],
            ignorePatterns: [
                'vendor',
                'node_modules',
                '.git',
            ],
        );
    }

    /**
     * テーマスキャン用のターゲットを生成
     *
     * @param  string  $themeSlug  テーマスラッグ
     */
    public static function theme(string $themeSlug): self
    {
        return new self(
            scope: self::SCOPE_THEME,
            identifier: $themeSlug,
            paths: [
                "themes/{$themeSlug}",
            ],
            ignorePatterns: [
                'vendor',
                'node_modules',
                '.git',
            ],
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
            'scope' => $this->scope,
            'identifier' => $this->identifier,
            'paths' => $this->paths,
            'ignore_patterns' => $this->ignorePatterns,
            'hash_algo' => $this->hashAlgo,
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
            scope: $data['scope'] ?? self::SCOPE_CORE,
            identifier: $data['identifier'] ?? null,
            paths: $data['paths'] ?? [],
            ignorePatterns: $data['ignore_patterns'] ?? [],
            hashAlgo: $data['hash_algo'] ?? 'sha256',
        );
    }

    /**
     * ベースラインファイル名を取得
     */
    public function getBaselineFilename(): string
    {
        return match ($this->scope) {
            self::SCOPE_PLUGIN => "plugin_{$this->identifier}_hashes.json",
            self::SCOPE_THEME => "theme_{$this->identifier}_hashes.json",
            default => 'core_hashes.json',
        };
    }
}
