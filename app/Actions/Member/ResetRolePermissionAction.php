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

namespace App\Actions\Member;

use App\Actions\AbstractAction;
use App\Contracts\Action\Actor;
use App\DTO\Action\ActionResult;
use App\Enums\Permission;
use App\Facades\Audit;
use App\Models\AuditLog;
use App\Models\RolePermissionOverride;
use App\Services\PermissionRegistry;

/**
 * Return one admin menu's permission to its default by removing the override.
 *
 * The reset button on the roles screen posts a single menu key, so this is a
 * separate action from {@see UpdateRolePermissionsAction} rather than a mode of
 * it. A key that carries no override is left alone and writes no audit row.
 */
final class ResetRolePermissionAction extends AbstractAction
{
    protected function requiredPermission(): Permission
    {
        return Permission::MEMBERS_MANAGE_ROLES;
    }

    protected function auditAction(): string
    {
        return AuditLog::ACTION_ROLE_CHANGED;
    }

    protected function auditCategory(): string
    {
        return AuditLog::CATEGORY_ACCOUNT;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handle(Actor $actor, array $data): ActionResult
    {
        $menuKey = (string) ($data['menu_key'] ?? '');
        $pluginSlug = $data['plugin_slug'] ?? null;
        $pluginSlug = is_string($pluginSlug) && $pluginSlug !== '' ? $pluginSlug : null;

        if ($menuKey === '') {
            return ActionResult::failure('menu_key is required');
        }

        $effective = $pluginSlug !== null
            ? PermissionRegistry::getPluginEffective($pluginSlug, $menuKey)
            : PermissionRegistry::getEffective($menuKey);

        $before = $effective === null ? null : [
            'access_roles' => (int) $effective['access_roles'],
            'view_roles' => (int) $effective['view_roles'],
        ];

        $after = $effective === null ? null : [
            'access_roles' => (int) $effective['default_access_roles'],
            'view_roles' => (int) $effective['default_view_roles'],
        ];

        $removed = $pluginSlug !== null
            ? RolePermissionOverride::resetPluginOverride($pluginSlug, $menuKey)
            : RolePermissionOverride::resetCoreOverride($menuKey);

        PermissionRegistry::clearCache();

        $label = $pluginSlug !== null ? $pluginSlug.'.'.$menuKey : $menuKey;

        return new ActionResult(
            success: true,
            targetLabel: $label,
            metadata: [
                'diff' => $removed && $before !== null && $before !== $after
                    ? [$label => ['from' => $before, 'to' => $after]]
                    : [],
                'removed' => $removed,
            ],
        );
    }

    /**
     * Record the reset only when an override was actually removed.
     *
     * @param  array<string, mixed>  $data
     */
    protected function audit(Actor $actor, array $data, ActionResult $result): void
    {
        if (! $result->success) {
            return;
        }

        /** @var array<string, array{from: array<string, int>, to: array<string, int>}> $diff */
        $diff = $result->metadata['diff'] ?? [];

        if ($diff === []) {
            return;
        }

        Audit::log([
            'category' => $this->auditCategory(),
            'action' => $this->auditAction(),
            'actor' => $actor->toAuditMorph(),
            'actor_name' => $actor->getActorName(),
            'target_label' => $result->targetLabel,
            'context' => [
                'reset' => $result->targetLabel,
                'changed' => $diff,
                'changed_count' => count($diff),
            ],
            'outcome' => AuditLog::OUTCOME_SUCCESS,
        ]);
    }
}
