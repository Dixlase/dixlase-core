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

namespace App\Console\Commands\Api;

use App\Facades\Audit;
use App\Models\ApiKey;
use App\Models\AuditLog;
use Illuminate\Console\Command;

/**
 * CLI-only entry point for creating a network-scope API key (site_id = null).
 *
 * Network keys authenticate against any site and are intentionally not
 * exposed through any web UI. Every successful creation is recorded in
 * audit_logs at SEVERITY_CRITICAL so an operator action is always
 * traceable.
 */
class CreateNetworkApiKeyCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'dls:api:create-network-key
                            {--name= : Display name for the key (required)}
                            {--scopes=* : One or more permission scopes (e.g. read:content)}
                            {--environment=live : Environment tag (live or test)}
                            {--rate-limit= : Per-minute request ceiling for this key (optional)}
                            {--expires-days= : Lifetime of the key in days (optional, never expires when omitted)}
                            {--description= : Free-form note stored alongside the key}
                            {--force : Skip the interactive confirmation prompt}';

    /**
     * @var string
     */
    protected $description = 'Issue a network-scope API key (site_id = null) that authenticates against any site. CLI-only.';

    public function handle(): int
    {
        $name = $this->resolveName();
        if ($name === null) {
            return self::FAILURE;
        }

        $environment = (string) $this->option('environment');
        if (! in_array($environment, [ApiKey::ENV_LIVE, ApiKey::ENV_TEST], true)) {
            $this->error(__('admin/command/api-create-network-key.invalid_environment', [
                'environment' => $environment,
            ]));

            return self::FAILURE;
        }

        $scopes = $this->resolveScopes();
        if ($scopes === null) {
            return self::FAILURE;
        }

        $rateLimit = $this->option('rate-limit');
        $rateLimit = $rateLimit !== null ? (int) $rateLimit : null;

        $expiresDays = $this->option('expires-days');
        $expiresAt = $expiresDays !== null ? now()->addDays((int) $expiresDays) : null;

        $description = $this->option('description');
        $description = is_string($description) ? $description : null;

        $this->renderSummary($name, $environment, $scopes, $rateLimit, $expiresAt, $description);

        if (! $this->option('force') && ! $this->confirmDangerousAction()) {
            $this->info(__('admin/command/api-create-network-key.cancelled'));

            return self::SUCCESS;
        }

        $result = ApiKey::generateNetworkKey($name, $scopes, null, [
            'environment' => $environment,
            'rate_limit' => $rateLimit,
            'expires_at' => $expiresAt,
            'description' => $description,
        ]);

        /** @var ApiKey $apiKey */
        $apiKey = $result['model'];
        $plainKey = $result['plain_key'];

        Audit::log([
            'action' => 'network_api_key_created',
            'category' => AuditLog::CATEGORY_SECURITY,
            'severity' => AuditLog::SEVERITY_CRITICAL,
            'outcome' => 'success',
            'context' => [
                'api_key_id' => $apiKey->id,
                'name' => $name,
                'environment' => $environment,
                'scopes' => $scopes,
                'rate_limit' => $rateLimit,
                'expires_at' => $expiresAt?->toIso8601String(),
                'triggered_by' => 'cli',
            ],
        ]);

        $this->renderResult($apiKey, $plainKey);

        return self::SUCCESS;
    }

    private function resolveName(): ?string
    {
        $name = $this->option('name');
        if (is_string($name) && trim($name) !== '') {
            return trim($name);
        }

        $prompted = $this->ask(__('admin/command/api-create-network-key.name_prompt'));
        if (! is_string($prompted) || trim($prompted) === '') {
            $this->error(__('admin/command/api-create-network-key.name_required'));

            return null;
        }

        return trim($prompted);
    }

    /**
     * @return array<int, string>|null Returns null when validation fails.
     */
    private function resolveScopes(): ?array
    {
        $scopes = $this->option('scopes');
        if (! is_array($scopes)) {
            $scopes = [];
        }

        $available = array_keys(ApiKey::availableScopes());
        $unknown = array_diff($scopes, $available);

        if ($unknown !== []) {
            $this->error(__('admin/command/api-create-network-key.invalid_scope', [
                'scopes' => implode(', ', $unknown),
                'available' => implode(', ', $available),
            ]));

            return null;
        }

        return array_values(array_unique($scopes));
    }

    /**
     * @param  array<int, string>  $scopes
     */
    private function renderSummary(
        string $name,
        string $environment,
        array $scopes,
        ?int $rateLimit,
        ?\DateTimeInterface $expiresAt,
        ?string $description,
    ): void {
        $this->newLine();
        $this->warn(__('admin/command/api-create-network-key.warning'));
        $this->newLine();
        $this->info(__('admin/command/api-create-network-key.summary_title'));

        $this->table(
            [
                __('admin/command/api-create-network-key.field'),
                __('admin/command/api-create-network-key.value'),
            ],
            [
                [__('admin/command/api-create-network-key.summary_name'), $name],
                [__('admin/command/api-create-network-key.summary_environment'), $environment],
                [__('admin/command/api-create-network-key.summary_scopes'), $scopes === [] ? '—' : implode(', ', $scopes)],
                [__('admin/command/api-create-network-key.summary_rate_limit'), $rateLimit !== null ? $rateLimit.' /min' : '—'],
                [__('admin/command/api-create-network-key.summary_expires_at'), $expiresAt?->format('Y-m-d H:i:s') ?? '—'],
                [__('admin/command/api-create-network-key.summary_description'), $description ?? '—'],
            ]
        );

        $this->newLine();
    }

    private function confirmDangerousAction(): bool
    {
        return $this->confirm(__('admin/command/api-create-network-key.confirm_create'), false);
    }

    private function renderResult(ApiKey $apiKey, string $plainKey): void
    {
        $this->newLine();
        $this->info(__('admin/command/api-create-network-key.created'));
        $this->newLine();

        $this->warn(__('admin/command/api-create-network-key.plain_key_warning'));
        $this->line('  '.$plainKey);
        $this->newLine();

        $this->line(__('admin/command/api-create-network-key.id_label').': '.$apiKey->id);
        $this->line(__('admin/command/api-create-network-key.prefix_label').': '.$apiKey->key_prefix);
        $this->line(__('admin/command/api-create-network-key.audit_logged'));
    }
}
