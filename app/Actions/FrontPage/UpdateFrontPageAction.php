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

namespace App\Actions\FrontPage;

use App\Actions\AbstractAction;
use App\Contracts\Action\Actor;
use App\DTO\Action\ActionResult;
use App\Enums\ContentEditorType;
use App\Enums\ContentStorageType;
use App\Enums\Permission;
use App\Models\FrontPage;
use App\Services\FrontPageContentService;
use App\Services\FrontPageRevisionService;

/**
 * Update a front page with storage type migration support
 *
 * Handles FILE→DB migration (deletes files), dual-write,
 * and editor-specific JS/CSS file management.
 */
class UpdateFrontPageAction extends AbstractAction
{
    public function __construct(
        protected readonly FrontPage $frontPage,
        protected readonly FrontPageContentService $contentService,
        protected readonly FrontPageRevisionService $revisionService,
    ) {}

    protected function requiredPermission(): ?Permission
    {
        return Permission::SETTINGS_BASE;
    }

    protected function auditAction(): string
    {
        return 'front_page.updated';
    }

    protected function useTransaction(): bool
    {
        return false;
    }

    protected function handle(Actor $actor, array $data): ActionResult
    {
        // Storage format can only be selected on initial creation. Existing value is used as-is when editing.
        $storageTypeEnum = $this->frontPage->storage_type;
        $editorTypeSlug = $this->frontPage->editor_type->slug();
        $locale = $this->frontPage->lang;
        $content = $data['content'] ?? '';
        $isHtmlEditor = $this->frontPage->editor_type === ContentEditorType::HTML;
        $customJs = $isHtmlEditor ? ($data['custom_js'] ?? null) : null;
        $customCss = $isHtmlEditor ? ($data['custom_css'] ?? null) : null;

        // If file storage, also save to file
        if ($storageTypeEnum === ContentStorageType::FILE) {
            $this->contentService->saveToFile('main_content', $locale, $editorTypeSlug, $content);

            if ($isHtmlEditor) {
                if ($customJs !== null && $customJs !== '') {
                    $this->contentService->saveJsToFile('main_content', $locale, $customJs);
                } else {
                    $this->contentService->deleteJsFile('main_content', $locale);
                }
                if ($customCss !== null && $customCss !== '') {
                    $this->contentService->saveCssToFile('main_content', $locale, $customCss);
                } else {
                    $this->contentService->deleteCssFile('main_content', $locale);
                }
            }
        }

        // Also save content to DB (serves as backup and revision source)
        $updateData = ['content' => $content];
        if ($isHtmlEditor) {
            $updateData['custom_js'] = $customJs;
            $updateData['custom_css'] = $customCss;
        }
        $this->frontPage->update($updateData);

        // Record post-save state to revision (treated as manual since it's an explicit user save)
        // Skip if no difference from previous revision
        $this->revisionService->record(
            $this->frontPage->fresh() ?? $this->frontPage,
            type: \App\Models\FrontPageRevision::TYPE_MANUAL,
            userId: $actor->getActorId(),
        );

        return ActionResult::success(
            model: $this->frontPage,
            label: $this->frontPage->lang ?? 'default',
        );
    }
}
