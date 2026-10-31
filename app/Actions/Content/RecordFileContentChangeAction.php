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
    /**
     * Nobody known made this change: it arrived through the files
     *
     * @api Read it from the audit context to tell such an entry apart from
     *      one Dixlase wrote by itself.
     */
    public const SOURCE_OUTSIDE_ADMIN = 'outside_admin';

    /** The mode in effect here; see {@see FileContentPublisher::effectivePublishMode()} */
    private ContentPublishMode $mode;

    /** The mode the owner chose, which may not be in effect here */
    private ContentPublishMode $selectedMode;

    /** @var list<string> */
    private array $parts = [];

    /** @var list<string> The changed parts this mode did not take in */
    private array $held = [];

    /**
     * @param  PublishesFileContent&Model  $model
     * @param  array<string, string>  $files  The files as just read
     * @param  string  $detectedBy  {@see FileContentPublisher::DETECTED_BY_RENDER}
     *                              or {@see FileContentPublisher::DETECTED_BY_SCAN}
     * @param  string  $publishedHash  The published copy's hash as it stood
     *                                 before this change, taken by the caller
     *                                 because handle() may move it
     */
    public function __construct(
        protected readonly PublishesFileContent $model,
        protected readonly FileContentPublisher $publisher,
        protected readonly array $files,
        protected readonly string $detectedBy = FileContentPublisher::DETECTED_BY_RENDER,
        protected readonly string $publishedHash = '',
    ) {
        $this->selectedMode = $model->fileContentPublishMode();
        $this->mode = $publisher->effectivePublishMode($model);
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

        $taken = $this->publisher->takeableParts($this->model, $this->files);
        $this->held = array_values(array_diff($this->parts, $taken));

        if ($taken !== []) {
            // Only the parts this mode may take in. A part that can carry a
            // script is left on the record as it was published, so the site
            // keeps rendering the reviewed copy until someone publishes the
            // new one from the admin.
            $columns = $this->model->fileContentParts();
            $values = [];
            foreach ($taken as $part) {
                $values[$columns[$part]] = $this->files[$part];
            }
            $this->model->forceFill($values)->save();

            if ($this->model instanceof Revisionable) {
                app(RevisionService::class)->record($this->model, RevisionService::TYPE_AUTO);
            }
        }

        // The published hash comes from the columns as they now stand -- a
        // mix of what was just taken in and what was held -- and the file
        // hash from the files. The two are equal only when nothing was held,
        // so a held part keeps the record pending without being asked to.
        $this->publisher->storeHashes(
            $this->model,
            FileContentPublisher::hash($this->publisher->publishedParts($this->model)),
            $fileHash,
        );

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
            // The actor is the system, because the application cannot know
            // who wrote a file outside it. Saying so in the entry keeps a
            // reader from taking it for a change Dixlase made by itself.
            'source' => self::SOURCE_OUTSIDE_ADMIN,
            'detected_by' => $this->detectedBy,
            // What the record published before this change, and what the
            // files held when it was found.
            'published_hash' => $this->publishedHash,
            'file_hash' => FileContentPublisher::hash($this->files),
            // The mode in effect, and the one chosen: the file save mode
            // falls back to the admin mode outside a local environment.
            'publish_mode' => $this->mode->value,
            'selected_publish_mode' => $this->selectedMode->value,
            'parts' => $this->parts,
            // The parts that were not taken in and are waiting to be
            // published from the admin: every changed part, unless the file
            // save mode is in effect, which takes them all in.
            'held' => $this->held,
            // True only when the whole change went live. It used to be "the
            // mode is file save", which stopped being the same thing once a
            // part can be held.
            'published' => $this->held === [],
        ];
    }
}
