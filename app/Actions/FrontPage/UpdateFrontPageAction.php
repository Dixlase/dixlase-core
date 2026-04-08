<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
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
        $newStorageTypeEnum = ContentStorageType::fromSlug($data['storage_type']);
        $oldStorageTypeEnum = $this->frontPage->storage_type;
        $editorTypeSlug = $this->frontPage->editor_type->slug();
        $locale = $this->frontPage->lang;
        $content = $data['content'] ?? '';
        $isHtmlEditor = $this->frontPage->editor_type === ContentEditorType::HTML;
        $customJs = $isHtmlEditor ? ($data['custom_js'] ?? null) : null;
        $customCss = $isHtmlEditor ? ($data['custom_css'] ?? null) : null;

        // 保存方法が変更された場合の処理
        if ($oldStorageTypeEnum !== $newStorageTypeEnum) {
            if ($oldStorageTypeEnum === ContentStorageType::FILE && $newStorageTypeEnum === ContentStorageType::DATABASE) {
                $this->contentService->deleteFile('main_content', $locale, $editorTypeSlug);
                if ($isHtmlEditor) {
                    $this->contentService->deleteJsFile('main_content', $locale);
                    $this->contentService->deleteCssFile('main_content', $locale);
                }
            }
        }

        // ファイル保存の場合はファイルにも保存
        if ($newStorageTypeEnum === ContentStorageType::FILE) {
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

        // 常にDBにもコンテンツを保存（バックアップ）
        $updateData = [
            'content' => $content,
            'storage_type' => $newStorageTypeEnum,
        ];
        if ($isHtmlEditor) {
            $updateData['custom_js'] = $customJs;
            $updateData['custom_css'] = $customCss;
        }
        $this->frontPage->update($updateData);

        return ActionResult::success(
            model: $this->frontPage,
            label: $this->frontPage->lang ?? 'default',
        );
    }
}
