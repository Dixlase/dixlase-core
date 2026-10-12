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

namespace App\Actions\Api;

use App\Actions\AbstractAction;
use App\Contracts\Action\Actor;
use App\DTO\Action\ActionResult;
use App\Facades\Audit;
use App\Models\AuditLog;

/**
 * Shared audit shape for the API key lifecycle.
 *
 * The three operations differ only in what they do and which permission they
 * need; the row they write has the same columns, the same category, and the
 * same rule about its context: **no key material.** The plain key, its hash and
 * its prefix never reach the audit log, so a leaked trail cannot be turned into
 * a working credential. Keys are identified by id and name.
 *
 * The actor is passed as a model because {@see AuditLog::log()} fills
 * `actor_type` / `actor_id` only from one — see core #546, after which this
 * override can go and the base class can be used directly.
 */
abstract class AbstractApiKeyAction extends AbstractAction
{
    protected function auditCategory(): string
    {
        return AuditLog::CATEGORY_SYSTEM;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function audit(Actor $actor, array $data, ActionResult $result): void
    {
        if (! $result->success) {
            return;
        }

        Audit::log([
            'category' => $this->auditCategory(),
            'action' => $this->auditAction(),
            'actor' => $actor->toAuditMorph(),
            'actor_name' => $actor->getActorName(),
            'target' => $result->model,
            'target_label' => $result->targetLabel,
            'context' => $this->buildAuditContext($data, $result),
            'outcome' => AuditLog::OUTCOME_SUCCESS,
        ]);
    }
}
