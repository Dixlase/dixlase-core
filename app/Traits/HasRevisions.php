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

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * `App\Contracts\Revisionable` を実装するモデルに `use` することで、
 * リビジョンへの `HasMany` リレーションを自動提供する。
 *
 * 使用例:
 * ```php
 * class FrontPage extends Model implements Revisionable
 * {
 *     use HasRevisions;
 *
 *     public function revisionModel(): string { return FrontPageRevision::class; }
 *     public function revisionForeignKey(): string { return 'front_page_id'; }
 *     public function revisionableFields(): array { return ['title', 'content', ...]; }
 * }
 * ```
 */
trait HasRevisions
{
    /**
     * リビジョン（編集履歴）を新しい順で取得する。
     *
     * @return HasMany<\Illuminate\Database\Eloquent\Model>
     */
    public function revisions(): HasMany
    {
        /** @var \App\Contracts\Revisionable $this */
        return $this->hasMany($this->revisionModel(), $this->revisionForeignKey())
            ->latest('created_at');
    }
}
