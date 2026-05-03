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

namespace App\DTO\PluginIntegration;

use JsonSerializable;

/**
 * コンテンツ単位のSEOメタ情報DTO
 *
 * プラグイン生成ページ（固定ページ、リーガルページ、ブログ記事など）に
 * 紐づくSEOメタタグ・OGP情報を受け渡しするための不変データオブジェクトです。
 *
 * 現時点の最小フィールド: description, ogpMediaId
 * 将来的にタイトルタグのオーバーライドや keywords 等を追加予定。
 */
final readonly class SeoMetaDTO implements JsonSerializable
{
    /**
     * @param  string|null  $description  メタディスクリプション（未設定時はnull）
     * @param  int|null  $ogpMediaId  OGP画像のメディアID（未設定時はnull）
     */
    public function __construct(
        public ?string $description = null,
        public ?int $ogpMediaId = null,
    ) {}

    /**
     * メタ情報が空（全フィールド未設定）かどうか判定
     */
    public function isEmpty(): bool
    {
        return $this->description === null
            && $this->ogpMediaId === null;
    }

    /**
     * JSON形式にシリアライズ
     *
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'description' => $this->description,
            'ogp_media_id' => $this->ogpMediaId,
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
            description: isset($data['description']) ? (string) $data['description'] : null,
            ogpMediaId: isset($data['ogp_media_id']) ? (int) $data['ogp_media_id'] : null,
        );
    }
}
