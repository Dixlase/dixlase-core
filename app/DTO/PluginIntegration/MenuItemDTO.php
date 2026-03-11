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

use JsonSerializable;

/**
 * @api プラグイン/テーマから使用可能な安定APIです
 *
 * メニューアイテムのDTO
 *
 * メニュープラグインで使用するメニューアイテムの
 * 不変データオブジェクトです。
 */
final readonly class MenuItemDTO implements JsonSerializable
{
    /**
     * @param  string  $label  メニューラベル（表示名）
     * @param  string  $url  メニューURL
     * @param  string  $target  リンクターゲット（'_self', '_blank', '_parent', '_top'）
     * @param  string|null  $sourceType  ソースタイプ（'custom', 'page', 'post', etc.）
     * @param  string|null  $sourceId  ソースID（プラグインコンテンツのID）
     * @param  string|null  $sourceProvider  ソースプロバイダー（プラグインスラッグ）
     * @param  string|null  $iconClass  アイコンクラス（例: 'fas fa-home'）
     * @param  string|null  $cssClass  CSSクラス
     * @param  int  $displayOrder  表示順
     * @param  bool  $isActive  有効/無効
     * @param  array<string,mixed>  $meta  追加メタデータ
     */
    public function __construct(
        public string $label,
        public string $url,
        public string $target = '_self',
        public ?string $sourceType = 'custom',
        public ?string $sourceId = null,
        public ?string $sourceProvider = null,
        public ?string $iconClass = null,
        public ?string $cssClass = null,
        public int $displayOrder = 0,
        public bool $isActive = true,
        public array $meta = [],
    ) {}

    /**
     * JSON形式にシリアライズ
     *
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'label' => $this->label,
            'url' => $this->url,
            'target' => $this->target,
            'source_type' => $this->sourceType,
            'source_id' => $this->sourceId,
            'source_provider' => $this->sourceProvider,
            'icon_class' => $this->iconClass,
            'css_class' => $this->cssClass,
            'display_order' => $this->displayOrder,
            'is_active' => $this->isActive,
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
     * 配列からDTOを生成
     *
     * @param  array<string,mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            label: $data['label'] ?? '',
            url: $data['url'] ?? '',
            target: $data['target'] ?? '_self',
            sourceType: $data['source_type'] ?? 'custom',
            sourceId: $data['source_id'] ?? null,
            sourceProvider: $data['source_provider'] ?? null,
            iconClass: $data['icon_class'] ?? null,
            cssClass: $data['css_class'] ?? null,
            displayOrder: $data['display_order'] ?? 0,
            isActive: $data['is_active'] ?? true,
            meta: $data['meta'] ?? [],
        );
    }

    /**
     * LinkableDTOからMenuItemDTOを生成
     *
     * @param  string  $target  リンクターゲット
     */
    public static function fromLinkable(LinkableDTO $linkable, string $target = '_self'): self
    {
        return new self(
            label: $linkable->title,
            url: $linkable->url,
            target: $target,
            sourceType: $linkable->type,
            sourceId: $linkable->id,
            sourceProvider: $linkable->source,
        );
    }

    /**
     * カスタムURLかどうか判定
     */
    public function isCustomUrl(): bool
    {
        return $this->sourceType === 'custom' || $this->sourceId === null;
    }

    /**
     * プラグインコンテンツかどうか判定
     */
    public function isPluginContent(): bool
    {
        return $this->sourceType !== 'custom' && $this->sourceId !== null;
    }

    /**
     * ターゲットを変更した新しいDTOを生成
     *
     * @param  string  $target  新しいターゲット
     */
    public function withTarget(string $target): self
    {
        return new self(
            label: $this->label,
            url: $this->url,
            target: $target,
            sourceType: $this->sourceType,
            sourceId: $this->sourceId,
            sourceProvider: $this->sourceProvider,
            iconClass: $this->iconClass,
            cssClass: $this->cssClass,
            displayOrder: $this->displayOrder,
            isActive: $this->isActive,
            meta: $this->meta,
        );
    }

    /**
     * ラベルを変更した新しいDTOを生成
     *
     * @param  string  $label  新しいラベル
     */
    public function withLabel(string $label): self
    {
        return new self(
            label: $label,
            url: $this->url,
            target: $this->target,
            sourceType: $this->sourceType,
            sourceId: $this->sourceId,
            sourceProvider: $this->sourceProvider,
            iconClass: $this->iconClass,
            cssClass: $this->cssClass,
            displayOrder: $this->displayOrder,
            isActive: $this->isActive,
            meta: $this->meta,
        );
    }
}
