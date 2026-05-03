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

namespace App\Actions\Settings;

use App\Actions\AbstractAction;
use App\Contracts\Action\Actor;
use App\Contracts\Repositories\SettingRepositoryInterface;
use App\DTO\Action\ActionResult;
use App\Enums\Permission;
use App\Facades\Audit;
use Closure;

/**
 * Generic action for bulk settings updates
 *
 * Handles the common pattern across all settings controllers:
 * capture before state → apply writes → capture after state → audit log.
 *
 * Usage:
 *   UpdateSettingsAction::make(
 *       repository: $this->securitySettingRepository,
 *       settingsPage: 'security.password',
 *       settingKeys: ['password_min_length', 'password_require_uppercase', ...],
 *       writeCallback: function (SettingRepositoryInterface $repo, array $data) {
 *           $repo->set('password_min_length', $data['password_min_length']);
 *           // ...
 *       },
 *   )->execute($actor, $validated);
 */
class UpdateSettingsAction extends AbstractAction
{
    /**
     * @param  SettingRepositoryInterface  $repository  The settings repository
     * @param  string  $settingsPage  Audit log identifier (e.g. 'security.password')
     * @param  array<string>  $settingKeys  Keys to track for before/after diff
     * @param  Closure  $writeCallback  fn(SettingRepositoryInterface $repo, array $data): void
     * @param  Permission|null  $permission  Required permission (default: SETTINGS_BASE)
     * @param  array<string>  $sensitiveKeys  Keys to mask in audit logs
     */
    public function __construct(
        protected readonly SettingRepositoryInterface $repository,
        protected readonly string $settingsPage,
        protected readonly array $settingKeys,
        protected readonly Closure $writeCallback,
        protected readonly ?Permission $permission = Permission::SETTINGS_BASE,
        protected readonly array $sensitiveKeys = [],
    ) {}

    /**
     * Convenience factory
     */
    public static function make(
        SettingRepositoryInterface $repository,
        string $settingsPage,
        array $settingKeys,
        Closure $writeCallback,
        ?Permission $permission = Permission::SETTINGS_BASE,
        array $sensitiveKeys = [],
    ): self {
        return new self($repository, $settingsPage, $settingKeys, $writeCallback, $permission, $sensitiveKeys);
    }

    protected function requiredPermission(): ?Permission
    {
        return $this->permission;
    }

    protected function auditAction(): string
    {
        return $this->settingsPage;
    }

    protected function auditCategory(): string
    {
        return 'system';
    }

    protected function handle(Actor $actor, array $data): ActionResult
    {
        $before = $this->repository->getMultiple($this->settingKeys);

        ($this->writeCallback)($this->repository, $data);

        $after = $this->repository->getMultiple($this->settingKeys);

        return new ActionResult(
            success: true,
            metadata: [
                'before' => $before,
                'after' => $after,
            ],
        );
    }

    /**
     * Override audit to use bulk settings change format
     */
    protected function audit(Actor $actor, array $data, ActionResult $result): void
    {
        if (! $result->success) {
            return;
        }

        $model = $actor->toAuditMorph();

        Audit::logBulkSettingsChange(
            $this->settingsPage,
            $result->metadata['before'] ?? [],
            $result->metadata['after'] ?? [],
            $model,
            $this->sensitiveKeys,
        );
    }
}
