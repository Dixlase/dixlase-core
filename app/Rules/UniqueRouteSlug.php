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

namespace App\Rules;

use App\Services\RouteSlugRegistry;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * ルートスラッグ一意性バリデーションルール
 *
 * システム全体でトップレベルURLスラッグが重複しないことを検証する。
 * 予約パスとの競合、他機能との競合を検出し、適切なエラーメッセージを返す。
 */
class UniqueRouteSlug implements ValidationRule
{
    /**
     * @param  string  $owner  自身のオーナーID（自分自身を除外するため）
     */
    public function __construct(
        private string $owner,
    ) {}

    /**
     * バリデーション実行
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            return;
        }

        $registry = app(RouteSlugRegistry::class);
        $conflict = $registry->findConflict($value, $this->owner);

        if ($conflict === null) {
            return;
        }

        if ($conflict->isReserved) {
            $fail(__('validation/route-slug.reserved', [
                'slug' => $value,
            ]));
        } else {
            $fail(__('validation/route-slug.conflict', [
                'slug' => $value,
                'owner' => __($conflict->label),
            ]));
        }
    }

    /**
     * 静的ファクトリーメソッド
     *
     * @param  string  $owner  オーナーID（例: 'core:admin_url'）
     */
    public static function for(string $owner): static
    {
        return new static($owner);
    }
}
