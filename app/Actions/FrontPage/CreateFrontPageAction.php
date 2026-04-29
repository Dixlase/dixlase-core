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

namespace App\Actions\FrontPage;

use App\Actions\AbstractAction;
use App\Contracts\Action\Actor;
use App\DTO\Action\ActionResult;
use App\Enums\ContentEditorType;
use App\Enums\ContentStatus;
use App\Enums\ContentStorageType;
use App\Enums\Permission;
use App\Models\FrontPage;
use App\Services\FrontPageContentService;
use App\Services\FrontPageRevisionService;

/**
 * Create a front page with dual-write (DB + file) support
 *
 * Handles content storage, editor-specific JS/CSS files,
 * and always keeps a DB backup regardless of storage type.
 */
class CreateFrontPageAction extends AbstractAction
{
    public function __construct(
        protected readonly FrontPageContentService $contentService,
        protected readonly FrontPageRevisionService $revisionService,
    ) {}

    protected function requiredPermission(): ?Permission
    {
        return Permission::SETTINGS_BASE;
    }

    protected function auditAction(): string
    {
        return 'front_page.created';
    }

    protected function useTransaction(): bool
    {
        return false;
    }

    protected function handle(Actor $actor, array $data): ActionResult
    {
        $storageTypeEnum = ContentStorageType::fromSlug($data['storage_type']);
        $editorTypeEnum = ContentEditorType::fromSlug($data['editor_type']);
        $editorTypeSlug = $editorTypeEnum->slug();
        $content = $data['content'] ?? '';
        $customJs = $data['custom_js'] ?? null;
        $customCss = $data['custom_css'] ?? null;

        // HTML エディタ以外は JS/CSS を無視
        if ($editorTypeEnum !== ContentEditorType::HTML) {
            $customJs = null;
            $customCss = null;
        }

        // ファイル保存の場合はファイルにも保存
        if ($storageTypeEnum === ContentStorageType::FILE) {
            $this->contentService->saveToFile('main_content', $data['lang'], $editorTypeSlug, $content);

            if ($editorTypeEnum === ContentEditorType::HTML) {
                if ($customJs !== null && $customJs !== '') {
                    $this->contentService->saveJsToFile('main_content', $data['lang'], $customJs);
                }
                if ($customCss !== null && $customCss !== '') {
                    $this->contentService->saveCssToFile('main_content', $data['lang'], $customCss);
                }
            }
        }

        // 常にDBにもコンテンツを保存（バックアップ）
        $frontPage = FrontPage::create([
            'page_type' => 'main_content',
            'lang' => $data['lang'],
            'content' => $content,
            'custom_js' => $customJs,
            'custom_css' => $customCss,
            'editor_type' => $editorTypeEnum,
            'storage_type' => $storageTypeEnum,
            'status' => ContentStatus::PUBLISHED,
        ]);

        // 初回作成時のリビジョンを記録（ユーザーの明示保存なので manual として扱う）
        $this->revisionService->record(
            $frontPage,
            type: \App\Models\FrontPageRevision::TYPE_MANUAL,
            userId: $actor->getActorId(),
        );

        return ActionResult::success(
            model: $frontPage,
            label: $frontPage->lang ?? 'default',
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildAuditContext(array $data, ActionResult $result): array
    {
        return [
            'editor_type' => $result->model?->editor_type ?? null,
            'storage_type' => $result->model?->storage_type ?? null,
        ];
    }
}
