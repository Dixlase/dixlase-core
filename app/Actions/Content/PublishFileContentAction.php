<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
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

namespace App\Actions\Content;

use App\Actions\AbstractAction;
use App\Contracts\Action\Actor;
use App\Contracts\Content\PublishesFileContent;
use App\Contracts\Revisionable;
use App\DTO\Action\ActionResult;
use App\Enums\Permission;
use App\Services\Content\FileContentPublisher;
use App\Services\RevisionService;
use Illuminate\Database\Eloquent\Model;

/**
 * Publish the files of file-stored content from the admin
 *
 * Takes the files in as the published copy, moves both hashes, records a
 * manual revision when the model is {@see Revisionable}, and audits it as
 * `{prefix}.published_from_file`. Fails when the files hold nothing that is
 * not published already.
 */
class PublishFileContentAction extends AbstractAction
{
    /** @var list<string> */
    private array $parts = [];

    /**
     * @param  PublishesFileContent&Model  $model
     * @param  Permission|null  $permission  The permission the owner requires to edit the content
     */
    public function __construct(
        protected readonly PublishesFileContent $model,
        protected readonly ?Permission $permission,
        protected readonly FileContentPublisher $publisher,
    ) {}

    protected function requiredPermission(): ?Permission
    {
        return $this->permission;
    }

    protected function auditAction(): string
    {
        return $this->model->fileContentAuditPrefix().'.published_from_file';
    }

    protected function useTransaction(): bool
    {
        return false;
    }

    protected function handle(Actor $actor, array $data): ActionResult
    {
        if ($this->publisher->pendingChanges($this->model) === []) {
            return ActionResult::failure(__('common.file_changes.nothing_to_publish'));
        }

        $this->parts = $this->publisher->publish($this->model);

        if ($this->model instanceof Revisionable) {
            app(RevisionService::class)->record(
                $this->model,
                RevisionService::TYPE_MANUAL,
                $actor->getActorId(),
            );
        }

        return ActionResult::success($this->model);
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildAuditContext(array $data, ActionResult $result): array
    {
        return [
            'parts' => $this->parts,
            // The hash the record now publishes. Together with the
            // file_changed entry's published_hash it says which copy this
            // approval moved the record from and to.
            'published_hash' => FileContentPublisher::hash(
                $this->publisher->publishedParts($this->model),
            ),
        ];
    }
}
