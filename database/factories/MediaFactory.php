<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
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

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Media;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Core's own factory for the media library.
 *
 * An extension that ships a `MediaFactory` still takes precedence over this
 * one, because `Tests\TestCase` checks the installed extensions' factory
 * namespaces before falling back to this one. This exists so the fallback has
 * something to fall back to: without it, a core test calling
 * `Media::factory()` fails outright wherever no extension is installed --
 * which is every clone of this repository and every Dependabot run, since a
 * Dependabot run receives no repository secrets and so fetches no extension.
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Media>
 */
class MediaFactory extends Factory
{
    protected $model = Media::class;

    /**
     * An image, because that is what the library is used for and what the
     * selector renders. `site_id` is left out: BelongsToSite fills it from
     * SiteContext.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->slug(2);
        $extension = fake()->randomElement(['jpg', 'png', 'webp']);

        return [
            'name' => $name.'.'.$extension,
            'path' => 'media/'.$name.'.'.$extension,
            'type' => 'image/'.($extension === 'jpg' ? 'jpeg' : $extension),
            'alt_text' => fake()->optional()->sentence(3),
            'caption' => null,
            'description' => null,
            'file_size' => fake()->numberBetween(1024, 4 * 1024 * 1024),
            'width' => fake()->numberBetween(320, 4096),
            'height' => fake()->numberBetween(240, 2160),
            'uploaded_by' => Member::factory(),
        ];
    }

    /**
     * A row whose uploader is gone, which the column allows.
     */
    public function withoutUploader(): static
    {
        return $this->state(fn (array $attributes): array => [
            'uploaded_by' => null,
        ]);
    }

    /**
     * A non-image file, for the paths that branch on the MIME type.
     */
    public function document(): static
    {
        return $this->state(function (array $attributes): array {
            $name = fake()->unique()->slug(2);

            return [
                'name' => $name.'.pdf',
                'path' => 'media/'.$name.'.pdf',
                'type' => 'application/pdf',
                'width' => null,
                'height' => null,
            ];
        });
    }
}
