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
 *       (see LICENSE.commercial, or contact info@dixlase.org).
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

namespace App\Rules;

use App\Services\RouteSlugRegistry;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Route slug uniqueness validation rule
 *
 * Validates that top-level URL slugs do not duplicate across the entire system
 * Detects conflicts with reserved paths and other features, and returns appropriate error messages
 */
class UniqueRouteSlug implements ValidationRule
{
    /**
     * @param  string  $owner  Own owner ID (to exclude self)
     */
    public function __construct(
        private string $owner,
    ) {}

    /**
     * Execute validation
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
     * Static factory method
     *
     * @param  string  $owner  Owner ID (e.g., 'core:admin_url')
     */
    public static function for(string $owner): static
    {
        return new static($owner);
    }
}
