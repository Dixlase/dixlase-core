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

namespace App\Actions\Security;

use App\Actions\AbstractAction;
use App\Contracts\Action\Actor;
use App\Contracts\Repositories\SecuritySettingRepositoryInterface;
use App\DTO\Action\ActionResult;
use App\Enums\Permission;
use App\Facades\Audit;

/**
 * Rollback CSP settings to previous state from session
 */
class RollbackCspSettingsAction extends AbstractAction
{
    protected const SETTING_KEYS = [
        'csp_enabled', 'csp_mode', 'csp_log_violations', 'csp_exclude_dev_tools',
        'csp_trusted_domains', 'csp_denied_domains', 'csp_custom_directives',
        'csp_custom_directives_mode', 'csp_blocklist_check_enabled',
        'csp_blocklist_action', 'csp_blocklist_enabled_categories',
    ];

    public function __construct(
        protected readonly SecuritySettingRepositoryInterface $repository,
    ) {}

    protected function requiredPermission(): ?Permission
    {
        return Permission::SETTINGS_SECURITY;
    }

    protected function auditAction(): string
    {
        return 'security.csp';
    }

    protected function auditCategory(): string
    {
        return 'system';
    }

    protected function useTransaction(): bool
    {
        return false;
    }

    protected function handle(Actor $actor, array $data): ActionResult
    {
        $previousSettings = session('csp_previous_settings');

        if (! $previousSettings) {
            return ActionResult::failure('No previous settings found for rollback.');
        }

        $before = $this->repository->getMultiple(static::SETTING_KEYS);

        foreach ($previousSettings as $key => $value) {
            $this->repository->set($key, $value);
        }

        $after = $this->repository->getMultiple(static::SETTING_KEYS);

        // Clear session
        session()->forget('csp_pending_confirmation');
        session()->forget('csp_previous_settings');
        session()->forget('csp_confirmation_expires_at');

        return new ActionResult(
            success: true,
            metadata: ['before' => $before, 'after' => $after],
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

        Audit::logBulkSettingsChange(
            'security.csp',
            $result->metadata['before'] ?? [],
            $result->metadata['after'] ?? [],
            $actor->toAuditMorph(),
        );
    }
}
