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
use App\Enums\Permission;
use App\Models\FrontPage;
use App\Models\FrontPageRevision;
use App\Services\RevisionService;

/**
 * Action to restore front page from specified revision
 *
 * Only when the pre-restore state differs from the latest revision, as TYPE_RESTORE_BACKUP
 * a backup is automatically created (logic on the RevisionService side)
 */
class RestoreFrontPageRevisionAction extends AbstractAction
{
    public function __construct(
        protected readonly FrontPageRevision $revision,
        protected readonly RevisionService $revisionService,
    ) {}

    protected function requiredPermission(): ?Permission
    {
        return Permission::SETTINGS_BASE;
    }

    protected function auditAction(): string
    {
        return 'front_page.revision.restored';
    }

    protected function useTransaction(): bool
    {
        return false;
    }

    protected function handle(Actor $actor, array $data): ActionResult
    {
        /** @var FrontPage $frontPage */
        $frontPage = $this->revisionService->restore($this->revision, $actor->getActorId());

        return ActionResult::success(
            model: $frontPage,
            label: (string) $this->revision->id,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function buildAuditContext(array $data, ActionResult $result): array
    {
        return [
            'revision_id' => $this->revision->id,
            'revision_type' => $this->revision->type,
            'revision_created_at' => $this->revision->created_at?->toIso8601String(),
        ];
    }
}
