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

namespace App\Actions\Media;

use App\Actions\AbstractAction;
use App\Contracts\Action\Actor;
use App\DTO\Action\ActionResult;
use App\Enums\Permission;
use App\Models\Media;
use App\Models\MediaTag;

/**
 * Change the tags of one media file (#766).
 *
 * Takes either the whole set, `tags` (names), as the media page sends it,
 * or a change to the current set, `add` (names) and `remove` (tag ids), as
 * the library's bulk tagging sends it for each selected file. A name with no
 * tag yet creates the tag. Nothing is recorded when the set does not change.
 */
class UpdateMediaTagsAction extends AbstractAction
{
    public function __construct(
        protected readonly Media $media,
    ) {}

    protected function requiredPermission(): ?Permission
    {
        return Permission::MEDIA_UPLOAD;
    }

    protected function auditAction(): string
    {
        return 'media.tags_updated';
    }

    protected function handle(Actor $actor, array $data): ActionResult
    {
        $current = $this->media->tags()->pluck('media_tags.id')->map(fn ($id) => (int) $id)->all();

        if (array_key_exists('tags', $data)) {
            $target = $this->resolveIds($data['tags'] ?? []);
        } else {
            $remove = array_map('intval', $data['remove'] ?? []);
            $target = array_values(array_diff(
                array_unique(array_merge($current, $this->resolveIds($data['add'] ?? []))),
                $remove,
            ));
        }

        $changes = $this->media->tags()->sync($target);
        $attached = array_map('intval', $changes['attached']);
        $detached = array_map('intval', $changes['detached']);

        // Not a failure for the caller; success: false only keeps audit()
        // from recording a change that did not happen
        if ($attached === [] && $detached === []) {
            return new ActionResult(success: false, model: $this->media, metadata: ['changed' => false]);
        }

        $names = MediaTag::whereIn('id', array_merge($attached, $detached))->pluck('name', 'id');

        return ActionResult::success(
            model: $this->media,
            label: (string) $this->media->getAttribute('name'),
            metadata: [
                'changed' => true,
                'added' => $names->only($attached)->values()->all(),
                'removed' => $names->only($detached)->values()->all(),
            ],
        );
    }

    /**
     * Ids of the tags with these names, creating the ones that do not exist
     * yet. Names are normalised first; empty ones are dropped.
     *
     * @param  array<int, mixed>  $names
     * @return array<int, int>
     */
    private function resolveIds(array $names): array
    {
        $ids = [];
        foreach ($names as $name) {
            $name = MediaTag::normalizeName((string) $name);
            if ($name === '') {
                continue;
            }
            $ids[] = (int) MediaTag::firstOrCreate(['name' => $name])->getKey();
        }

        return array_values(array_unique($ids));
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildAuditContext(array $data, ActionResult $result): array
    {
        return [
            'added' => $result->metadata['added'] ?? [],
            'removed' => $result->metadata['removed'] ?? [],
        ];
    }
}
