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
use App\Contracts\Repositories\MediaSettingRepositoryInterface;
use App\DTO\Action\ActionResult;
use App\Enums\Permission;
use App\Facades\Audit;

/**
 * Update media settings (file types, size limits, security options)
 */
class UpdateMediaSettingsAction extends AbstractAction
{
    protected const SETTING_KEYS = [
        'allowed_file_types', 'max_file_size',
        'max_file_size_image', 'max_file_size_video',
        'max_file_size_document', 'max_file_size_archive',
        'svg_sanitization_enabled', 'zip_security_enabled',
        'mime_validation_enabled', 'zip_max_compression_ratio', 'zip_max_file_count',
    ];

    public function __construct(
        protected readonly MediaSettingRepositoryInterface $mediaSettingRepository,
    ) {}

    protected function requiredPermission(): ?Permission
    {
        return Permission::SETTINGS_SYSTEM;
    }

    protected function auditAction(): string
    {
        return 'media.settings_updated';
    }

    protected function auditCategory(): string
    {
        return 'system';
    }

    protected function handle(Actor $actor, array $data): ActionResult
    {
        $before = $this->mediaSettingRepository->getMultiple(static::SETTING_KEYS);

        $this->mediaSettingRepository->set('allowed_file_types', $data['allowed_file_types'] ?? []);
        $this->mediaSettingRepository->set('max_file_size', round(($data['max_file_size'] ?? 2) * 1024));

        $this->mediaSettingRepository->set('max_file_size_image', round(($data['max_file_size_image'] ?? 10) * 1024));
        $this->mediaSettingRepository->set('max_file_size_video', round(($data['max_file_size_video'] ?? 300) * 1024));
        $this->mediaSettingRepository->set('max_file_size_document', round(($data['max_file_size_document'] ?? 30) * 1024));
        $this->mediaSettingRepository->set('max_file_size_archive', round(($data['max_file_size_archive'] ?? 100) * 1024));

        $this->mediaSettingRepository->set('svg_sanitization_enabled', ($data['svg_sanitization_enabled'] ?? false) ? '1' : '0');
        $this->mediaSettingRepository->set('zip_security_enabled', ($data['zip_security_enabled'] ?? false) ? '1' : '0');
        $this->mediaSettingRepository->set('mime_validation_enabled', ($data['mime_validation_enabled'] ?? false) ? '1' : '0');

        $this->mediaSettingRepository->set('zip_max_compression_ratio', $data['zip_max_compression_ratio'] ?? 100);
        $this->mediaSettingRepository->set('zip_max_file_count', $data['zip_max_file_count'] ?? 1000);

        $after = $this->mediaSettingRepository->getMultiple(static::SETTING_KEYS);

        return new ActionResult(
            success: true,
            message: __('admin/media/settings.success.settings_updated'),
            metadata: ['before' => $before, 'after' => $after],
        );
    }

    /**
     * Override audit to use bulk settings change format
     */
    protected function audit(Actor $actor, array $data, ActionResult $result): void
    {
        if (! $result->success) {
            return;
        }

        $model = $actor->toAuditMorph();

        Audit::logBulkSettingsChange(
            $this->auditAction(),
            $result->metadata['before'] ?? [],
            $result->metadata['after'] ?? [],
            $model,
        );
    }
}
