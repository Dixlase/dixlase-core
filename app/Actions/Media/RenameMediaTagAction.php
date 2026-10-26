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
use App\Models\MediaTag;
use Illuminate\Validation\ValidationException;

/**
 * Rename a media library tag (#766). The files that carry it keep it.
 */
class RenameMediaTagAction extends AbstractAction
{
    public function __construct(
        protected readonly MediaTag $tag,
    ) {}

    protected function requiredPermission(): ?Permission
    {
        return Permission::MEDIA_UPLOAD;
    }

    protected function auditAction(): string
    {
        return 'media.tag_renamed';
    }

    /**
     * A name another tag of this site already has is refused: two tags
     * cannot share a name, and folding one into the other is a merge.
     */
    protected function validate(Actor $actor, array $data): void
    {
        $name = MediaTag::normalizeName((string) ($data['name'] ?? ''));

        if ($name === '') {
            throw ValidationException::withMessages([
                'name' => __('admin/media/tags.error.name_required'),
            ]);
        }

        $taken = MediaTag::where('name', $name)->whereKeyNot($this->tag->getKey())->exists();
        if ($taken) {
            throw ValidationException::withMessages([
                'name' => __('admin/media/tags.error.name_taken', ['name' => $name]),
            ]);
        }
    }

    protected function handle(Actor $actor, array $data): ActionResult
    {
        $from = $this->tag->name;
        $this->tag->update(['name' => MediaTag::normalizeName((string) $data['name'])]);

        return ActionResult::success(
            model: $this->tag,
            label: $this->tag->name,
            metadata: ['from' => $from, 'to' => $this->tag->name],
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildAuditContext(array $data, ActionResult $result): array
    {
        return [
            'from' => $result->metadata['from'] ?? null,
            'to' => $result->metadata['to'] ?? null,
        ];
    }
}
