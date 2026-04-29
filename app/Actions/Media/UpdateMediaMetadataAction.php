<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
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

namespace App\Actions\Media;

use App\Actions\AbstractAction;
use App\Contracts\Action\Actor;
use App\Contracts\Repositories\MediaRepositoryInterface;
use App\DTO\Action\ActionResult;
use App\Enums\Permission;
use App\Models\Media;

/**
 * Update media metadata (caption, alt_text, description)
 */
class UpdateMediaMetadataAction extends AbstractAction
{
    public function __construct(
        protected readonly Media $media,
        protected readonly MediaRepositoryInterface $mediaRepository,
    ) {}

    protected function requiredPermission(): ?Permission
    {
        return Permission::MEDIA_UPLOAD;
    }

    protected function auditAction(): string
    {
        return 'media.updated';
    }

    protected function handle(Actor $actor, array $data): ActionResult
    {
        $this->mediaRepository->update($this->media->id, [
            'caption' => $data['caption'] ?? null,
            'alt_text' => $data['alt_text'] ?? null,
            'description' => $data['description'] ?? null,
        ]);

        return ActionResult::success(
            model: $this->media,
            label: $this->media->name,
            metadata: ['updated_fields' => array_keys($data)],
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildAuditContext(array $data, ActionResult $result): array
    {
        return [
            'updated_fields' => $result->metadata['updated_fields'] ?? [],
        ];
    }
}
