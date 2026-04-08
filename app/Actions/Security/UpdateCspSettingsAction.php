<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
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
use App\Enums\CspBlocklistAction;
use App\Enums\CspMode;
use App\Enums\Permission;
use App\Facades\Audit;

/**
 * Update CSP settings with rollback support
 *
 * Saves previous settings to session for rollback,
 * handles form-based and raw directive modes.
 */
class UpdateCspSettingsAction extends AbstractAction
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

    protected function handle(Actor $actor, array $data): ActionResult
    {
        $before = $this->repository->getMultiple(static::SETTING_KEYS);

        // Save current settings to session for rollback
        $previousSettings = [];
        $rollbackKeys = [
            'csp_enabled', 'csp_mode', 'csp_admin_mode', 'csp_log_violations',
            'csp_exclude_dev_tools', 'csp_trusted_domains', 'csp_denied_domains',
            'csp_custom_directives', 'csp_custom_directives_mode',
            'csp_blocklist_check_enabled', 'csp_blocklist_action',
            'csp_blocklist_enabled_categories',
        ];
        foreach ($rollbackKeys as $key) {
            $previousSettings[$key] = $this->repository->get($key);
        }

        session(['csp_previous_settings' => $previousSettings]);
        session(['csp_pending_confirmation' => true]);
        session(['csp_confirmation_expires_at' => now()->addSeconds(10)]);

        // Write settings
        $this->repository->set('csp_enabled', $data['csp_enabled'] ?? false);
        $this->repository->set('csp_mode', $data['csp_mode'] ?? (string) CspMode::default()->value);
        $this->repository->set('csp_log_violations', $data['csp_log_violations'] ?? true);
        $this->repository->set('csp_exclude_dev_tools', $data['csp_exclude_dev_tools'] ?? true);
        $this->repository->set('csp_trusted_domains', $data['csp_trusted_domains'] ?? '');
        $this->repository->set('csp_denied_domains', $data['csp_denied_domains'] ?? '');

        // Custom directives mode
        $directivesMode = $data['csp_custom_directives_mode'] ?? 'form';
        $this->repository->set('csp_custom_directives_mode', $directivesMode);

        if ($directivesMode === 'form') {
            $customDirectives = $this->buildDirectivesFromForm($data);
            $this->repository->set('csp_custom_directives', $customDirectives);
        } else {
            $this->repository->set('csp_custom_directives', $data['csp_custom_directives'] ?? '');
        }

        $this->repository->set('csp_blocklist_check_enabled', $data['csp_blocklist_check_enabled'] ?? false);
        $this->repository->set('csp_blocklist_action', $data['csp_blocklist_action'] ?? (string) CspBlocklistAction::default()->value);

        $categories = $data['csp_blocklist_categories'] ?? [];
        $this->repository->set('csp_blocklist_enabled_categories', implode(',', $categories));

        $after = $this->repository->getMultiple(static::SETTING_KEYS);

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

    /**
     * Build directives JSON from form fields
     */
    private function buildDirectivesFromForm(array $data): string
    {
        $directiveKeys = [
            'script-src' => 'csp_directive_script_src',
            'style-src' => 'csp_directive_style_src',
            'img-src' => 'csp_directive_img_src',
            'connect-src' => 'csp_directive_connect_src',
            'font-src' => 'csp_directive_font_src',
            'frame-src' => 'csp_directive_frame_src',
        ];

        $result = [];
        foreach ($directiveKeys as $directive => $field) {
            $value = trim($data[$field] ?? '');
            if ($value !== '') {
                $domains = array_filter(
                    array_map('trim', explode("\n", $value)),
                    fn (string $line): bool => $line !== ''
                );
                if (! empty($domains)) {
                    $result[$directive] = array_values($domains);
                }
            }
        }

        return ! empty($result) ? json_encode($result, JSON_UNESCAPED_SLASHES) : '';
    }
}
