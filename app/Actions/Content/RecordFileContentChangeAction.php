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

namespace App\Actions\Content;

use App\Actions\AbstractAction;
use App\Contracts\Action\Actor;
use App\Contracts\Content\PublishesFileContent;
use App\Contracts\Revisionable;
use App\DTO\Action\ActionResult;
use App\Enums\ContentPublishMode;
use App\Enums\Permission;
use App\Facades\Audit;
use App\Models\AuditLog;
use App\Services\Content\FileContentPublisher;
use App\Services\RevisionService;
use Illuminate\Database\Eloquent\Model;

/**
 * Record a change made to file-stored content outside the admin
 *
 * Run by {@see FileContentPublisher::check()} when the files differ from the
 * published copy and from what was last seen. Under the file save mode the
 * files are taken in as the published copy and recorded as a revision; under
 * the admin mode they wait to be published. Either way the change is
 * audited once.
 */
class RecordFileContentChangeAction extends AbstractAction
{
    private ContentPublishMode $mode;

    /** @var list<string> */
    private array $parts = [];

    /**
     * @param  PublishesFileContent&Model  $model
     * @param  array<string, string>  $files  The files as just read
     */
    public function __construct(
        protected readonly PublishesFileContent $model,
        protected readonly FileContentPublisher $publisher,
        protected readonly array $files,
    ) {
        $this->mode = $model->fileContentPublishMode();
    }

    protected function requiredPermission(): ?Permission
    {
        return null;
    }

    protected function auditAction(): string
    {
        return $this->model->fileContentAuditPrefix().'.file_changed';
    }

    protected function useTransaction(): bool
    {
        return false;
    }

    protected function handle(Actor $actor, array $data): ActionResult
    {
        $this->parts = $this->publisher->changedParts($this->model, $this->files);
        $fileHash = FileContentPublisher::hash($this->files);

        if ($this->mode === ContentPublishMode::FILE_SAVE) {
            $values = [];
            foreach ($this->model->fileContentParts() as $part => $column) {
                $values[$column] = $this->files[$part];
            }
            $this->model->forceFill($values)->save();
            $this->publisher->storeHashes($this->model, $fileHash, $fileHash);

            if ($this->model instanceof Revisionable) {
                app(RevisionService::class)->record($this->model, RevisionService::TYPE_AUTO);
            }
        } else {
            $this->publisher->storeHashes(
                $this->model,
                FileContentPublisher::hash($this->publisher->publishedParts($this->model)),
                $fileHash,
            );
        }

        return ActionResult::success($this->model);
    }

    /**
     * A change outside the admin is worth a look, so it is a warning
     */
    protected function audit(Actor $actor, array $data, ActionResult $result): void
    {
        Audit::log([
            'category' => $this->auditCategory(),
            'action' => $this->auditAction(),
            'severity' => AuditLog::SEVERITY_WARNING,
            'actor' => $actor->toAuditMorph(),
            'actor_name' => $actor->getActorName(),
            'target' => $result->model,
            'context' => $this->buildAuditContext($data, $result),
            'outcome' => 'success',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildAuditContext(array $data, ActionResult $result): array
    {
        return [
            'publish_mode' => $this->mode->value,
            'parts' => $this->parts,
            'published' => $this->mode === ContentPublishMode::FILE_SAVE,
        ];
    }
}
