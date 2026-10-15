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
use App\Enums\MemberRole;
use App\Enums\Permission;
use App\Facades\Audit;
use App\Models\AuditLog;
use App\Models\RolePermissionOverride;
use App\Services\PermissionRegistry;
use Illuminate\Validation\ValidationException;

/**
 * Save the role / permission matrix for core and plugin admin menus.
 *
 * An override row is written only where the submitted values differ from the
 * default; a menu key set back to its default has its override removed. Both
 * directions are a change as far as the audit trail is concerned, so the row
 * records the effective values before and after, keyed by menu key.
 *
 * Validation runs before anything is written (see {@see validate()}), so a
 * rejected form leaves the matrix untouched.
 */
final class UpdateRolePermissionsAction extends AbstractAction
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
     * Reject a submission where edit permission is below view permission.
     *
     * The whole matrix is checked so the operator sees every offending menu key
     * at once rather than one per save.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    protected function validate(Actor $actor, array $data): void
    {
        $errors = [];

        foreach ($this->coreRows($data) as $menuKey => $row) {
            if ($row['access_roles'] < $row['view_roles']) {
                $errors[] = __('admin/members/roles.validation.access_must_be_greater_than_view', [
                    'menu_key' => $menuKey,
                ]);
            }
        }

        foreach ($this->pluginRows($data) as $menuKey => $row) {
            if ($row['access_roles'] < $row['view_roles']) {
                $errors[] = __('admin/members/roles.validation.access_must_be_greater_than_view', [
                    'menu_key' => $menuKey,
                ]);
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages(['permissions' => $errors]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handle(Actor $actor, array $data): ActionResult
    {
        $updatedBy = $actor->getActorId();
        $updatedBy = is_int($updatedBy) ? $updatedBy : null;

        $diff = [];

        foreach ($this->coreRows($data) as $menuKey => $row) {
            $effective = PermissionRegistry::getEffective($menuKey);

            if ($effective === null) {
                continue;
            }

            $before = $this->effectiveValues($effective);
            $after = ['access_roles' => $row['access_roles'], 'view_roles' => $row['view_roles']];

            if ($this->isDefault($effective, $after)) {
                RolePermissionOverride::resetCoreOverride($menuKey);
            } else {
                RolePermissionOverride::setCoreOverride($menuKey, $after['access_roles'], $after['view_roles'], $updatedBy);
            }

            if ($before !== $after) {
                $diff[$menuKey] = ['from' => $before, 'to' => $after];
            }
        }

        foreach ($this->pluginRows($data) as $menuKey => $row) {
            $effective = PermissionRegistry::getPluginEffective($row['plugin_slug'], $row['menu_key']);

            if ($effective === null) {
                continue;
            }

            $before = $this->effectiveValues($effective);
            $after = ['access_roles' => $row['access_roles'], 'view_roles' => $row['view_roles']];

            if ($this->isDefault($effective, $after)) {
                RolePermissionOverride::resetPluginOverride($row['plugin_slug'], $row['menu_key']);
            } else {
                RolePermissionOverride::setPluginOverride(
                    $row['plugin_slug'],
                    $row['menu_key'],
                    $after['access_roles'],
                    $after['view_roles'],
                    $updatedBy,
                );
            }

            if ($before !== $after) {
                $diff[$menuKey] = ['from' => $before, 'to' => $after];
            }
        }

        PermissionRegistry::clearCache();

        return new ActionResult(
            success: true,
            targetLabel: 'role_permissions',
            metadata: ['diff' => $diff],
        );
    }

    /**
     * Write one row describing every menu key whose effective permission moved.
     *
     * A save that changes nothing writes no row: the screen posts the whole
     * matrix on every submit, so recording unchanged saves would bury the real
     * changes.
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
                'changed' => $diff,
                'changed_count' => count($diff),
            ],
            'outcome' => AuditLog::OUTCOME_SUCCESS,
        ]);
    }

    /**
     * Normalise the submitted core rows to `menu key => access/view`.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, array{access_roles: int, view_roles: int}>
     */
    private function coreRows(array $data): array
    {
        $rows = [];

        foreach ((array) ($data['permissions'] ?? []) as $menuKey => $values) {
            $rows[(string) $menuKey] = [
                'access_roles' => isset($values['access_roles']) ? (int) $values['access_roles'] : MemberRole::GUEST->value,
                'view_roles' => isset($values['view_roles']) ? (int) $values['view_roles'] : MemberRole::GUEST->value,
            ];
        }

        return $rows;
    }

    /**
     * Normalise the submitted plugin rows to `slug.menu key => access/view`.
     *
     * The key is prefixed with the plugin slug because a menu key is only
     * unique inside its plugin.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, array{access_roles: int, view_roles: int, plugin_slug: string, menu_key: string}>
     */
    private function pluginRows(array $data): array
    {
        $rows = [];

        foreach ((array) ($data['plugin_permissions'] ?? []) as $pluginSlug => $menuItems) {
            foreach ((array) $menuItems as $menuKey => $values) {
                $rows[$pluginSlug.'.'.$menuKey] = [
                    'access_roles' => isset($values['access_roles']) ? (int) $values['access_roles'] : MemberRole::ADMIN->value,
                    'view_roles' => isset($values['view_roles']) ? (int) $values['view_roles'] : MemberRole::ADMIN->value,
                    'plugin_slug' => (string) $pluginSlug,
                    'menu_key' => (string) $menuKey,
                ];
            }
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $effective
     * @return array<string, int>
     */
    private function effectiveValues(array $effective): array
    {
        return [
            'access_roles' => (int) $effective['access_roles'],
            'view_roles' => (int) $effective['view_roles'],
        ];
    }

    /**
     * @param  array<string, mixed>  $effective
     * @param  array<string, int>  $submitted
     */
    private function isDefault(array $effective, array $submitted): bool
    {
        return $submitted['access_roles'] === (int) $effective['default_access_roles']
            && $submitted['view_roles'] === (int) $effective['default_view_roles'];
    }
}
