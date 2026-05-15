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

declare(strict_types=1);

namespace App\Actions\Media;

use App\Actions\AbstractAction;
use App\Contracts\Action\Actor;
use App\DTO\Action\ActionResult;
use App\Enums\Permission;
use App\Models\Media;
use Illuminate\Support\Facades\Storage;

/**
 * Delete a media file and its database record
 *
 * Handles storage file cleanup and model deletion.
 */
class DeleteMediaAction extends AbstractAction
{
    public function __construct(
        protected readonly Media $media,
    ) {}

    protected function requiredPermission(): ?Permission
    {
        return Permission::MEDIA_DELETE;
    }

    protected function auditAction(): string
    {
        return 'media.deleted';
    }

    protected function auditCategory(): string
    {
        return 'content';
    }

    protected function useTransaction(): bool
    {
        return false;
    }

    protected function handle(Actor $actor, array $data): ActionResult
    {
        $label = $this->media->name;
        $disk = config('admin.files.storageDisk', 'public');
        $mediaPath = config('admin.files.mediaPath', 'media');

        // Resolve file path (handles both full path and filename-only storage)
        $filePath = str_starts_with($this->media->path, $mediaPath)
            ? $this->media->path
            : $mediaPath.'/'.$this->media->path;

        if (Storage::disk($disk)->exists($filePath)) {
            Storage::disk($disk)->delete($filePath);
        }

        $this->media->delete();

        return new ActionResult(
            success: true,
            targetType: Media::class,
            targetId: $this->media->id,
            targetLabel: $label,
            metadata: [
                'file_name' => $label,
                'type' => $this->media->type,
                'file_size' => $this->media->file_size,
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildAuditContext(array $data, ActionResult $result): array
    {
        return [
            'file_name' => $result->metadata['file_name'] ?? null,
            'type' => $result->metadata['type'] ?? null,
            'file_size' => $result->metadata['file_size'] ?? null,
        ];
    }
}
