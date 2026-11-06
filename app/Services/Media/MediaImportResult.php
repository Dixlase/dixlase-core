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

namespace App\Services\Media;

use App\Models\Media;

/**
 * What {@see MediaImporter} did with one local file (#902)
 *
 * @api
 */
final class MediaImportResult
{
    /**
     * @param  list<string>  $warnings
     */
    private function __construct(
        public readonly string $source,
        public readonly ?Media $media,
        public readonly bool $reused,
        public readonly ?string $error,
        public readonly array $warnings,
    ) {}

    /**
     * @param  list<string>  $warnings
     */
    public static function imported(string $source, Media $media, array $warnings = []): self
    {
        return new self($source, $media, false, null, $warnings);
    }

    public static function reused(string $source, Media $media): self
    {
        return new self($source, $media, true, null, []);
    }

    public static function failed(string $source, string $error): self
    {
        return new self($source, null, false, $error, []);
    }

    public function succeeded(): bool
    {
        return $this->media !== null;
    }

    /**
     * The result as the command's --json prints it
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        if ($this->media === null) {
            return ['source' => $this->source, 'error' => $this->error];
        }

        return [
            'source' => $this->source,
            'id' => $this->media->id,
            'name' => $this->media->name,
            'type' => $this->media->type,
            'width' => $this->media->width,
            'height' => $this->media->height,
            'path' => MediaImporter::pathOf($this->media),
            'url' => MediaImporter::urlOf($this->media),
            'reused' => $this->reused,
            'warnings' => $this->warnings,
        ];
    }
}
