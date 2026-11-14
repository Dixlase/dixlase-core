<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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
 *       (see LICENSE-COMMERCIAL, or contact info@dixlase.org).
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

namespace App\DTO\Front;

use JsonSerializable;

/**
 * Front page body choice DTO
 *
 * One choice a plugin offers for the body of the front page, described without
 * rendering it. A theme turns a list of these into its chooser.
 *
 * It carries no provider key and composes no stored value. The provider a choice
 * belongs to is known from the provider that offered it, and FrontPageBodyResolver
 * composes the stored form — so a provider cannot offer a choice that resolves to
 * someone else.
 *
 * @see \App\Contracts\Front\FrontPageBodyProviderInterface
 * @see \App\Services\Front\FrontPageBodyResolver
 */
final readonly class FrontPageBodyDTO implements JsonSerializable
{
    /**
     * @param  string  $id  Identifier of this choice within its provider (e.g. 'top', '12')
     * @param  string  $label  Human-readable label (recommended: a translation key)
     * @param  string|null  $description  Short description shown next to the choice
     * @param  array<string, mixed>  $meta  Provider-specific metadata
     */
    public function __construct(
        public string $id,
        public string $label,
        public ?string $description = null,
        public array $meta = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'description' => $this->description,
            'meta' => $this->meta,
        ];
    }
}
