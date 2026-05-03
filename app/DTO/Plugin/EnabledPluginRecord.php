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

namespace App\DTO\Plugin;

use JsonSerializable;

/**
 * 有効化されたプラグインの基本情報を保持するDTO
 *
 * Plugin モデルに依存しない純粋な値オブジェクト。
 * PluginRepositoryInterface::getEnabled() の戻り値として使用される。
 */
final readonly class EnabledPluginRecord implements JsonSerializable
{
    /**
     * @param  string  $name  プラグイン名（例: DixlasePages）
     * @param  string  $directory  プラグインディレクトリ名（例: DixlasePages）
     * @param  string  $slug  プラグインスラッグ（例: dixlase-pages）
     */
    public function __construct(
        public string $name,
        public string $directory,
        public string $slug,
    ) {}

    /**
     * 配列からインスタンスを生成
     *
     * @param  array{name: string, directory: string, slug: string}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            directory: $data['directory'],
            slug: $data['slug'],
        );
    }

    /**
     * @return array{name: string, directory: string, slug: string}
     */
    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
            'directory' => $this->directory,
            'slug' => $this->slug,
        ];
    }

    /**
     * @return array{name: string, directory: string, slug: string}
     */
    public function toArray(): array
    {
        return $this->jsonSerialize();
    }
}
