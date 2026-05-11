<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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

namespace App\Actions;

use App\Contracts\Action\ActionInterface;
use App\Contracts\Action\Actor;
use App\DTO\Action\ActionResult;
use App\Enums\Permission;
use App\Facades\Audit;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

/**
 * Base class for all CMS actions.
 *
 * Plugins and themes may extend this class to define their own auditable actions.
 *
 * ## Lifecycle (template method)
 *
 * Each call to {@see execute()} runs the following steps in order:
 *
 *   1. {@see authorize()}   — permission check against the Actor.
 *   2. {@see validate()}    — business-rule validation that cannot be expressed
 *                              in a Form Request (cross-field, state-machine,
 *                              site-scoped uniqueness, etc.). No-op by default.
 *   3. {@see handle()}      — the action body. Subclasses MUST implement this.
 *                              Wrapped in a DB transaction unless
 *                              {@see useTransaction()} returns false.
 *   4. {@see audit()}       — write an audit log entry on success.
 *   5. {@see dispatchEvents()} — fire any domain events.
 *
 * The lifecycle order is part of the Plugin API contract. New steps will not
 * be inserted between existing ones within `^0.1`.
 *
 * ## Audit responsibility
 *
 * Actions are the canonical audit producer for the operations they own.
 * If a model touched inside {@see handle()} also uses {@see \App\Traits\AuditableTrait},
 * the trait will fire its own log entry on save, producing a duplicate. Wrap such
 * saves with `$model->withoutAudit(fn () => $model->save())` so the action's
 * richer business-level entry is the single source of truth. See
 * `docs/development/action-layer.md` for the full convention.
 */
abstract class AbstractAction implements ActionInterface
{
    /**
     * Execute the action with authorization, validation, transaction, and audit logging
     *
     * @param  Actor  $actor  The entity performing the operation
     * @param  array<string, mixed>  $data  Validated input data
     * @return ActionResult The result of the operation
     *
     * @throws AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function execute(Actor $actor, array $data): ActionResult
    {
        $this->authorize($actor, $data);
        $this->validate($actor, $data);

        $result = $this->useTransaction()
            ? DB::transaction(fn () => $this->handle($actor, $data))
            : $this->handle($actor, $data);

        $this->audit($actor, $data, $result);
        $this->dispatchEvents($actor, $data, $result);

        return $result;
    }

    /**
     * Perform the business logic
     *
     * This is the main method subclasses must implement.
     *
     * @param  Actor  $actor  The entity performing the operation
     * @param  array<string, mixed>  $data  Validated input data
     */
    abstract protected function handle(Actor $actor, array $data): ActionResult;

    /**
     * Get the permission required for this action
     *
     * Return null if the action does not require a specific permission
     * (e.g. system-only actions that are never called from user context).
     */
    abstract protected function requiredPermission(): ?Permission;

    /**
     * Get the audit log action name
     *
     * Used as the 'action' field in audit logs.
     * Example: 'member.created', 'front_page.updated'
     */
    abstract protected function auditAction(): string;

    /**
     * Authorize the actor before execution
     *
     * @throws AuthorizationException
     */
    protected function authorize(Actor $actor, array $data): void
    {
        $permission = $this->requiredPermission();

        if ($permission !== null && ! $actor->hasPermission($permission)) {
            throw new AuthorizationException(
                "Actor [{$actor->getActorType()}:{$actor->getActorName()}] lacks permission [{$permission->value}]."
            );
        }
    }

    /**
     * Validate input data and business rules between authorize() and handle().
     *
     * Default implementation is a no-op. Override in subclasses for checks
     * that cannot be expressed in a Form Request — cross-field consistency,
     * state-machine transitions, site-scoped uniqueness, etc.
     *
     * Throw {@see \Illuminate\Validation\ValidationException} to abort with a
     * 422-style error. Other exceptions propagate as-is.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validate(Actor $actor, array $data): void
    {
        // No-op by default.
    }

    /**
     * Record an audit log entry after execution
     *
     * Subclasses can override to customize audit context.
     */
    protected function audit(Actor $actor, array $data, ActionResult $result): void
    {
        if (! $result->success) {
            return;
        }

        $model = $actor->toAuditMorph();

        Audit::log([
            'category' => $this->auditCategory(),
            'action' => $this->auditAction(),
            'actor_type' => $model ? get_class($model) : null,
            'actor_id' => $actor->getActorId(),
            'actor_name' => $actor->getActorName(),
            'target_type' => $result->targetType,
            'target_id' => $result->targetId,
            'target_label' => $result->targetLabel,
            'context' => $this->buildAuditContext($data, $result),
            'outcome' => 'success',
        ]);
    }

    /**
     * Dispatch domain events after execution
     *
     * Subclasses can override to fire specific DixlaseEvents.
     */
    protected function dispatchEvents(Actor $actor, array $data, ActionResult $result): void
    {
        // Default: no events. Subclasses override as needed.
    }

    /**
     * Get the audit log category
     *
     * Maps to AuditLog category constants (e.g. 'content', 'account', 'system').
     */
    protected function auditCategory(): string
    {
        return 'content';
    }

    /**
     * Build additional audit context
     *
     * Subclasses can override to include before/after diffs or metadata.
     *
     * @return array<string, mixed>
     */
    protected function buildAuditContext(array $data, ActionResult $result): array
    {
        return [];
    }

    /**
     * Whether to wrap handle() in a database transaction
     *
     * Override and return false for actions that manage their own
     * transactions or perform non-transactional work (e.g. file I/O).
     */
    protected function useTransaction(): bool
    {
        return true;
    }
}
