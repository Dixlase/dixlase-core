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

namespace App\Actions\FrontPage;

use App\Actions\AbstractAction;
use App\Contracts\Action\Actor;
use App\DTO\Action\ActionResult;
use App\Enums\ContentEditorType;
use App\Enums\ContentStorageType;
use App\Enums\Permission;
use App\Models\FrontPage;
use App\Services\FrontPageContentService;

/**
 * Delete a front page and its associated files
 *
 * Cleans up content files, JS/CSS files, and the database record.
 */
class DeleteFrontPageAction extends AbstractAction
{
    public function __construct(
        protected readonly FrontPage $frontPage,
        protected readonly FrontPageContentService $contentService,
    ) {}

    protected function requiredPermission(): ?Permission
    {
        return Permission::SETTINGS_BASE;
    }

    protected function auditAction(): string
    {
        return 'front_page.deleted';
    }

    protected function useTransaction(): bool
    {
        return false;
    }

    protected function handle(Actor $actor, array $data): ActionResult
    {
        $label = $this->frontPage->lang ?? 'default';

        // ファイル保存の場合、関連ファイルも削除
        if ($this->frontPage->storage_type === ContentStorageType::FILE) {
            $this->contentService->deleteFile(
                'main_content',
                $this->frontPage->lang,
                $this->frontPage->editor_type->slug()
            );

            if ($this->frontPage->editor_type === ContentEditorType::HTML) {
                $this->contentService->deleteJsFile('main_content', $this->frontPage->lang);
                $this->contentService->deleteCssFile('main_content', $this->frontPage->lang);
            }
        }

        $this->frontPage->delete();

        return new ActionResult(
            success: true,
            targetType: FrontPage::class,
            targetId: $this->frontPage->id,
            targetLabel: $label,
        );
    }
}
