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

namespace App\DTO\RouteSlug;

use JsonSerializable;

/**
 * 登録済みルートスラッグDTO
 *
 * システム全体で使用されるURLスラッグの情報を保持する。
 * スラッグの競合検出に使用される。
 *
 * @param  string  $slug  スラッグ値（例: admin, pages）
 * @param  string  $owner  所有者ID（例: core:admin_url, dixlase-pages:pages_directory）
 * @param  string  $label  人間可読なラベル（翻訳キー）
 * @param  bool  $isReserved  システム予約パスか
 */
final readonly class RegisteredSlug implements JsonSerializable
{
    public function __construct(
        public string $slug,
        public string $owner,
        public string $label,
        public bool $isReserved = false,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'slug' => $this->slug,
            'owner' => $this->owner,
            'label' => $this->label,
            'is_reserved' => $this->isReserved,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->jsonSerialize();
    }

    /**
     * 配列から生成
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            slug: $data['slug'],
            owner: $data['owner'],
            label: $data['label'] ?? '',
            isReserved: $data['is_reserved'] ?? false,
        );
    }

    /**
     * システム予約パスとして生成
     */
    public static function reserved(string $slug): self
    {
        return new self(
            slug: $slug,
            owner: 'system:reserved',
            label: 'validation/route-slug.reserved_path',
            isReserved: true,
        );
    }
}
