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

/**
 * Delete a media library tag (#766). The files that carried it stay; they
 * only lose the tag.
 */
class DeleteMediaTagAction extends AbstractAction
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
        return 'media.tag_deleted';
    }

    protected function handle(Actor $actor, array $data): ActionResult
    {
        $name = $this->tag->name;
        $count = $this->tag->media()->count();

        // Detached here rather than left to the cascade, which SQLite applies
        // only when foreign keys are switched on
        $this->tag->media()->detach();
        $this->tag->delete();

        return new ActionResult(
            success: true,
            targetType: MediaTag::class,
            targetId: $this->tag->getKey(),
            targetLabel: $name,
            metadata: ['name' => $name, 'media_count' => $count],
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildAuditContext(array $data, ActionResult $result): array
    {
        return [
            'name' => $result->metadata['name'] ?? null,
            'media_count' => $result->metadata['media_count'] ?? 0,
        ];
    }
}
