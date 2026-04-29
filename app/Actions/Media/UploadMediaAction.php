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
use App\Services\Media\MediaSecurityService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Upload a single media file
 *
 * Handles security validation, storage, SVG sanitization,
 * image dimension extraction, and database record creation.
 */
class UploadMediaAction extends AbstractAction
{
    public function __construct(
        protected readonly MediaSecurityService $mediaSecurityService,
        protected readonly MediaRepositoryInterface $mediaRepository,
    ) {}

    protected function requiredPermission(): ?Permission
    {
        return Permission::MEDIA_UPLOAD;
    }

    protected function auditAction(): string
    {
        return 'media.uploaded';
    }

    protected function useTransaction(): bool
    {
        return false;
    }

    /**
     * @param  array{file: UploadedFile, uploaded_by: int}  $data
     */
    protected function handle(Actor $actor, array $data): ActionResult
    {
        /** @var UploadedFile $file */
        $file = $data['file'];
        $uploadedBy = $data['uploaded_by'];

        // Security validation
        $securityResult = $this->mediaSecurityService->validateUpload($file);
        if (! $securityResult->isValid()) {
            return ActionResult::failure($securityResult->getFirstError(), [
                'file_name' => $file->getClientOriginalName(),
            ]);
        }

        // Store file
        try {
            $path = $file->store(config('admin.files.mediaPath'), config('admin.files.storageDisk'));
            $fileName = basename($path);

            // SVG sanitization
            if (strtolower($file->getClientOriginalExtension()) === 'svg') {
                $fullPath = Storage::disk(config('admin.files.storageDisk'))->path($path);
                $this->mediaSecurityService->sanitizeSvgIfNeeded($fullPath);
            }
        } catch (\Exception $e) {
            return ActionResult::failure(__('admin/media/index.error.save_failed'), [
                'file_name' => $file->getClientOriginalName(),
            ]);
        }

        // Extract image dimensions
        $mimeType = $file->getMimeType();
        $width = null;
        $height = null;

        if (str_starts_with($mimeType, 'image/') && $mimeType !== 'image/svg+xml') {
            $storedPath = Storage::disk(config('admin.files.storageDisk'))->path($path);
            $imageSize = @getimagesize($storedPath);
            if ($imageSize !== false) {
                $width = $imageSize[0];
                $height = $imageSize[1];
            }
        }

        // Create database record
        $media = $this->mediaRepository->create([
            'name' => $file->getClientOriginalName(),
            'path' => $fileName,
            'type' => $mimeType,
            'file_size' => $file->getSize(),
            'width' => $width,
            'height' => $height,
            'uploaded_by' => $uploadedBy,
        ]);

        return ActionResult::success(
            model: $media,
            label: $media->name,
            metadata: [
                'warnings' => $securityResult->hasWarnings()
                    ? array_map(fn ($w) => $file->getClientOriginalName().': '.$w['message'], $securityResult->getWarnings())
                    : [],
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildAuditContext(array $data, ActionResult $result): array
    {
        $model = $result->model;

        return [
            'file_name' => $model?->name,
            'type' => $model?->type,
            'file_size' => $model?->file_size,
        ];
    }
}
