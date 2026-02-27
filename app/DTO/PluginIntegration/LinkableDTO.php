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

namespace App\DTO\PluginIntegration;

use App\Contracts\PluginIntegration\LinkableInterface;
use JsonSerializable;

/**
 * @api プラグイン/テーマから使用可能な安定APIです
 *
 * リンク可能なコンテンツのDTO
 *
 * プラグイン間でコンテンツ情報を受け渡しする際の
 * 不変データオブジェクトです。
 */
final readonly class LinkableDTO implements JsonSerializable, LinkableInterface
{
    /**
     * @param  string  $id  コンテンツID（ULID/UUID）
     * @param  string  $title  コンテンツタイトル
     * @param  string  $url  コンテンツURL
     * @param  string  $type  コンテンツタイプ（'post', 'page', 'media', etc.）
     * @param  string  $source  データソース（'core' または プラグインスラッグ）
     * @param  string|null  $sourceTable  ソーステーブル名（オプション）
     * @param  string|null  $locale  ロケール（'ja', 'en', etc.）
     * @param  array<string,mixed>  $meta  追加メタデータ
     */
    public function __construct(
        public string $id,
        public string $title,
        public string $url,
        public string $type,
        public string $source,
        public ?string $sourceTable = null,
        public ?string $locale = null,
        public array $meta = [],
    ) {}

    /**
     * コンテンツIDを取得
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * コンテンツタイトルを取得
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * コンテンツURLを取得
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * コンテンツタイプを取得
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * データソースを取得
     */
    public function getSource(): string
    {
        return $this->source;
    }

    /**
     * ソーステーブル名を取得
     */
    public function getSourceTable(): ?string
    {
        return $this->sourceTable;
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
            'title' => $this->title,
            'url' => $this->url,
            'type' => $this->type,
            'source' => $this->source,
            'source_table' => $this->sourceTable,
            'locale' => $this->locale,
            'meta' => $this->meta,
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
     * コアのコンテンツかどうか判定
     */
    public function isCore(): bool
    {
        return $this->source === 'core';
    }

    /**
     * プラグインのコンテンツかどうか判定
     */
    public function isPlugin(): bool
    {
        return $this->source !== 'core';
    }

    /**
     * 特定のプラグインのコンテンツかどうか判定
     *
     * @param  string  $pluginSlug  プラグインスラッグ
     */
    public function isFromPlugin(string $pluginSlug): bool
    {
        return $this->source === $pluginSlug;
    }

    /**
     * 配列からDTOを生成
     *
     * @param  array<string,mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            title: $data['title'],
            url: $data['url'],
            type: $data['type'],
            source: $data['source'],
            sourceTable: $data['source_table'] ?? null,
            locale: $data['locale'] ?? null,
            meta: $data['meta'] ?? [],
        );
    }

    /**
     * タイトルを変更した新しいDTOを生成（不変オブジェクトパターン）
     *
     * @param  string  $title  新しいタイトル
     */
    public function withTitle(string $title): self
    {
        return new self(
            id: $this->id,
            title: $title,
            url: $this->url,
            type: $this->type,
            source: $this->source,
            sourceTable: $this->sourceTable,
            locale: $this->locale,
            meta: $this->meta,
        );
    }

    /**
     * URLを変更した新しいDTOを生成（不変オブジェクトパターン）
     *
     * @param  string  $url  新しいURL
     */
    public function withUrl(string $url): self
    {
        return new self(
            id: $this->id,
            title: $this->title,
            url: $url,
            type: $this->type,
            source: $this->source,
            sourceTable: $this->sourceTable,
            locale: $this->locale,
            meta: $this->meta,
        );
    }

    /**
     * メタデータを追加した新しいDTOを生成（不変オブジェクトパターン）
     *
     * @param  array<string,mixed>  $meta  追加するメタデータ
     */
    public function withMeta(array $meta): self
    {
        return new self(
            id: $this->id,
            title: $this->title,
            url: $this->url,
            type: $this->type,
            source: $this->source,
            sourceTable: $this->sourceTable,
            locale: $this->locale,
            meta: array_merge($this->meta, $meta),
        );
    }
}
